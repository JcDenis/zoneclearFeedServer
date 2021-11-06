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

$core->addBehavior(
    'initWidgets',
    ['zoneclearFeedServerWidget', 'adminSource']
);
$core->addBehavior(
    'initWidgets',
    ['zoneclearFeedServerWidget', 'adminNumber']
);

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - widgets methods.
 * @since 2.6
 */
class zoneclearFeedServerWidget
{
    /**
     * Widget configuration for sources list.
     *
     * @param  dcWidget $w dcWidget instance
     */
    public static function adminSource($w)
    {
        $w
            ->create(
                'zcfssource',
                __('Feeds server: sources'),
                ['zoneclearFeedServerWidget', 'publicSource'],
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
                   __('Create date') => 'feed_creadt'
               ]
           )
            ->setting(
                'sort',
                __('Sort:'),
                'desc',
                'combo',
                [
                    __('Ascending')  => 'asc',
                    __('Descending') => 'desc'
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
    }

    /**
     * Widget configuration for feeds info.
     *
     * @param  dcWidget $w dcWidget instance
     */
    public static function adminNumber($w)
    {
        $w
            ->create(
                'zcfsnumber',
                __('Feeds server: numbers'),
                ['zoneclearFeedServerWidget', 'publicNumber'],
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
     * @param  dcWidget $w dcWidget instance
     */
    public static function publicSource($w)
    {
        global $core;

        if ($w->offline) {
            return null;
        }

        if (!$core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_active
            || $w->homeonly == 1 && !$core->url->isHome($core->url->type)
            || $w->homeonly == 2 && $core->url->isHome($core->url->type)
        ) {
            return null;
        }

        $p          = [];
        $p['order'] = ($w->sortby && in_array($w->sortby, ['feed_upd_last', 'lowername', 'feed_creadt'])) ?
            $w->sortby . ' ' : 'feed_upd_last ';
        $p['order'] .= $w->sort == 'desc' ? 'DESC' : 'ASC';
        $p['limit']       = abs((int) $w->limit);
        $p['feed_status'] = 1;

        $zc = new zoneclearFeedServer($core);
        $rs = $zc->getFeeds($p);

        if ($rs->isEmpty()) {
            return null;
        }

        $lines = [];
        $i     = 1;
        while ($rs->fetch()) {
            $lines[] = sprintf(
                '<li><a href="%s" title="%s">%s</a></li>',
                $rs->feed_url,
                $rs->feed_owner,
                $rs->feed_name
            );
            $i++;
        }
        $pub = '';
        if ($w->pagelink && $core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_pub_active) {
            $pub = sprintf(
                '<p><strong><a href="%s">%s</a></strong></p>',
                $core->blog->url . $core->url->getBase('zoneclearFeedsPage'),
                html::escapeHTML($w->pagelink)
            );
        }

        return $w->renderDiv(
            $w->content_only,
            'zoneclear-sources ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            sprintf('<ul>%s</ul>', implode('', $lines)) . $pub
        );
    }

    /**
     * Widget for feeds info.
     *
     * @param  dcWidget $w dcWidget instance
     */
    public static function publicNumber($w)
    {
        global $core;

        if ($w->offline) {
            return;
        }

        if (!$core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_active
            || $w->homeonly == 1 && !$core->url->isHome($core->url->type)
            || $w->homeonly == 2 && $core->url->isHome($core->url->type)
        ) {
            return null;
        }

        $zc      = new zoneclearFeedServer($core);
        $content = '';

        # Feed
        if ($w->feed_show) {
            $title = ($w->feed_title ? sprintf(
                '<strong>%s</strong> ',
                html::escapeHTML($w->feed_title)
            ) : '');

            $count = $zc->getFeeds([], true)->f(0);

            $text = $count ? sprintf(__('one source', '%d sources', $count), $count) : __('no sources');

            if ($core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_pub_active) {
                $text = sprintf(
                    '<a href="%s">%s</a>',
                    $core->blog->url . $core->url->getBase('zoneclearFeedsPage'),
                    $text
                );
            }

            $content .= sprintf('<li>%s%s</li>', $title, $text);
        }

        # Entry
        if ($w->entry_show) {
            $count = 0;
            $feeds = $zc->getFeeds();

            if (!$feeds->isEmpty()) {
                while ($feeds->fetch()) {
                    $count += (int) $zc->getPostsByFeed(['feed_id' => $feeds->feed_id], true)->f(0);
                }
            }
            $title = ($w->entry_title ? sprintf(
                '<strong>%s</strong> ',
                html::escapeHTML($w->entry_title)
            ) : '');

            $text = $count ? sprintf(__('one entry', '%d entries', $count), $count) : __('no entries');

            $content .= sprintf('<li>%s%s</li>', $title, $text);
        }

        if (!$content) {
            return null;
        }

        # Display
        return $w->renderDiv(
            $w->content_only,
            'zoneclear-number ' . $w->class,
            '',
            ($w->title ? $w->renderTitle(html::escapeHTML($w->title)) : '') .
            sprintf('<ul>%s</ul>', $content)
        );
    }
}
