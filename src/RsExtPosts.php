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

use context;
use dcCore;
use rsExtPost;
use rsExtPostPublic;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;

/**
 * Posts record extension to integrate feed info.
 */
class RsExtPosts extends rsExtPost
{
    /** @var    array<string,mixed>     $brother_extensions     Stack posts record extensions */
    public static array $brother_extensions = [];

    /**
     * Get feed meta.
     *
     * @param   MetaRecord  $rs     The record instance
     * @param   string      $info   The feed info key
     *
     * @return  null|string     Feed info value
     */
    public static function zcFeed(MetaRecord $rs, string $info): ?string
    {
        $meta = dcCore::app()->meta->getMetadata([
            'post_id'   => $rs->f('post_id'),
            'meta_type' => My::META_PREFIX . $info,
            'limit'     => 1,
        ]);

        return $meta->isEmpty() || !is_string($meta->f('meta_id')) ? null : $meta->f('meta_id');
    }

    /**
     * Call other rs extension.
     *
     * @param   string                      $type   The type of extension
     * @param   array<string|int,mixed>     $args   The arguments
     *
     * @return  string   The record extension ressource result
     */
    public static function zcFeedBrother(string $type, array $args): string
    {
        $ext = static::$brother_extensions;
        if (isset($ext[$type]) && is_callable($ext[$type])) {
            $func = $ext[$type];
        } elseif (is_callable([rsExtPostPublic::class, $type])) {
            $func = [rsExtPostPublic::class, $type];
        } elseif (is_callable([rsExtPost::class, $type])) {
            $func = [rsExtPost::class, $type];
        } else {
            return '';
        }

        $cb = call_user_func_array($func, $args);

        return is_string($cb) ? $cb : '';
    }

    /**
     * Get author link from post to feed.
     *
     * @param   MetaRecord  $rs     The record instance
     * @return  string  The author link
     */
    public static function getAuthorLink(MetaRecord $rs): string
    {
        $author   = $rs->__call('zcFeed', ['author']);
        $site     = $rs->__call('zcFeed', ['site']);
        $sitename = $rs->__call('zcFeed', ['sitename']);

        return is_string($author) && is_string($site) && is_string($sitename) ?
            sprintf('%s (<a href="%s">%s</a>)', $author, $site, $sitename) :
            self::zcFeedBrother('getAuthorLink', [&$rs]);
    }

    /**
     * Get author CN from post to feed.
     *
     * @param   MetaRecord  $rs     The record instance
     *
     * @return  string  The author CN
     */
    public static function getAuthorCN(MetaRecord $rs): string
    {
        $author = $rs->__call('zcFeed', ['author']);

        return is_string($author) ?
            $author :
            self::zcFeedBrother('getAuthorCN', [&$rs]);
    }

    /**
     * Get post link from post to feed.
     *
     * @param   MetaRecord  $rs     The record instance
     *
     * @return  string  The post link
     */
    public static function getURL(MetaRecord $rs): string
    {
        $url  = $rs->__call('zcFeed', ['url']);
        $site = $rs->__call('zcFeed', ['site']);
        $full = in_array(dcCore::app()->url->type, ZoneclearFeedServer::instance()->settings->post_title_redir);

        return is_string($site) && is_string($url) && $full ?
            ZoneclearFeedServer::instance()::absoluteURL($site, $url) :
            self::zcFeedBrother('getURL', [&$rs]);
    }

    /**
     * Get post content from post to feed.
     *
     * @param   MetaRecord  $rs             The record instance
     * @param   mixed       $absolute_urls  Serve absolute URL (type "mixed" from rsExtPost)
     *
     * @return  string  The post content
     */
    public static function getContent(MetaRecord $rs, mixed $absolute_urls = false): string
    {
        $url      = $rs->__call('zcFeed', ['url']);
        $sitename = $rs->__call('zcFeed', ['sitename']);
        $content  = self::zcFeedBrother('getContent', [&$rs, $absolute_urls]);

        if (is_string($url) && is_string($sitename) && $rs->f('post_type') == 'post') {
            if (in_array(dcCore::app()->url->type, ZoneclearFeedServer::instance()->settings->post_full_tpl)) {
                return $content . sprintf(
                    '<p class="zoneclear-original"><em>%s</em></p>',
                    sprintf(__('Original post on <a href="%s">%s</a>'), $url, $sitename)
                );
            }
            $content = context::remove_html($content);
            $content = context::cut_string($content, 350);
            $content = Html::escapeHTML($content);

            return sprintf(
                '<p>%s... <em><a href="%s" title="%s">%s</a></em></p>',
                $content,
                self::getURL($rs),
                __('Read more details about this feed'),
                __('Continue reading')
            );
        }

        return $content;
    }
}
