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

use adminModulesList;
use dcCore;
use dcPage;
use dcNsProcess;
use Exception;

/**
 * Backend module configuration.
 */
class Config extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth?->isSuperAdmin();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // no action
        if (empty($_POST['save'])) {
            return true;
        }

        try {
            BackendBehaviors::adminBeforeBlogSettingsUpdate(null);

            dcPage::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            dcCore::app()->adminurl?->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
                'redir'  => !(dcCore::app()->admin->__get('list') instanceof adminModulesList) ? '' : dcCore::app()->admin->__get('list')->getRedir(),
            ]);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        BackendBehaviors::adminBlogPreferencesFormV2(null);

        dcPage::helpBlock('zoneclearFeedServer');
    }
}
