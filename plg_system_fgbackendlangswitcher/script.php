<?php

/**
 * @package     FG.Plugin
 * @subpackage  System.fgbackendlangswitcher
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
                    // Pri prvej instalacii plugin rovno zapni
                    if ($type === 'install' || $type === 'discover_install') {
                        $db = Factory::getContainer()->get(DatabaseInterface::class);

                        $query = $db->getQuery(true)
                            ->update($db->quoteName('#__extensions'))
                            ->set($db->quoteName('enabled') . ' = 1')
                            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
                            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
                            ->where($db->quoteName('element') . ' = ' . $db->quote('fgbackendlangswitcher'));

                        $db->setQuery($query)->execute();
                    }

                    return true;
                }
            }
        );
    }
};
