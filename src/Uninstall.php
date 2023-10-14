<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\Core\Process;
use Dotclear\Plugin\Uninstaller\Uninstaller;

/**
 * @brief       zoneclearFeedServer uninstall class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Uninstall extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::UNINSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (!empty($_POST[My::id() . 'DeletePostsMeta'])) {
            ZoneclearFeedServer::instance()::deletePostsMeta(null);
        }

        Uninstaller::instance()
            ->addUserAction(
                'settings',
                'delete_all',
                My::id()
            )
            ->addUserAction(
                My::id() . 'DeletePostsMeta',
                'delete_all',
                My::id()
            )
            ->addUserAction(
                'tables',
                'delete',
                My::TABLE_NAME,
            )
            ->addUserAction(
                'plugins',
                'delete',
                My::id()
            )
            ->addUserAction(
                'versions',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'settings',
                'delete_all',
                My::id()
            )
            ->addDirectAction(
                My::id() . 'DeletePostsMeta',
                'delete_all',
                My::id()
            )
            ->addDirectAction(
                'tables',
                'delete',
                My::TABLE_NAME
            )
            ->addDirectAction(
                'plugins',
                'delete',
                My::id()
            )
            ->addDirectAction(
                'versions',
                'delete',
                My::id()
            )
        ;

        return false;
    }
}
