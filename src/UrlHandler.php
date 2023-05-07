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
use dcUrlHandlers;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * Frontend URL handler.
 *
 * This adds public page that list feeds.
 * And serve an endpoint to update feeds through js.
 */
class UrlHandler extends dcUrlHandlers
{
    /**
     * Feeds source page and update methods.
     *
     * @param   string  $args   The page arguments
     */
    public static function zoneclearFeedsPage(string $args): void
    {
        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        # Not active
        if (is_null(dcCore::app()->blog) || !$s->active) {
            self::p404();
        }

        # Update feeds (from ajax or other post resquest)
        if ($args == '/zcfsupd' && 3 == $s->bhv_pub_upd) {
            $msg = '';
            if (!empty($_POST['blogId']) && Html::escapeJS(dcCore::app()->blog->id) == $_POST['blogId']) {
                try {
                    if ($z->checkFeedsUpdate()) {
                        $msg = sprintf(
                            '<status>%s</status><message>%s</message>',
                            'ok',
                            'Feeds updated successfully'
                        );
                    }
                } catch (Exception $e) {
                }
            }
            if (empty($msg)) {
                $msg = sprintf(
                    '<status>%s</status><message>%s</message>',
                    'failed',
                    'Failed to update feeds'
                );
            }

            header('Content-Type: application/xml; charset=UTF-8');
            echo
            '<?xml version="1.0" encoding="utf-8"?> ' . "\n" .
            '<response><rsp>' . "\n" .
            $msg . "\n" .
            '</rsp></response>';

            exit(1);

        # Server js
        } elseif ($args == '/zcfsupd.js' && 3 == $s->bhv_pub_upd) {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), My::path() . '/default-templates');
            self::serveDocument(
                'zcfsupd.js',
                'text/javascript',
                false,
                false
            );

        # Server feeds description page
        } elseif (in_array($args, ['', '/']) && $s->pub_active) {
            $theme = dcCore::app()->blog->settings->get('system')->get('theme');
            if (!is_string($theme)) {
                self::p404();
            }
            $tplset = dcCore::app()->themes->moduleInfo($theme, 'tplset');
            $path   = My::path() . '/default-templates/';
            if (!empty($tplset) && is_dir($path . $tplset)) {
                dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), $path . $tplset);
            } else {
                dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), $path . DC_DEFAULT_TPLSET);
            }
            self::serveDocument('zcfeeds.html');
        }
        # Unknow
        else {
            self::p404();
        }
    }
}
