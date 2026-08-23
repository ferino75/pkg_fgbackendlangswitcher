<?php

/**
 * @package     FG.Module
 * @subpackage  mod_fgbackendlangswitcher
 */

namespace FG\Module\Fgbackendlangswitcher\Administrator\Dispatcher;

use FG\Module\Fgbackendlangswitcher\Administrator\Helper\FgbackendlangswitcherHelper;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;

\defined('_JEXEC') or die;

/**
 * Dispatcher class for mod_fgbackendlangswitcher
 */
class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    /**
     * Returns the layout data.
     *
     * @return  array
     */
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $app  = $this->getApplication();

        // Na prihlasovacej obrazovke (napr. /administrator bez session)
        // ešte neexistuje prihlásený používateľ, ktorému by sa dal jazyk
        // administrácie prepnúť – modul by sa zobrazil, ale kliknutie by
        // ticho nič nespravilo. Radšej sa vôbec nevykresľuje.
        if (!$app->getIdentity() || $app->getIdentity()->guest) {
            $data['languages'] = [];

            return $data;
        }

        /** @var FgbackendlangswitcherHelper $helper */
        $helper = $this->getHelperFactory()->getHelper('FgbackendlangswitcherHelper');

        // Ak prišla požiadavka na prepnutie jazyka, spracuj ju (uloží voľbu a presmeruje)
        $helper->processSwitch($app, $this->input, $data['params']);

        $data['languages']  = $helper->getLanguages($app);
        $data['currentTag'] = $app->getLanguage()->getTag();
        $data['hasCustom']  = $helper->hasCustomSelection($app);

        return $data;
    }
}
