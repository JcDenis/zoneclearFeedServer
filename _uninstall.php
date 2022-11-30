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

$mod_id = 'zoneclearFeedServer';

$this->addUserAction(
    /* type */
    'settings',
    /* action */
    'delete_all',
    /* ns */
    $mod_id,
    /* desc */
    __('delete all settings')
);
$this->addUserAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initZoneclearFeedServer::FEED_TABLE_NAME,
    /* desc */
    __('delete table')
);
$this->addUserAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    $mod_id,
    /* desc */
    __('delete plugin files')
);
$this->addUserAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    $mod_id,
    /* desc */
    __('delete the version number')
);

$this->addDirectAction(
    /* type */
    'settings',
    /* action */
    'delete_all',
    /* ns */
    $mod_id,
    /* desc */
    sprintf(__('delete all %s settings'), $mod_id)
);
$this->addDirectAction(
    /* type */
    'tables',
    /* action */
    'delete',
    /* ns */
    initZoneclearFeedServer::FEED_TABLE_NAME,
    /* desc */
    sprintf(__('delete %s table'), $mod_id)
);
$this->addDirectAction(
    /* type */
    'plugins',
    /* action */
    'delete',
    /* ns */
    $mod_id,
    /* desc */
    sprintf(__('delete %s plugin files'), $mod_id)
);
$this->addDirectAction(
    /* type */
    'versions',
    /* action */
    'delete',
    /* ns */
    $mod_id,
    /* desc */
    sprintf(__('delete %s version number'), $mod_id)
);
$this->addDirectCallback(
    /* function */
    'zoneclearfeedServerUninstall',
    /* desc */
    'delete feeds relations'
);

function zoneclearfeedServerUninstall($id)
{
    if ($id != 'zoneclearFeedServer') {
        return null;
    }
    //...
}
