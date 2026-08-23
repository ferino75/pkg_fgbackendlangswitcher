<?php

/**
 * @package     FG.Module
 * @subpackage  mod_fgbackendlangswitcher
 */

namespace FG\Module\Fgbackendlangswitcher\Administrator\Helper;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

/**
 * Helper for mod_fgbackendlangswitcher
 */
class FgbackendlangswitcherHelper
{
    /**
     * Marker value used for "reset to default language". Also duplicated
     * (intentionally) as a private constant in
     * plg_system_fgbackendlangswitcher – see that class for why.
     */
    public const DEFAULT_MARKER = 'default';

    /**
     * URL variable name carrying the requested language tag.
     */
    public const URL_VAR = 'blswitch';

    /**
     * Session key used for the temporary (non-persisted) language switch.
     * Also duplicated (intentionally, not referenced) as a private constant
     * in plg_system_fgbackendlangswitcher, to keep that system plugin free of
     * a hard class dependency on this module – the plugin runs on every
     * backend request, so it must not be able to fatal-error just because
     * this module is missing, broken, or has a changed namespace.
     */
    public const SESSION_KEY = 'mod_fgbackendlangswitcher.language';

    /**
     * Returns installed administrator languages with metadata, sorted by native name.
     *
     * @param   AdministratorApplication  $app  The application.
     *
     * @return  object[]  List of language objects (element, metadata, active).
     */
    public function getLanguages(AdministratorApplication $app): array
    {
        // 1 = administrator client, true = process metadata from XML manifest
        $languages = LanguageHelper::getInstalledLanguages(1, true);
        $current   = $app->getLanguage()->getTag();

        foreach ($languages as $language) {
            $meta = (array) ($language->metadata ?? []);

            $language->tag        = $language->element;
            $language->nativeName = $meta['nativeName'] ?? ($meta['name'] ?? $language->element);
            $language->engName    = $meta['name'] ?? $language->element;
            $language->rtl        = (int) ($meta['rtl'] ?? 0);
            $language->active     = ($language->element === $current);
        }

        $this->sortByNativeName($languages, $current);

        return array_values($languages);
    }

    /**
     * Sorts languages by native name, in place. Prefers an ICU Collator
     * (locale-aware, independent of the server's configured locales) and
     * falls back to strcoll() when the intl extension isn't available.
     *
     * @param   object[]  $languages    Language objects (modified in place).
     * @param   string    $currentTag   Tag of the currently active language, used to pick the collator's locale.
     *
     * @return  void
     */
    private function sortByNativeName(array &$languages, string $currentTag): void
    {
        if (\class_exists(\Collator::class)) {
            $collator = \Collator::create($currentTag);

            if ($collator !== null) {
                usort(
                    $languages,
                    static fn ($a, $b) => $collator->compare($a->nativeName, $b->nativeName)
                );

                return;
            }
        }

        // Fallback bez ext-intl: strcoll() závisí od locale nastavenej na
        // serveri (setlocale) – ak hosting nemá cieľovú locale nainštalovanú,
        // diakritika sa môže radiť podľa ASCII hodnôt namiesto abecedne.
        usort(
            $languages,
            static fn ($a, $b) => strcoll($a->nativeName, $b->nativeName)
        );
    }

    /**
     * Checks whether the user has any custom selection (temporary or persisted),
     * i.e. whether the "Default" reset option should be offered.
     *
     * @param   AdministratorApplication  $app  The application.
     *
     * @return  boolean
     */
    public function hasCustomSelection(AdministratorApplication $app): bool
    {
        $sessionTag = (string) $app->getSession()->get(self::SESSION_KEY, '');
        $userTag    = (string) ($app->getIdentity()?->getParam('admin_language', '') ?? '');

        // Marker "Predvolený" v session bez profilového jazyka znamená, že
        // je už aktívny predvolený jazyk webu – niet čo prebiť, takže voľbu
        // "Predvolený" netreba znova ponúkať.
        return ($sessionTag !== '' && $sessionTag !== self::DEFAULT_MARKER) || $userTag !== '';
    }

