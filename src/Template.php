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

use ArrayObject;
use dcCore;
use dcTemplate;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;

/**
 * Frontend template blocks and values.
 */
class Template
{
    public static function Feeds(ArrayObject $a, string $c): string
    {
        $lastn = -1;
        $p     = '';
        if (isset($a['lastn']) && is_numeric($a['lastn'])) {
            $lastn = abs((int) $a['lastn']) + 0;
            $p .= "\$zcfs_params['limit'] = " . $lastn . ";\n";
        }
        if (isset($a['cat_id']) && is_string($a['cat_id'])) {
            $p .= "@\$zcfs_params['sql'] .= 'AND Z.cat_id = " . addslashes($a['cat_id']) . " ';\n";
        }
        if (isset($a['no_category'])) {
            $p .= "@\$zcfs_params['sql'] .= 'AND Z.cat_id IS NULL ';\n";
        }
        if (!empty($a['site_url']) && is_string($a['site_url'])) {
            $p .= "\$zcfs_params['feed_url'] = '" . addslashes($a['site_url']) . "';\n";
        }
        if (isset($a['feed_status']) && is_numeric($a['feed_status'])) {
            $p .= "\$zcfs_params['feed_status'] = " . ((int) $a['feed_status']) . ";\n";
        } else {
            $p .= "\$zcfs_params['feed_status'] = 1;\n";
        }
        if (!empty($a['feed_url']) && is_string($a['feed_url'])) {
            $p .= "\$zcfs_params['feed_feed'] = '" . addslashes($a['feed_url']) . "';\n";
        }
        if (isset($a['feed_owner']) && is_string($a['feed_owner'])) {
            $p .= "@\$zcfs_params['sql'] .= \"AND Z.feed_owner = '" . addslashes($a['feed_owner']) . "' \";\n";
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
        if (isset($a['order']) && is_string($a['order']) && preg_match('/^(desc|asc)$/i', $a['order'])) {
            $order = $a['order'];
        }
        $p .= "\$zcfs_params['order'] = '" . $sortby . ' ' . $order . "';\n";

        return
        '<?php ' . $p .
        'dcCore::app()->ctx->feeds_params = $zcfs_params;' . "\n" .
        '$zcfs = ' . ZoneclearFeedServer::class . '::instance();' . "\n" .
        'dcCore::app()->ctx->feeds = $zcfs->getFeeds($zcfs_params); unset($zcfs_params,$zcfs);' . "\n" .
        "?>\n" .
        '<?php while (dcCore::app()->ctx->feeds->fetch()) : ?>' . $c . '<?php endwhile; ' .
        'dcCore::app()->ctx->feeds = null; dcCore::app()->ctx->feeds_params = null; ?>';
    }

    public static function FeedIf(ArrayObject $a, string $c): string
    {
        $if = [];

        $operator = isset($a['operator']) && is_string($a['operator']) ? dcTemplate::getOperator($a['operator']) : '&&';

        if (isset($a['type']) && is_string($a['type'])) {
            $type = trim($a['type']);
            $type = !empty($type) ? $type : 'feed';
            $if[] = 'dcCore::app()->ctx->feeds->feed_type == "' . addslashes($type) . '"';
        }
        if (isset($a['site_url']) && is_string($a['site_url'])) {
            $url = trim($a['site_url']);
            if (substr($url, 0, 1) == '!') {
                $url  = substr($url, 1);
                $if[] = 'dcCore::app()->ctx->feeds->feed_url != "' . addslashes($url) . '"';
            } else {
                $if[] = 'dcCore::app()->ctx->feeds->feed_url == "' . addslashes($url) . '"';
            }
        }
        if (isset($a['feed_url']) && is_string($a['feed_url'])) {
            $url = trim($a['feed_url']);
            if (substr($url, 0, 1) == '!') {
                $url  = substr($url, 1);
                $if[] = 'dcCore::app()->ctx->feeds->feed_feed != "' . addslashes($url) . '"';
            } else {
                $if[] = 'dcCore::app()->ctx->feeds->feed_feed == "' . addslashes($url) . '"';
            }
        }
        if (isset($a['category']) && is_string($a['category'])) {
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

    public static function FeedIfFirst(ArrayObject $a): string
    {
        $ret = Html::escapeHTML(isset($a['return']) && is_string($a['return']) ? $a['return'] : 'first');

        return
        '<?php if (dcCore::app()->ctx->feeds->index() == 0) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    public static function FeedIfOdd(ArrayObject $a): string
    {
        $ret = Html::escapeHTML(isset($a['return']) && is_string($a['return']) ? $a['return'] : 'odd');

        return
        '<?php if ((dcCore::app()->ctx->feeds->index()+1)%2 == 1) { ' .
        "echo '" . addslashes($ret) . "'; } ?>";
    }

    public static function FeedDesc(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_desc');
    }

    public static function FeedOwner(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_owner');
    }

    public static function FeedCategory(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->cat_title');
    }

    public static function FeedCategoryID(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->cat_id');
    }

    public static function FeedCategoryURL(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->blog->url.dcCore::app()->url->getBase(\'category\').\'/\'.Html::sanitizeURL(dcCore::app()->ctx->feeds->cat_url)');
    }

    public static function FeedCategoryShortURL(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->cat_url');
    }

    public static function FeedID(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_id');
    }

    public static function FeedLang(ArrayObject $a): string
    {
        return empty($a['full']) ?
            self::getValue($a, 'dcCore::app()->ctx->feeds->feed_lang') :
            '<?php $langs = ' . L10n::class . '::getISOcodes(); if (isset($langs[dcCore::app()->ctx->feeds->feed_lang])) { ?>' .
            self::getValue($a, '$langs[dcCore::app()->ctx->feeds->feed_lang]') .
            '<?php } else { ?>' .
            self::getValue($a, 'dcCore::app()->ctx->feeds->feed_lang') .
            '<?php ; } unset($langs); ?>';
    }

    public static function FeedName(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_name');
    }

    public static function FeedSiteURL(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_url');
    }

    public static function FeedFeedURL(ArrayObject $a): string
    {
        return self::getValue($a, 'dcCore::app()->ctx->feeds->feed_feed');
    }

    public static function FeedsHeader(ArrayObject $a, string $c): string
    {
        return '<?php if (dcCore::app()->ctx->feeds->isStart()) : ?>' . $c . '<?php endif; ?>';
    }

    public static function FeedsFooter(ArrayObject $a, string $c): string
    {
        return '<?php if (dcCore::app()->ctx->feeds->isEnd()) : ?>' . $c . '<?php endif; ?>';
    }

    public static function FeedsCount(ArrayObject $a): string
    {
        $none = isset($a['none']) && is_string($a['none']) ? addslashes($a['none']) : 'no sources';
        $one  = isset($a['one'])  && is_string($a['one']) ? addslashes($a['one']) : 'one source';
        $more = isset($a['more']) && is_string($a['more']) ? addslashes($a['more']) : '%d sources';

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

    public static function FeedsEntriesCount(ArrayObject $a): string
    {
        $none = isset($a['none']) && is_string($a['none']) ? addslashes($a['none']) : 'no entries';
        $one  = isset($a['one'])  && is_string($a['one']) ? addslashes($a['one']) : 'one entry';
        $more = isset($a['more']) && is_string($a['more']) ? addslashes($a['more']) : '%d entries';

        return
        "<?php \$fcount = 0; \n" .
        '$zc = ' . ZoneclearFeedServer::class . '::instance();' . "\n" .
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

    public static function FeedEntriesCount(ArrayObject $a): string
    {
        $none = isset($a['none']) && is_string($a['none']) ? addslashes($a['none']) : 'no entries';
        $one  = isset($a['one'])  && is_string($a['one']) ? addslashes($a['one']) : 'one entry';
        $more = isset($a['more']) && is_string($a['more']) ? addslashes($a['more']) : '%d entries';

        return
        '<?php $zcfs = ' . ZoneclearFeedServer::class . "::instance(); \n" .
        "\$fcount = \$zc->getPostsByFeed(array('feed_id'=>dcCore::app()->ctx->feeds->feed_id),true)->f(0); \n" .
        "if (\$fcount == 0) {\n" .
        "  printf(__('" . $none . "'),\$fcount);\n" .
        "} elseif (\$fcount == 1) {\n" .
        "  printf(__('" . $one . "'),\$fcount);\n" .
        "} else {\n" .
        "  printf(__('" . $more . "'),\$fcount);\n" .
        '} unset($fcount); ?>';
    }

    protected static function getValue(ArrayObject $a, string $v): string
    {
        return '<?php echo ' . sprintf(dcCore::app()->tpl->getFilters($a), $v) . '; ?>';
    }
}
