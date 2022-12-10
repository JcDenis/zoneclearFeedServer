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
