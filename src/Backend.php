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
declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use dcAdmin;
use dcCore;
use dcPage;
use dcMenu;
use dcNsProcess;

/**
 * Backend prepend.
 */
class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN');

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // behaviors that will be always loaded
        dcCore::app()->addBehaviors([
            // Allways take care to delete related info about feed post in meta table
            'adminBeforePostDelete' => function (int $post_id): void {
                ZoneclearFeedServer::instance()::deletePostsMeta($post_id);
            },
            // widgets registration
            'initWidgets' => [Widgets::class, 'init'],
            // add Uninstaller cleaner for special direct action
            'UninstallerCleanersConstruct' => [UninstallCleaner::class, 'init'],
        ]);

        // nullsafe
        if (is_null(dcCore::app()->auth)
            || is_null(dcCore::app()->blog)
            || is_null(dcCore::app()->adminurl)
        ) {
            return false;
        }

        // not active
        if (!dcCore::app()->blog->settings->get(My::id())->get('active')
            || '' == dcCore::app()->blog->settings->get(My::id())->get('user')
        ) {
            return false;
        }

        // get user perm
        $has_perm = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id);

        // add sidebar menu icon
        if ((dcCore::app()->menu[dcAdmin::MENU_PLUGINS] instanceof dcMenu)) {
            dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
                My::name(),
                dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                dcPage::getPF(My::id() . '/icon.svg'),
                preg_match(
                    '/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . My::id())) . '(&.*)?$/',
                    $_SERVER['REQUEST_URI']
                ),
                $has_perm
            );
        }

        // no perm
        if (!$has_perm) {
            return true;
        }

        // behaviors that require user perm
        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => [BackendBehaviors::class, 'adminDashboardFavoritesV2'],
            'adminColumnsListsV2'       => [BackendBehaviors::class, 'adminColumnsListsV2'],
            'adminFiltersListsV2'       => [BackendBehaviors::class, 'adminFiltersListsV2'],
            'adminPostListHeaderV2'     => [BackendBehaviors::class, 'adminPostListHeaderV2'],
            'adminPostListValueV2'      => [BackendBehaviors::class, 'adminPostListValueV2'],
            'adminPostHeaders'          => [BackendBehaviors::class, 'adminPostHeaders'],
            'adminPostFormItems'        => [BackendBehaviors::class, 'adminPostFormItems'],
        ]);

        return true;
    }
}
