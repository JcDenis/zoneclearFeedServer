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
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\widgets\WidgetsStack;
use Dotclear\Plugin\widgets\WidgetsElement;

/**
 * Widgets.
 *
 * A widget to list feeds source.
 * A widget to list feeds statistics.
 */
class Widgets
{
    /**
     * @param  WidgetsStack $w WidgetsStack instance
     */
    public static function init(WidgetsStack $w): void
    {
        $w
            ->create(
                'zcfssource',
                __('Feeds server: sources'),
                [self::class, 'publicSource'],
                null,
                __('List sources of feeds')
            )
            ->addTitle(
                __('Feeds sources'),
            )
           ->setting(
               'sortby',
               __('Order by:'),
               'feed_upd_last',
               'combo',
               [
                   __('Last update') => 'feed_upd_last',
                   __('Name')        => 'lowername',
                   __('Create date') => 'feed_creadt',
               ]
           )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc',
                ]
            )
            ->setting(
                'limit',
                __('Limit:'),
                10,
                'text'
            )
            ->setting(
                'pagelink',
                __('Link to the list of sources:'),
                __('All sources'),
                'text'
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();

        $w
            ->create(
                'zcfsnumber',
                __('Feeds server: numbers'),
                [self::class, 'publicNumber'],
                null,
                __('Show some numbers about feeds')
            )
            ->addTitle(
                __('Feeds numbers'),
            )
            ->setting(
                'title',
                __('Title:'),
                __('Feeds numbers'),
                'text'
            )
            ->setting(
                'feed_show',
                __('Show feeds count'),
                1,
                'check'
            )
            ->setting(
                'feed_title',
                __('Title for feeds count:'),
                __('Feeds:'),
                'text'
            )
            ->setting(
                'entry_show',
                __('Show entries count'),
                1,
                'check'
            )
            ->setting(
                'entry_title',
                __('Title for entries count:'),
                __('Entries:'),
                'text'
            )
            ->addHomeOnly()
            ->addContentOnly()
            ->addClass()
            ->addOffline();
    }

    /**
     * Widget for sources list.
     *
     * @param   WidgetsElement  $w  Widgets Element instance
     */
    public static function publicSource(WidgetsElement $w): string
    {
        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        if ($w->__get('offline')
            || !$s->active
            || !$w->checkHomeOnly(dcCore::app()->url->type)
        ) {
            return '';
        }

        $p          = [];
        $p['order'] = ($w->__get('sortby') && in_array($w->__get('sortby'), ['feed_upd_last', 'lowername', 'feed_creadt'])) ?
            $w->__get('sortby') . ' ' : 'feed_upd_last ';
        $p['order'] .= $w->__get('sort') == 'desc' ? 'DESC' : 'ASC';
        $p['limit']       = is_numeric($w->__get('limit')) ? abs((int) $w->__get('limit')) : 10;
        $p['feed_status'] = 1;

        $rs = $z->getFeeds($p);
        if ($rs->isEmpty()) {
            return '';
        }

        $lines = [];
        $i     = 1;
        while ($rs->fetch()) {
            $row     = new FeedRow($rs);
            $lines[] = sprintf(
                '<li><a href="%s" title="%s">%s</a></li>',
                $row->url,
                $row->owner,
                $row->name
            );
            $i++;
        }
        $pub = '';
        if ($w->__get('pagelink') && $s->pub_active) {
            $pub = sprintf(
                '<p><strong><a href="%s">%s</a></strong></p>',
                dcCore::app()->blog?->url . dcCore::app()->url->getBase('zoneclearFeedsPage'),
                Html::escapeHTML(is_string($w->__get('pagelink')) ? $w->__get('pagelink') : '')
            );
        }

        return $w->renderDiv(
            (bool) $w->__get('content_only'),
            'zoneclear-sources ' . $w->__get('class'),
            '',
            ($w->__get('title') ? $w->renderTitle(Html::escapeHTML(is_string($w->__get('title')) ? $w->__get('title') : '')) : '') .
            sprintf('<ul>%s</ul>', implode('', $lines)) . $pub
        );
    }

    /**
     * Widget for feeds info.
     *
     * @param   WidgetsElement  $w  Widgets Element instance
     */
    public static function publicNumber(WidgetsElement $w): string
    {
        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        if ($w->__get('offline')
            || !$s->active
            || !$w->checkHomeOnly(dcCore::app()->url->type)
        ) {
            return '';
        }

        $content = '';

        # Feed
        if ($w->__get('feed_show')) {
            $title = ($w->__get('feed_title') ? sprintf(
                '<strong>%s</strong> ',
                Html::escapeHTML(is_string($w->__get('feed_title')) ? $w->__get('feed_title') : '')
            ) : '');

            $count = $z->getFeeds([], true)->f(0);
            $count = is_numeric($count) ? (int) $count : 0;

            $text = $count ? sprintf(__('one source', '%d sources', $count), $count) : __('no sources');

            if ($s->pub_active) {
                $text = sprintf(
                    '<a href="%s">%s</a>',
                    dcCore::app()->blog?->url . dcCore::app()->url->getBase('zoneclearFeedsPage'),
                    $text
                );
            }

            $content .= sprintf('<li>%s%s</li>', $title, $text);
        }

        # Entry
        if ($w->__get('entry_show')) {
            $count = 0;
            $feeds = $z->getFeeds();

            if (!$feeds->isEmpty()) {
                while ($feeds->fetch()) {
                    $fid = is_numeric($feeds->f('feed_id')) ? (int) $feeds->f('feed_id') : 0;
                    $c   = $z->getPostsByFeed(['feed_id' => $fid], true)->f(0);
                    $c   = is_numeric($c) ? (int) $c : 0;
                    $count += $c;
                }
            }
            $title = ($w->__get('entry_title') ? sprintf(
                '<strong>%s</strong> ',
                Html::escapeHTML(is_string($w->__get('entry_title')) ? $w->__get('entry_title') : '')
            ) : '');

            $text = $count ? sprintf(__('one entry', '%d entries', $count), $count) : __('no entries');

            $content .= sprintf('<li>%s%s</li>', $title, $text);
        }

        if (!$content) {
            return '';
        }

        # Display
        return $w->renderDiv(
            (bool) $w->__get('content_only'),
            'zoneclear-number ' . $w->__get('class'),
            '',
            ($w->__get('title') ? $w->renderTitle(Html::escapeHTML(is_string($w->__get('title')) ? $w->__get('title') : '')) : '') .
            sprintf('<ul>%s</ul>', $content)
        );
    }
}
