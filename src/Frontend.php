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
use dcUtils;
use Dotclear\Core\Process;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * Frontend prepend.
 */
class Frontend extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::FRONTEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $s = ZoneclearFeedServer::instance()->settings;

        dcCore::app()->addBehaviors([
            // posts record
            'coreBlogGetPosts' => function (MetaRecord $rs): void {
                RsExtPosts::$brother_extensions = $rs->extensions();
                $rs->extend(RsExtPosts::class);
            },
            // breadcrumb
            'publicBreadcrumb' => function (string $context, string $separator): string {
                return $context == 'zoneclearFeedsPage' ? __('List of feeds') : '';
            },
            // widgets registration
            'initWidgets' => [Widgets::class, 'init'],
        ]);

        // Register template blocks
        foreach (My::TPL_BLOCKS as $block) {
            dcCore::app()->tpl->addBlock('zc' . $block, [Template::class, $block]);
        }

        // Register template values
        foreach (My::TPL_VALUES as $value) {
            dcCore::app()->tpl->addValue('zc' . $value, [Template::class, $value]);
        }

        // module not active
        if (!$s->active) {
            return true;
        }

        // feeds update methods
        if (1 == $s->bhv_pub_upd) {
            dcCore::app()->addBehavior('publicBeforeDocumentV2', function (): void {
                if (in_array(dcCore::app()->url->type, ['default', 'feed'])) {
                    try {
                        ZoneclearFeedServer::instance()->checkFeedsUpdate();
                    } catch (Exception $e) {
                    }
                };
            });
        } elseif (2 == $s->bhv_pub_upd) {
            dcCore::app()->addBehavior('publicAfterDocumentV2', function (): void {
                try {
                    ZoneclearFeedServer::instance()->checkFeedsUpdate();
                } catch (Exception $e) {
                }
            });
        } elseif (3 == $s->bhv_pub_upd) {
            dcCore::app()->addBehavior('publicHeadContent', function (): void {
                if (is_null(dcCore::app()->blog) || dcCore::app()->url->type != 'default') {
                    return;
                }

                $blog_url = Html::escapeJS(
                    dcCore::app()->blog->url .
                    dcCore::app()->url->getBase('zoneclearFeedsPage') .
                    '/zcfsupd'
                );
                $blog_id = Html::escapeJS(dcCore::app()->blog->id);

                echo
                "\n<!-- JS for zoneclearFeedServer --> \n" .
                dcUtils::jsLoad(dcCore::app()->blog->url . dcCore::app()->url->getBase('zoneclearFeedsPage') . '/zcfsupd.js') .
                "<script type=\"text/javascript\"> \n//<![CDATA[\n" .
                ' $(function(){if(!document.getElementById){return;} ' .
                " $('body').zoneclearFeedServer({blog_url:'" .
                    $blog_url . "',blog_id:'" . $blog_id . "'}); " .
                " })\n" .
                "//]]>\n</script>\n";
            });
        }

        return true;
    }
}
