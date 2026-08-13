<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       zoneclearFeedServer frontend URL handler.
 * @ingroup     zoneclearFeedServer
 *
 * This adds public page that list feeds.
 * And serve an endpoint to update feeds through js.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UrlHandler
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
        if (!App::blog()->isDefined() || !$s->active) {
            App::url()::p404();
        }

        # Update feeds (from ajax or other post resquest)
        if ($args == '/zcfsupd' && 3 == $s->bhv_pub_upd) {
            $msg = '';
            if (!empty($_POST['blogId']) && Html::escapeJS(App::blog()->id()) == $_POST['blogId']) {
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
            App::frontend()->template()->appendPath(My::path() . '/default-templates');
            App::url()::serveDocument(
                'zcfsupd.js',
                'text/javascript',
                false,
                false
            );

            # Server feeds description page
        } elseif (in_array($args, ['', '/']) && $s->pub_active) {
            $theme = App::blog()->settings()->get('system')->get('theme');
            if (!is_string($theme)) {
                App::url()::p404();
            }
            $id = App::blog()->settings()->get('system')->get('theme');
            $tplset = is_string($id) ? App::themes()->getDefine($id)->get('tplset') : '';
            $paths = implode(DIRECTORY_SEPARATOR, array_filter([My::path(), 'default-templates', $tplset], is_string(...)));
            if (empty($tplset) || !is_dir($paths)) {
                $tplset = App::config()->defaultTplset();
            }
            App::frontend()->template()->appendPath($paths);
            App::url()::serveDocument('zcfeeds.html');
        }
        # Unknow
        else {
            App::url()::p404();
        }
    }
}
