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
use Dotclear\Core\Backend\{
    Notices,
    ModulesList,
    Page
};
use Dotclear\Core\Process;
use Exception;

/**
 * Backend module configuration.
 */
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // no action
        if (empty($_POST['save'])) {
            return true;
        }

        try {
            BackendBehaviors::adminBeforeBlogSettingsUpdate(null);

            Notices::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            dcCore::app()->admin->url->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
                'redir'  => !(dcCore::app()->admin->__get('list') instanceof ModulesList) ? '' : dcCore::app()->admin->__get('list')->getRedir(),
            ]);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        BackendBehaviors::adminBlogPreferencesFormV2(null);

        Page::helpBlock('zoneclearFeedServer');
    }
}