    /**
     * Processes a language switch request. Depending on the module's
     * "persist" parameter the selection is stored either in the session
     * (temporary, applied by plg_system_fgbackendlangswitcher) or in the
     * user's admin_language profile parameter (permanent).
     *
     * @param   AdministratorApplication  $app     The application.
     * @param   Input                     $input   The request input.
     * @param   Registry                  $params  The module parameters.
     *
     * @return  void
     */
    public function processSwitch(AdministratorApplication $app, Input $input, Registry $params): void
    {
        $requested = $input->getCmd(self::URL_VAR, '');

        if ($requested === '') {
            return;
        }

        // CSRF ochrana (token v GET)
        if (!Session::checkToken('get')) {
            $app->enqueueMessage(Text::_('JINVALID_TOKEN_NOTICE'), 'warning');
            $this->redirectClean($app);

            return;
        }

        $user = $app->getIdentity();

        if (!$user || $user->guest) {
            return;
        }

        $persist = (bool) $params->get('persist', 0);
        $session = $app->getSession();

        // Dočasný režim funguje len so zapnutým system pluginom.
        if (!$persist && !$this->isTempSwitchPluginEnabled()) {
            $app->enqueueMessage(Text::_('MOD_FGBACKENDLANGSWITCHER_ERROR_PLUGIN_DISABLED'), 'warning');
            $this->redirectClean($app);

            return;
        }

        $newLang = '';
        $label   = Text::_('MOD_FGBACKENDLANGSWITCHER_DEFAULT_LANGUAGE');

        if ($requested !== self::DEFAULT_MARKER) {
            $installed = LanguageHelper::getInstalledLanguages(1, true);

            if (!isset($installed[$requested])) {
                $app->enqueueMessage(Text::_('MOD_FGBACKENDLANGSWITCHER_ERROR_NOT_INSTALLED'), 'warning');
                $this->redirectClean($app);

                return;
            }

            $newLang = $requested;
            $meta    = (array) ($installed[$requested]->metadata ?? []);
            $label   = ($meta['nativeName'] ?? $requested) . ' (' . $requested . ')';
        } else {
            // Čisto na zobrazenie v hlásení – nemení, čo sa reálne ukladá
            // ($newLang zostáva '', do session ide marker ako doteraz).
            // Rovnaké rozlíšenie ako v pluginovej logike (1.2.16): toto NIE
            // JE $app->get('language') (site default), ale samostatné
            // administrátorské nastavenie.
            $resolvedTag = (string) ComponentHelper::getParams('com_languages')
                ->get('administrator', $app->get('language'));

            if ($resolvedTag !== '') {
                $label .= ' → ' . $resolvedTag;
            }
        }

        if ($persist) {
            $user->setParam('admin_language', $newLang);

            $saveError = null;

            try {
                $saved = $user->save();
            } catch (\Throwable $e) {
                $saved     = false;
                $saveError = $e->getMessage();
            }

            if (!$saved) {
                $detail = $saveError ?? 'User::save() returned false (no exception, likely validation failure).';

                $this->logError(\sprintf(
                    'Failed to save admin_language for user #%d: %s',
                    (int) $user->id,
                    $detail
                ));

                $message = Text::_('MOD_FGBACKENDLANGSWITCHER_ERROR_SAVE');

                // V debug režime (System → Global Configuration → Debug
                // System) pridaj konkrétny detail priamo do hlásenia –
                // administrátor si ho vedome zapol, takže vie, že sa
                // zobrazujú diagnostické detaily.
                if ($app->get('debug')) {
                    $message .= ' ' . Text::sprintf('MOD_FGBACKENDLANGSWITCHER_ERROR_SAVE_DEBUG', $detail);
                }

                $app->enqueueMessage($message, 'error');
                $this->redirectClean($app);

                return;
            }

            // Trvalá voľba ruší prípadné dočasné prepnutie.
            $session->set(self::SESSION_KEY, null);

            $app->enqueueMessage(Text::sprintf('MOD_FGBACKENDLANGSWITCHER_SWITCHED', $label), 'message');
        } else {
            // Dočasne: len do session, profil sa nemení.
            //
            // "Predvolený" treba do session uložiť ako explicitný marker,
            // nie len vymazať kľúč – keby sme kľúč len vymazali, plugin by
            // v ďalšom requeste nenašiel žiadnu dočasnú voľbu a použil by
            // sa admin_language z profilu (ak ho má používateľ nastavený),
            // nie skutočný predvolený jazyk webu. Marker umožňuje pluginu
            // tieto dva stavy rozlíšiť.
            $session->set(
                self::SESSION_KEY,
                $requested === self::DEFAULT_MARKER ? self::DEFAULT_MARKER : $newLang
            );

            $app->enqueueMessage(Text::sprintf('MOD_FGBACKENDLANGSWITCHER_SWITCHED_TEMP', $label), 'message');
        }

        $this->redirectClean($app);
    }

    /**
     * Whether plg_system_fgbackendlangswitcher is enabled, required for the
     * temporary (session) switching mode. Cached per request – within a
     * single request this can't change, and PluginHelper::isEnabled()
     * already caches the plugin list internally, but caching here too
     * avoids relying on that internal detail.
     *
     * @return  boolean
     */
    private function isTempSwitchPluginEnabled(): bool
    {
        static $enabled = null;

        if ($enabled === null) {
            $enabled = PluginHelper::isEnabled('system', 'fgbackendlangswitcher');
        }

        return $enabled;
    }

    /**
     * Logs an error to mod_fgbackendlangswitcher.php in Joomla's configured
     * log directory (System → Global Configuration → Server → Log Path;
     * defaults to administrator/logs/, but can be changed). Independent
     * of the site's global *logging* configuration (which categories get
     * logged) – this uses its own dedicated file/category regardless.
     * Registered lazily – only when there's actually something to log.
     *
     * @param   string  $message  The message to log.
     *
     * @return  void
     */
    private function logError(string $message): void
    {
        static $loggerAdded = false;

        if (!$loggerAdded) {
            Log::addLogger(['text_file' => 'mod_fgbackendlangswitcher.php'], Log::ALL, ['mod_fgbackendlangswitcher']);
            $loggerAdded = true;
        }

        Log::add($message, Log::ERROR, 'mod_fgbackendlangswitcher');
    }

    /**
     * Redirects to the current URL stripped of the switcher variables,
     * so the new language is applied on the next request.
     *
     * @param   AdministratorApplication  $app  The application.
     *
     * @return  void
     */
    private function redirectClean(AdministratorApplication $app): void
    {
        $uri = clone Uri::getInstance();
        $uri->delVar(self::URL_VAR);
        $uri->delVar(Session::getFormToken());

        $app->redirect($uri->toString(['path', 'query', 'fragment']));
    }
}
