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
 * @brief Mix your blog with a feeds planet - rs methods.
 * @since 2.6
 */
class zcfsRsExtPosts extends rsExtPost
{
    public static function zc()
    {
        return new zoneclearFeedServer();
    }

    /**
     * Get feed meta.
     *
     * @param  dcRecord $rs   record instance
     * @param  string $info Feed info key
     * @return string       Feed info value
     */
    public static function zcFeed($rs, $info)
    {
        $meta = dcCore::app()->meta->getMetadata([
            'post_id'   => $rs->post_id,
            'meta_type' => 'zoneclearfeed_' . $info,
            'limit'     => 1,
        ]);

        return $meta->isEmpty() ? null : $meta->meta_id;
    }

    /**
     * Call other rs extension.
     *
     * @param  string $type Type of extension
     * @param  array  $args Arguments
     * @return mixed        record extension ressource
     */
    public static function zcFeedBrother($type, $args)
    {
        $ext = dcCore::app()->__get('beforeZcFeedRsExt');
        if (null !== $ext && !empty($ext[$type])) {
            $func = $ext[$type];
        } elseif (is_callable(['rsExtPostPublic', $type])) {
            $func = ['rsExtPostPublic', $type];
        } else {
            $func = ['rsExtPost', $type];
        }

        return call_user_func_array($func, $args);
    }

    /**
     * Get author link from post to feed.
     *
     * @param  dcRecord $rs record instance
     * @return string     Author link
     */
    public static function getAuthorLink(dcRecord $rs): string
    {
        $author   = $rs->zcFeed('author');
        $site     = $rs->zcFeed('site');
        $sitename = $rs->zcFeed('sitename');

        return $author && $sitename ?
            sprintf('%s (<a href="%s">%s</a>)', $author, $site, $sitename) :
            self::zcFeedBrother('getAuthorLink', [&$rs]);
    }

    /**
     * Get author CN from post to feed.
     *
     * @param  dcRecord $rs record instance
     * @return string     Author CN
     */
    public static function getAuthorCN(dcRecord $rs): string
    {
        $author = $rs->zcFeed('author');

        return $author ?
            $author :
            self::zcFeedBrother('getAuthorCN', [&$rs]);
    }

    /**
     * Get post link from post to feed.
     *
     * @param  dcRecord $rs record instance
     * @return string     Post link
     */
    public static function getURL(dcRecord $rs): string
    {
        $url   = $rs->zcFeed('url');
        $types = @unserialize(dcCore::app()->blog->settings->__get(basename(dirname('../' . __DIR__)))->post_title_redir);
        $full  = is_array($types) && in_array(dcCore::app()->url->type, $types);

        return $url && $full ?
            zoneclearFeedServer::absoluteURL($rs->zcFeed('site'), $url) :
            self::zcFeedBrother('getURL', [&$rs]);
    }

    /**
     * Get post content from post to feed.
     *
     * @param  dcRecord $rs record instance
     * @return string     Post content
     */
    public static function getContent(dcRecord $rs, bool $absolute_urls = false): string
    {
        $url      = $rs->zcFeed('url');
        $sitename = $rs->zcFeed('sitename');
        $content  = self::zcFeedBrother('getContent', [&$rs, $absolute_urls]);

        if ($url && $sitename && $rs->post_type == 'post') {
            $types = @unserialize(dcCore::app()->blog->settings->__get(basename(dirname('../' . __DIR__)))->post_full_tpl);

            if (is_array($types) && in_array(dcCore::app()->url->type, $types)) {
                return $content . sprintf(
                    '<p class="zoneclear-original"><em>%s</em></p>',
                    sprintf(__('Original post on <a href="%s">%s</a>'), $url, $sitename)
                );
            }
            $content = context::remove_html($content);
            $content = context::cut_string($content, 350);
            $content = html::escapeHTML($content);

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
