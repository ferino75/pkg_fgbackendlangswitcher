<?php

/**
 * @package     FG.Module
 * @subpackage  mod_fgbackendlangswitcher
 */

\defined('_JEXEC') or die;

use FG\Module\Fgbackendlangswitcher\Administrator\Helper\FgbackendlangswitcherHelper as Helper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

if (empty($languages)) {
    return;
}

$style       = $params->get('style', 'dropdown');
$showNative  = (bool) $params->get('shownative', 1);
$showCode    = (bool) $params->get('showcode', 1);
$showDefault = (bool) $params->get('showdefault', 1);
$suffix      = htmlspecialchars($params->get('moduleclass_sfx', ''), ENT_COMPAT, 'UTF-8');
$icon        = htmlspecialchars($params->get('icon', 'icon-language'), ENT_QUOTES, 'UTF-8');

$token = Session::getFormToken();

// Odkaz zachová aktuálnu stránku – pridá len prepínacie premenné
$makeLink = static function (string $tag) use ($token): string {
    $uri = clone Uri::getInstance();
    $uri->setVar(Helper::URL_VAR, $tag);
    $uri->setVar($token, '1');

    return $uri->toString(['path', 'query']);
};

$makeLabel = static function (object $lang) use ($showNative, $showCode): string {
    $parts = [];

    $parts[] = $showNative ? $lang->nativeName : $lang->engName;

    if ($showCode) {
        $parts[] = '(' . $lang->tag . ')';
    }

    return htmlspecialchars(implode(' ', $parts), ENT_QUOTES, 'UTF-8');
};

$current = null;

foreach ($languages as $lang) {
    if ($lang->active) {
        $current = $lang;
        break;
    }
}
?>
<?php if ($style === 'inline') : ?>
<nav class="mod-fgbackendlangswitcher mod-fgbackendlangswitcher--inline <?php echo $suffix; ?>"
     aria-label="<?php echo Text::_('MOD_FGBACKENDLANGSWITCHER'); ?>">
    <ul class="list-inline mb-0">
        <?php foreach ($languages as $lang) : ?>
            <li class="list-inline-item">
                <?php if ($lang->active) : ?>
                    <span class="badge bg-primary"><?php echo $makeLabel($lang); ?></span>
                <?php else : ?>
                    <a class="badge bg-secondary text-decoration-none"
                       href="<?php echo htmlspecialchars($makeLink($lang->tag), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo $makeLabel($lang); ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ($showDefault && !empty($hasCustom)) : ?>
            <li class="list-inline-item">
                <a class="badge bg-light text-dark text-decoration-none border"
                   href="<?php echo htmlspecialchars($makeLink(Helper::DEFAULT_MARKER), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo Text::_('MOD_FGBACKENDLANGSWITCHER_DEFAULT_LANGUAGE'); ?>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php else : ?>
<?php HTMLHelper::_('bootstrap.dropdown'); ?>
<div class="mod-fgbackendlangswitcher header-item-content dropdown <?php echo $suffix; ?>">
    <button type="button"
            class="dropdown-toggle d-flex align-items-center ps-0 py-0"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            title="<?php echo Text::_('MOD_FGBACKENDLANGSWITCHER_SWITCH_LABEL'); ?>">
        <div class="header-item-icon">
            <span class="<?php echo $icon; ?>" aria-hidden="true"></span>
        </div>
        <div class="header-item-text">
            <?php echo $current !== null ? htmlspecialchars($current->tag, ENT_QUOTES, 'UTF-8') : Text::_('MOD_FGBACKENDLANGSWITCHER'); ?>
        </div>
        <span class="icon-angle-down" aria-hidden="true"></span>
    </button>
    <div class="dropdown-menu dropdown-menu-end">
        <?php foreach ($languages as $lang) : ?>
            <?php if ($lang->active) : ?>
                <span class="dropdown-item active" aria-current="true">
                    <?php echo $makeLabel($lang); ?>
                </span>
            <?php else : ?>
                <a class="dropdown-item"
                   href="<?php echo htmlspecialchars($makeLink($lang->tag), ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo $makeLabel($lang); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($showDefault && !empty($hasCustom)) : ?>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item"
               href="<?php echo htmlspecialchars($makeLink(Helper::DEFAULT_MARKER), ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo Text::_('MOD_FGBACKENDLANGSWITCHER_DEFAULT_LANGUAGE'); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
