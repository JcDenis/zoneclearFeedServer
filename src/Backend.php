<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Core\Process;

/**
 * @brief       zoneclearFeedServer backend class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
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
        App::behavior()->addBehaviors([
            // Allways take care to delete related info about feed post in meta table
            'adminBeforePostDelete' => function (int $post_id): void {
                ZoneclearFeedServer::instance()::deletePostsMeta($post_id);
            },
            // widgets registration
            'initWidgets' => Widgets::init(...),
            // add Uninstaller cleaner for special direct action
            'UninstallerCleanersConstruct' => function ($uninstaller_stack) {
                UninstallCleaner::init($uninstaller_stack);
            },
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
        App::behavior()->addBehaviors([
            'adminDashboardFavoritesV2' => BackendBehaviors::adminDashboardFavoritesV2(...),
            'adminColumnsListsV2'       => BackendBehaviors::adminColumnsListsV2(...),
            'adminFiltersListsV2'       => BackendBehaviors::adminFiltersListsV2(...),
            'adminPostListHeaderV2'     => BackendBehaviors::adminPostListHeaderV2(...),
            'adminPostListValueV2'      => BackendBehaviors::adminPostListValueV2(...),
            'adminPostHeaders'          => BackendBehaviors::adminPostHeaders(...),
            'adminPostFormItems'        => BackendBehaviors::adminPostFormItems(...),
        ]);

        return true;
    }
}
