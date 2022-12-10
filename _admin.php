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

dcCore::app()->blog->settings->addNamespace(basename(__DIR__));

require_once __DIR__ . '/_widgets.php';

$perm = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
    dcAuth::PERMISSION_CONTENT_ADMIN,
]), dcCore::app()->blog->id);

if (dcCore::app()->blog->settings->__get(basename(__DIR__))->zoneclearFeedServer_active
    && '' != dcCore::app()->blog->settings->__get(basename(__DIR__))->zoneclearFeedServer_user
) {
    dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
        __('Feeds server'),
        dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__)),
        dcPage::getPF(basename(__DIR__) . '/icon.svg'),
        preg_match(
            '/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . basename(__DIR__))) . '(&.*)?$/',
            $_SERVER['REQUEST_URI']
        ),
        $perm
    );

    if ($perm) {
        # Dashboard icon
        dcCore::app()->addBehavior('adminDashboardFavoritesV2', ['zcfsAdminBehaviors', 'adminDashboardFavoritesV2']);
        # User pref
        dcCore::app()->addBehavior('adminColumnsListsV2', ['zcfsAdminBehaviors', 'adminColumnsListsV2']);
        dcCore::app()->addBehavior('adminFiltersListsV2', ['zcfsAdminBehaviors', 'adminFiltersListsV2']);
        # Add info about feed on post page sidebar
        dcCore::app()->addBehavior('adminPostHeaders', ['zcfsAdminBehaviors', 'adminPostHeaders']);
        dcCore::app()->addBehavior('adminPostFormItems', ['zcfsAdminBehaviors', 'adminPostFormItems']);
    }

    # Take care about tweakurls (thanks Mathieu M.)
    if (version_compare(dcCore::app()->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {
        dcCore::app()->addbehavior('zcfsAfterPostCreate', ['zoneclearFeedServer', 'tweakurlsAfterPostCreate']);
    }
}

# Delete related info about feed post in meta table
dcCore::app()->addBehavior('adminBeforePostDelete', ['zcfsAdminBehaviors', 'adminBeforePostDelete']);
