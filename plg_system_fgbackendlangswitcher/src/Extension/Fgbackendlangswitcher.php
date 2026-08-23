<?php

/**
 * @package     FG.Plugin
 * @subpackage  System.fgbackendlangswitcher
 */

namespace FG\Plugin\System\Fgbackendlangswitcher\Extension;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Applies a temporary (session-scoped) administrator language selected
 * via mod_fgbackendlangswitcher.
 */
final class Fgbackendlangswitcher extends CMSPlugin implements SubscriberInterface
{
    /**
     * Session key used for the temporary (non-persisted) language switch.
     * Intentionally duplicated from FgbackendlangswitcherHelper::SESSION_KEY
     * (mod_fgbackendlangswitcher) rather than referenced across extensions –
     * this plugin runs on every backend request via onAfterInitialise, so
     * a hard class dependency on the module (which could be uninstalled,
     * broken, or have its namespace changed independently) risks a fatal
     * error before the admin panel even loads. A one-line stable constant
     * is cheaper to keep in sync than that blast radius is worth avoiding.
     */
    private const SESSION_KEY = 'mod_fgbackendlangswitcher.language';

    /**
     * Marker value used for "reset to default language". Duplicated from
     * FgbackendlangswitcherHelper::DEFAULT_MARKER for the same reason as
     * SESSION_KEY above.
     */
    private const DEFAULT_MARKER = 'default';

    /**
     * @return  array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterInitialise' => 'applySessionLanguage',
        ];
    }

    /**
     * Swaps the administrator language for the current request when a
     * temporary selection is stored in the session.
     *
     * @return  void
     */
    public function applySessionLanguage(): void
    {
        $app = $this->getApplication();

        if (!$app || !$app->isClient('administrator')) {
            return;
        }

        $session = $app->getSession();
        $tag     = (string) $session->get(self::SESSION_KEY, '');

        if ($tag === '') {
            return;
        }

        // Explicitná voľba "Predvolený" v dočasnom režime – rozlišujeme ju
        // od "žiadne dočasné prepnutie" (prázdny session kľúč). Bez tohto
        // rozlíšenia by vymazanie session kľúča znamenalo len návrat k
        // admin_language z profilu používateľa (ak ho má nastavený), nie
        // k skutočnému predvolenému jazyku administrácie webu.
        if ($tag === self::DEFAULT_MARKER) {
            $tag = (string) $app->get('language');
        }

        // Jazyk medzičasom odinštalovaný? Uprac session a skonči.
        if (!LanguageHelper::exists($tag, JPATH_ADMINISTRATOR)) {
            $session->set(self::SESSION_KEY, null);

            return;
        }

        if ($app->getLanguage()->getTag() === $tag) {
            return;
        }

        $language = Language::getInstance($tag, (bool) $app->get('debug_lang'));

        // loadLanguage() nastaví jazyk aplikácie ($app->getLanguage()).
        // Viaceré staršie časti jadra (napr. AdministratorMenuHelper pri
        // zostavovaní ľavého menu) však stále čítajú jazyk cez legacy
        // statickú Factory::getLanguage(), nie cez $app->getLanguage() –
        // preto treba Factory::$language nastaviť aj explicitne, inak sa
        // časť administrácie prekladá z pôvodného (nesprávneho) jazyka.
        $app->loadLanguage($language);
        Factory::$language = $language;

        // Rovnaké domény, aké aplikácia načíta pri bežnej inicializácii:
        // 'joomla' nesie hlavné reťazce admin UI (menu, tlačidlá, JYES/JNO...),
        // 'lib_joomla' reťazce jadra knižnice. Ak preklad pre cieľový jazyk
        // chýba, spadni na predvolený jazyk webu (rovnaký vzor ako jadro),
        // nech nezostanú nepreložené kľúče.
        $language->load('joomla', JPATH_ADMINISTRATOR, null, false, true)
            || $language->load('joomla', JPATH_ADMINISTRATOR, $app->get('language'), false, true);

        $language->load('lib_joomla', JPATH_ADMINISTRATOR, null, false, true)
            || $language->load('lib_joomla', JPATH_ADMINISTRATOR, $app->get('language'), false, true);
    }
}
