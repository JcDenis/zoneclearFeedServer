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

use dcCore;
use Dotclear\Core\Process;

/**
 * Backend prepend.
 */
class Backend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::BACKEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
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
            'UninstallerCleanersConstruct' => function ($uninstaller_stack) {
                UninstallCleaner::init($uninstaller_stack);
            },
            'adminBeforeBlogSettingsUpdate' => [BackendBehaviors::class, 'adminBeforeBlogSettingsUpdate'],
            'adminBlogPreferencesFormV2'    => [BackendBehaviors::class, 'adminBlogPreferencesFormV2'],
        ]);

        // not active
        if (!My::settings()->get('active') || '' == My::settings()->get('user')) {
            return false;
        }

        // no perm
        if (!My::checkContext(My::MENU)) {
            return true;
        }

        // sidebar menu
        My::addBackendMenuItem();

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
