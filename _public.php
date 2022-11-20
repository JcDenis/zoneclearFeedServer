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

# Namespace for settings
dcCore::app()->blog->settings->addNamespace('zoneclearFeedServer');
$s = dcCore::app()->blog->settings->zoneclearFeedServer;

# Widgets
require_once __DIR__ . '/_widgets.php';

dcCore::app()->addBehavior('coreBlogGetPosts', ['zcfsPublicBehaviors', 'coreBlogGetPosts']);

if (!$s->zoneclearFeedServer_active) {
    return null;
}
if (1 == $s->zoneclearFeedServer_bhv_pub_upd) {
    dcCore::app()->addBehavior('publicBeforeDocument', ['zcfsPublicBehaviors', 'publicDocument']);
} elseif (2 == $s->zoneclearFeedServer_bhv_pub_upd) {
    dcCore::app()->addBehavior('publicAfterDocument', ['zcfsPublicBehaviors', 'publicAfterDocument']);
} elseif (3 == $s->zoneclearFeedServer_bhv_pub_upd) {
    dcCore::app()->addBehavior('publicHeadContent', ['zcfsPublicBehaviors', 'publicHeadContent']);
}

# Take care about tweakurls (thanks Mathieu M.)
if (version_compare(dcCore::app()->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {
    dcCore::app()->addbehavior('zoneclearFeedServerAfterPostCreate', ['zoneclearFeedServer', 'tweakurlsAfterPostCreate']);
}

# Register tempalte blocks
$tpl_blocks = [
    'Feeds',
    'FeedsFooter',
    'FeedsHeader',
    'FeedIf',
];
foreach ($tpl_blocks as $v) {
    dcCore::app()->tpl->addBlock('zc' . $v, ['zcfsTemplate', $v]);
}

# Register tempalte values
$tpl_values = [
    'FeedsCount',
    'FeedsEntriesCount',
    'FeedEntriesCount',
    'FeedCategory',
    'FeedCategoryID',
    'FeedCategoryURL',
    'FeedCategoryShortURL',
    'FeedID',
    'FeedIfFirst',
    'FeedIfOdd',
    'FeedLang',
    'FeedName',
    'FeedOwner',
    'FeedDesc',
    'FeedSiteURL',
    'FeedFeedURL',
];
foreach ($tpl_values as $v) {
    dcCore::app()->tpl->addValue('zc' . $v, ['zcfsTemplate', $v]);
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - public methods.
 * @since 2.6
 */
class zcfsPublicBehaviors
{
    /**
     * Remember others post extension.
     *
     * @param  dcRecord $rs record instance
     */
    public static function coreBlogGetPosts(dcRecord $rs)
    {
        $GLOBALS['beforeZcFeedRsExt'] = $rs->extensions();
        $rs->extend('zcfsRsExtPosts');
    }

    /**
     * Update feeds after contents.
     */
    public static function publicAfterDocument()
    {
        # Limit feeds update to home page et feed page
        # Like publishScheduledEntries
        if (!in_array(dcCore::app()->url->type, ['default', 'feed'])) {
            return null;
        }

        self::publicDocument();
    }

    /**
     * Generic behavior for before and after public content.
     */
    public static function publicDocument()
    {
        $zc = new zoneclearFeedServer();
        $zc->checkFeedsUpdate();

        return null;
    }

    /**
     * Update feeds by an Ajax request (background).
     */
    public static function publicHeadContent()
    {
        # Limit update to home page
        if (dcCore::app()->url->type != 'default') {
            return null;
        }

        $blog_url = html::escapeJS(
            dcCore::app()->blog->url .
            dcCore::app()->url->getBase('zoneclearFeedsPage') .
            '/zcfsupd'
        );
        $blog_id = html::escapeJS(dcCore::app()->blog->id);

        echo
        "\n<!-- JS for zoneclearFeedServer --> \n" .
        dcUtils::jsLoad(dcCore::app()->blog->url . dcCore::app()->url->getBase('zoneclearFeedsPage') . '/zcfsupd.js') .
        "<script type=\"text/javascript\"> \n//<![CDATA[\n" .
        ' $(function(){if(!document.getElementById){return;} ' .
        " $('body').zoneclearFeedServer({blog_url:'" .
            $blog_url . "',blog_id:'" . $blog_id . "'}); " .
        " })\n" .
        "//]]>\n</script>\n";
    }
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
        return new zonclearFeedServer();
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
        if (!empty($GLOBALS['beforeZcFeedRsExt'][$type])) {
            $func = $GLOBALS['beforeZcFeedRsExt'][$type];
        } elseif (is_callable('rsExtPostPublic', $type)) {
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
        $types = @unserialize(dcCore::app()->blog->settings->zoneclearFeedServer->zoneclearFeedServer_post_title_redir);
        $full  = is_array($types) && in_array(dcCore::app()->url->type, $types);

        return $url && $full ?
            zoneclearFeedServer::absoluteURL($rs->zcFeed('site'), $url) :
            self::zcFeedBrother('getURL', [&$rs]);
    }

    /**
     * Get post content from post to feed.
     *
     * @param  record $rs record instance
     * @return string     Post content
     */
    public static function getContent(dcRecord $rs, bool $absolute_urls = false): string
    {
        $url      = $rs->zcFeed('url');
        $sitename = $rs->zcFeed('sitename');
        $content  = self::zcFeedBrother('getContent', [&$rs, $absolute_urls]);

        if ($url && $sitename && $rs->post_type == 'post') {
            $types = @unserialize(dcCore::app()->blog->settings->zoneclearFeedServer->zoneclearFeedServer_post_full_tpl);

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
        $s = dcCore::app()->blog->settings->zoneclearFeedServer;

        # Not active
        if (!$s->zoneclearFeedServer_active) {
            self::p404();

            return null;
        }

        # Update feeds (from ajax or other post resquest)
        if ($args == '/zcfsupd' && 3 == $s->zoneclearFeedServer_bhv_pub_upd) {
            $msg = '';
            if (!empty($_POST['blogId']) && html::escapeJS(dcCore::app()->blog->id) == $_POST['blogId']) {
                try {
                    $zc = new zoneclearFeedServer();
                    if ($zc->checkFeedsUpdate()) {
                        $msg = sprintf(
                            '<status>%s</status><message>s%</message>',
                            'ok',
                            'Feeds updated successfully'
                        );
                    }
                } catch (Exception $e) {
                }
            }
            if (empty($msg)) {
                $msg = sprintf(
                    '<status>%s</status><message>s%</message>',
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
        } elseif ($args == '/zcfsupd.js' && 3 == $s->zoneclearFeedServer_bhv_pub_upd) {
            dcCore::app()->tpl->setPath(dcCore::app()->tpl->getPath(), __DIR__ . '/default-templates');
            self::serveDocument(
                'zcfsupd.js',
                'text/javascript',
                false,
                false
            );

        # Server feeds description page
        } elseif (in_array($args, ['', '/']) && $s->zoneclearFeedServer_pub_active) {
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

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - template methods.
 * @since 2.6
 */
class zcfsTemplate
{
    public static function Feeds($a, $c)
    {
        $lastn = -1;
        $p     = '';
        if (isset($a['lastn'])) {
            $lastn = abs((int) $a['lastn']) + 0;
            $p .= "\$zcfs_params['limit'] = " . $lastn . ";\n";
        }
        if (isset($a['cat_id'])) {
            $p .= "@\$zcfs_params['sql'] .= 'AND Z.cat_id = " . addslashes($a['cat_id']) . " ';\n";
        }
        if (isset($a['no_category'])) {
            $p .= "@\$zcfs_params['sql'] .= 'AND Z.cat_id IS NULL ';\n";
        }
        if (!empty($a['site_url'])) {
            $p .= "\$zcfs_params['feed_url'] = '" . addslashes($a['site_url']) . "';\n";
        }
        if (isset($a['feed_status'])) {
            $p .= "\$zcfs_params['feed_status'] = " . ((int) $a['feed_status']) . ";\n";
        } else {
            $p .= "\$zcfs_params['feed_status'] = 1;\n";
        }
        if (!empty($a['feed_url'])) {
            $p .= "\$zcfs_params['feed_feed'] = '" . addslashes($a['feed_url']) . "';\n";
        }
        if (isset($a['feed_owner'])) {
            $p .= "@\$zcfs_params['sql'] .= \"AND Z.feed_owner = '" . addslashes($a['author']) . "' \";\n";
        }

        $sortby = 'feed_creadt';
        $order  = 'desc';
        if (isset($a['sortby'])) {
            switch ($a['sortby']) {
                case 'name':    $sortby = 'lowername';

                    break;
                case 'owner':  $sortby = 'feed_owner';

                    break;
                case 'date':   $sortby = 'feed_dt';

                    break;
                case 'update': $sortby = 'feed_upddt';

                    break;
                case 'id':     $sortby = 'feed_id';

                    break;
            }
        }
        if (isset($a['order']) && preg_match('/^(desc|asc)$/i', $a['order'])) {
            $order = $a['order'];
        }
        $p .= "\$zcfs_params['order'] = '" . $sortby . ' ' . $order . "';\n";

        return
        '<?php ' . $p .
        'dcCore::app()->ctx->feeds_params = $zcfs_params;' . "\n" .
        '$zcfs = new zoneclearFeedServer();' . "\n" .
        'dcCore::app()->ctx->feeds = $zcfs->getFeeds($zcfs_params); unset($zcfs_params,$zcfs);' . "\n" .
        "?>\n" .
        '<?php while (dcCore::app()->ctx->feeds->fetch()) : ?>' . $c . '<?php endwhile; ' .
        'dcCore::app()->ctx->feeds = null; dcCore::app()->ctx->feeds_params = null; ?>';
    }

    public static function FeedIf($a, $c)
    {
        $if = [];

        $operator = isset($a['operator']) ? self::getOperator($a['operator']) : '&&';

        if (isset($a['type'])) {
            $type = trim($a['type']);
            $type = !empty($type) ? $type : 'feed';
            $if[] = 'dcCore::app()->ctx->feeds->feed_type == "' . addslashes($type) . '"';
        }
        if (isset($a['site_url'])) {
            $url = trim($a['feed_url']);
            if (substr($url, 0, 1) == '!') {
                $url  = substr($url, 1);
                $if[] = 'dcCore::app()->ctx->feeds->feed_url != "' . addslashes($url) . '"';
            } else {
                $if[] = 'dcCore::app()->ctx->feeds->feed_url == "' . addslashes($url) . '"';
            }
        }
        if (isset($a['feed_url'])) {
            $url = trim($a['feed_feed']);
            if (substr($url, 0, 1) == '!') {
                $url  = substr($url, 1);
                $if[] = 'dcCore::app()->ctx->feeds->feed_feed != "' . addslashes($url) . '"';
            } else {
                $if[] = 'dcCore::app()->ctx->feeds->feed_feed == "' . addslashes($url) . '"';
            }
        }
        if (isset($a['category'])) {
            $category = addslashes(trim($a['category']));
            if (substr($category, 0, 1) == '!') {
                $category = substr($category, 1);
                $if[]     = '(dcCore::app()->ctx->feeds->cat_url != "' . $category . '")';
            } else {
                $if[] = '(dcCore::app()->ctx->feeds->cat_url == "' . $category . '")';
            }
        }
        if (isset($a['first'])) {
            $sign = (bool) $a['first'] ? '=' : '!';
            $if[] = 'dcCore::app()->ctx->feeds->index() ' . $sign . '= 0';
        }
        if (isset($a['odd'])) {
            $sign = (bool) $a['odd'] ? '=' : '!';
            $if[] = '(dcCore::app()->ctx->feeds->index()+1)%2 ' . $sign . ' = 1';
        }
        if (isset($a['has_category'])) {
            $sign = (bool) $a['has_category'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->ctx->feeds->cat_id';
        }
        if (isset($a['has_description'])) {
            $sign = (bool) $a['has_description'] ? '' : '!';
            $if[] = $sign . 'dcCore::app()->ctx->feeds->feed_desc';
        }

        return empty($if) ?
            $c :
            '<?php if(' . implode(' ' . $operator . ' ', $if) . ') : ?>' . $c . '<?php endif; ?>';
    }

    public static function FeedIfFirst($a)
    {
        $ret = $a['return'] ?? 'first';
        $ret = html::escapeHTML($ret);

        return
        '<?php if (dcCore::app()->ctx->feeds->index() == 0) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    public static function FeedIfOdd($a)
    {
        $ret = $a['return'] ?? 'odd';
        $ret = html::escapeHTML($ret);

        return
        '<?php if ((dcCore::app()->ctx->feeds->index()+1)%2 == 1) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    public static function FeedDesc($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_desc');
    }

    public static function FeedOwner($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_owner');
    }

    public static function FeedCategory($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->cat_title');
    }

    public static function FeedCategoryID($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->cat_id');
    }

    public static function FeedCategoryURL($a)
    {
        return self::getValue($a, 'dcCore::app()->blog->url.dcCore::app()->url->getBase(\'category\').\'/\'.html::sanitizeURL(dcCore::app()->ctx->feeds->cat_url)');
    }

    public static function FeedCategoryShortURL($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->cat_url');
    }

    public static function FeedID($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_id');
    }

    public static function FeedLang($a)
    {
        $f = dcCore::app()->tpl->getFilters($a);

        return empty($a['full']) ?
            '<?php echo ' . sprintf($f, 'dcCore::app()->ctx->feeds->feed_lang') . '; ?>' :
            '<?php $langs = l10n::getISOcodes(); if (isset($langs[dcCore::app()->ctx->feeds->feed_lang])) { echo ' .
                sprintf($f, '$langs[dcCore::app()->ctx->feeds->feed_lang]') . '; } else { echo ' .
                sprintf($f, 'dcCore::app()->ctx->feeds->feed_lang') . '; } unset($langs); ?>';
    }

    public static function FeedName($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_name');
    }

    public static function FeedSiteURL($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_url');
    }

    public static function FeedFeedURL($a)
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_feed');
    }

    public static function FeedsHeader($a, $c)
    {
        return '<?php if (dcCore::app()->ctx->feeds->isStart()) : ?>' . $c . '<?php endif; ?>';
    }

    public static function FeedsFooter($a, $c)
    {
        return '<?php if (dcCore::app()->ctx->feeds->isEnd()) : ?>' . $c . '<?php endif; ?>';
    }

    public static function FeedsCount($a)
    {
        $none = 'no sources';
        $one  = 'one source';
        $more = '%d sources';

        if (isset($a['none'])) {
            $none = addslashes($a['none']);
        }
        if (isset($a['one'])) {
            $one = addslashes($a['one']);
        }
        if (isset($a['more'])) {
            $more = addslashes($a['more']);
        }

        return
        "<?php \$fcount = dcCore::app()->ctx->feeds->count(); \n" .
        "if (\$fcount == 0) {\n" .
        "  printf(__('" . $none . "'),\$fcount);\n" .
        "} elseif (\$fcount == 1) {\n" .
        "  printf(__('" . $one . "'),\$fcount);\n" .
        "} else {\n" .
        "  printf(__('" . $more . "'),\$fcount);\n" .
        '} unset($fcount); ?>';
    }

    public static function FeedsEntriesCount($a)
    {
        $none = __('no entries');
        $one  = __('one entry');
        $more = __('%d entries');

        if (isset($a['none'])) {
            $none = addslashes($a['none']);
        }
        if (isset($a['one'])) {
            $one = addslashes($a['one']);
        }
        if (isset($a['more'])) {
            $more = addslashes($a['more']);
        }

        return
        "<?php \$fcount = 0; \n" .
        '$zc = new zoneclearFeedServer();' . "\n" .
        "\$allfeeds = \$zc->getFeeds(); \n" .
        "if (!\$allfeeds->isEmpty()) { \n" .
        ' while ($allfeeds->fetch()) { ' .
        "  \$fcount += (integer) \$zc->getPostsByFeed(array('feed_id'=>\$allfeeds->feed_id),true)->f(0); " .
        " } \n" .
        "} \n" .
        "if (\$fcount == 0) {\n" .
        "  printf(__('" . $none . "'),\$fcount);\n" .
        "} elseif (\$fcount == 1) {\n" .
        "  printf(__('" . $one . "'),\$fcount);\n" .
        "} else {\n" .
        "  printf(__('" . $more . "'),\$fcount);\n" .
        '} unset($allfeeds,$fcount); ?>';
    }

    public static function FeedEntriesCount($a)
    {
        $none = 'no entries';
        $one  = 'one entry';
        $more = '%d entries';

        if (isset($a['none'])) {
            $none = addslashes($a['none']);
        }
        if (isset($a['one'])) {
            $one = addslashes($a['one']);
        }
        if (isset($a['more'])) {
            $more = addslashes($a['more']);
        }

        return
        "<?php \$zcfs = new zoneclearFeedServer(); \n" .
        "\$fcount = \$zc->getPostsByFeed(array('feed_id'=>dcCore::app()->ctx->feeds->feed_id),true)->f(0); \n" .
        "if (\$fcount == 0) {\n" .
        "  printf(__('" . $none . "'),\$fcount);\n" .
        "} elseif (\$fcount == 1) {\n" .
        "  printf(__('" . $one . "'),\$fcount);\n" .
        "} else {\n" .
        "  printf(__('" . $more . "'),\$fcount);\n" .
        '} unset($fcount); ?>';
    }

    protected static function getValue($a, $v)
    {
        return '<?php echo ' . sprintf(dcCore::app()->tpl->getFilters($a), $v) . '; ?>';
    }

    protected static function getOperator($op)
    {
        switch (strtolower($op)) {
            case 'or':
            case '||':
                return '||';
            case 'and':
            case '&&':
            default:
                return '&&';
        }
    }
}

dcCore::app()->addBehavior('publicBreadcrumb', ['extZcfeeds', 'publicBreadcrumb']);

class extZcfeeds
{
    public static function publicBreadcrumb($context, $separator)
    {
        if ($context == 'zoneclearFeedsPage') {
            return __('List of feeds');
        }
    }
}
