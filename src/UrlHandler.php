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
if (!defined('DC_RC_PATH')) {
    return null;
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - url handler methods.
 * @since 2.6
 */
class zcfsUrlHandler extends dcUrlHandlers
{
    /**
     * Feeds source page and update methods.
     *
     * @param  array $args Page arguments
     * @return mixed
     */
    public static function zcFeedsPage($args)
    {
        $s = dcCore::app()->blog->settings->__get(basename(dirname('../' . __DIR__)));

        # Not active
        if (!$s->active) {
            self::p404();

            return null;
        }

        # Update feeds (from ajax or other post resquest)
        if ($args == '/zcfsupd' && 3 == $s->bhv_pub_upd) {
            $msg = '';
            if (!empty($_POST['blogId']) && html::escapeJS(dcCore::app()->blog->id) == $_POST['blogId']) {
                try {
                    $zc = new zoneclearFeedServer();
                    if ($zc->checkFeedsUpdate()) {
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
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates');
            self::serveDocument(
                'zcfsupd.js',
                'text/javascript',
                false,
                false
            );

        # Server feeds description page
        } elseif (in_array($args, ['', '/']) && $s->pub_active) {
            $tplset = dcCore::app()->themes->moduleInfo(dcCore::app()->blog->settings->system->theme, 'tplset');
            $path   = __DIR__ . '/default-templates/';
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

        return null;
    }
}
