<?php

/**
 * @package     Fero.Package
 * @subpackage  pkg_fgbackendlangswitcher
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            InstallerScriptInterface::class,
            new class () implements InstallerScriptInterface {
                public function install(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function preflight(string $type, InstallerAdapter $adapter): bool
                {
                    return true;
                }

                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    // Len pri prvej inštalácii balíka – nikdy pri update.
                    // Ak by administrátor inštanciu modulu neskôr zámerne
                    // zmazal, ďalšia aktualizácia balíka mu ju nemá vracať
                    // späť proti jeho vôli (rovnaká zásada ako pri
                    // auto-zapnutí system pluginu v jeho vlastnom script.php).
                    if ($type === 'install' || $type === 'discover_install') {
                        $this->createModuleInstance();
                    }

                    return true;
                }

                /**
                 * Vytvorí publikovanú inštanciu mod_fgbackendlangswitcher v
                 * pozícii "status" (Atum), viditeľnú na všetkých stránkach
                 * administrácie. Nič nerobí, ak už nejaká inštancia modulu
                 * existuje – nevytvára duplicity a neprepisuje prípadné
                 * ručne upravené nastavenia.
                 */
                private function createModuleInstance(): void
                {
                    $db = Factory::getContainer()->get(DatabaseInterface::class);

                    $countQuery = $db->getQuery(true)
                        ->select('COUNT(*)')
                        ->from($db->quoteName('#__modules'))
                        ->where($db->quoteName('module') . ' = ' . $db->quote('mod_fgbackendlangswitcher'));

                    if ((int) $db->setQuery($countQuery)->loadResult() > 0) {
                        return;
                    }

                    $columns = [
                        'title', 'note', 'content', 'ordering', 'position',
                        'checked_out', 'checked_out_time', 'publish_up', 'publish_down',
                        'published', 'module', 'access', 'showtitle', 'params',
                        'client_id', 'language',
                    ];

                    $values = [
                        $db->quote('FG Backend LangSwitcher'),
                        $db->quote(''),
                        $db->quote(''),
                        1,
                        $db->quote('status'),
                        0,
                        'NULL',
                        'NULL',
                        'NULL',
                        1,
                        $db->quote('mod_fgbackendlangswitcher'),
                        1,
                        0,
                        $db->quote('{}'),
                        1,
                        $db->quote('*'),
                    ];

                    $insertQuery = $db->getQuery(true)
                        ->insert($db->quoteName('#__modules'))
                        ->columns($db->quoteName($columns))
                        ->values(implode(',', $values));

                    $db->setQuery($insertQuery)->execute();

                    $moduleId = (int) $db->insertid();

                    if ($moduleId <= 0) {
                        return;
                    }

                    // menuid = 0 znamená "zobraziť na všetkých stránkach
                    // administrácie" – rovnaká konvencia ako u jadrových
                    // admin modulov (mod_menu, mod_toolbar,...).
                    $assignQuery = $db->getQuery(true)
                        ->insert($db->quoteName('#__modules_menu'))
                        ->columns($db->quoteName(['moduleid', 'menuid']))
                        ->values($moduleId . ', 0');

                    $db->setQuery($assignQuery)->execute();
                }
            }
        );
    }
};
