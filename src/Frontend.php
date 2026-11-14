<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       zoneclearFeedServer frontend class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Frontend
{
    use TraitProcess;

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

        App::behavior()->addBehaviors([
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
            'initWidgets' => Widgets::init(...),
        ]);

        // Register template blocks
        foreach (My::TPL_BLOCKS as $block) {
            App::frontend()->template()->addBlock('zc' . $block, [Template::class, $block]);
        }

        // Register template values
        foreach (My::TPL_VALUES as $value) {
            App::frontend()->template()->addValue('zc' . $value, [Template::class, $value]);
        }

        // module not active
        if (!$s->active) {
            return true;
        }

        // feeds update methods
        if (1 == $s->bhv_pub_upd) {
            App::behavior()->addBehavior('publicBeforeDocumentV2', function (): void {
                if (App::url()->isType(['default', 'feed'])) {
                    try {
                        ZoneclearFeedServer::instance()->checkFeedsUpdate();
                    } catch (Exception $e) {
                    }
                };
            });
        } elseif (2 == $s->bhv_pub_upd) {
            App::behavior()->addBehavior('publicAfterDocumentV2', function (): void {
                try {
                    ZoneclearFeedServer::instance()->checkFeedsUpdate();
                } catch (Exception $e) {
                }
            });
        } elseif (3 == $s->bhv_pub_upd) {
            App::behavior()->addBehavior('publicHeadContent', function (): void {
                if (!App::blog()->isDefined() || !App::url()->isType(['default', 'default-page', 'static'])) {
                    return;
                }

                // Background request, without waiting for response
                echo
                "\n<!-- JS for zoneclearFeedServer -->\n" .
                '<script>' .
                "window.addEventListener('load',()=>{" .
                'fetch(' . json_encode(App::blog()->url() . App::url()->getBase('zoneclearFeedsPage') . '/zcfsupd', JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) . ',' .
                "{method:'POST',keepalive:true,body:new URLSearchParams({blogId:" . json_encode(App::blog()->id(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR) . '})})' .
                '.catch(()=>{});' .
                '});' .
                "</script>\n";
            });
        }

        return true;
    }
}
