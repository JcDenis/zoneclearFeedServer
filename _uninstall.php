<?php
/**
 * @brief zoneclearFeedServer, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis, BG, Pierre Van Glabeke
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

$this->addUserAction(
    /* type */
    'settings',
    /* action */
    'delete_all',
    /* ns */
    basename(__DIR__),
    /* desc */
    __('delete all settings')
);
$this->addUserAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initZoneclearFeedServer::TABLE_NAME,
    /* desc */
    __('delete table')
);
$this->addUserAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* desc */
    __('delete plugin files')
);
$this->addUserAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* desc */
    __('delete the version number')
);

$this->addDirectAction(
    /* type */
    'settings',
    /* action */
    'delete_all',
    /* ns */
    basename(__DIR__),
    /* desc */
    sprintf(__('delete all %s settings'), basename(__DIR__))
);
$this->addDirectAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initZoneclearFeedServer::TABLE_NAME,
    /* desc */
    sprintf(__('delete %s table'), basename(__DIR__))
);
$this->addDirectAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* desc */
    sprintf(__('delete %s plugin files'), basename(__DIR__))
);
$this->addDirectAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    basename(__DIR__),
    /* desc */
    sprintf(__('delete %s version number'), basename(__DIR__))
);
$this->addDirectCallback(
    /* function */
    'zoneclearfeedServerUninstall',
    /* desc */
    'delete feeds relations'
);

function zoneclearfeedServerUninstall($id)
{
    if ($id != basename(__DIR__)) {
        return null;
    }
    //...
}
