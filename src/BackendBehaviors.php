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
use dcPage;
use dcFavorites;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Link,
    Para,
    Text
};
use Dotclear\Helper\Html\Html;

/**
 * Backend behaviors.
 */
class BackendBehaviors
{
    /**
     * User dashboard favorites icon.
     */
    public static function adminDashboardFavoritesV2(dcFavorites $favs): void
    {
        // nullsafe
        if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->adminurl)) {
            return;
        }

        $favs->register(My::id(), [
            'title'       => My::name(),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
            'small-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
            'large-icon'  => dcPage::getPF(My::id() . '/icon.svg'),
            'permissions' => dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_USAGE,
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]),
            // update user dashboard favorites icon with nb of updated feeds
            'dashboard_cb' => function (ArrayObject $fav): void {
                if (is_null(dcCore::app()->adminurl)) {
                    return;
                }

                $count = ZoneclearFeedServer::instance()->getFeeds(['feed_status' => '0'], true)->f(0);
                if (!$count || !is_numeric($count)) {
                    return;
                }

                $fav['title'] .= '<br />' . sprintf(__('%s feed disabled', '%s feeds disabled', (int) $count), (int) $count);
                $fav['large-icon'] = dcPage::getPF(My::id() . '/icon-update.svg');
                $fav['url']        = dcCore::app()->adminurl->get(
                    'admin.plugin.' . My::id(),
                    ['part' => 'feeds', 'sortby' => 'feed_status', 'order' => 'asc']
                );
            },
        ]);
    }

    /**
     * Lists columns user preference.
     */
    public static function adminColumnsListsV2(ArrayObject $cols): void
    {
        // feeds
        $cols[My::id() . 'feeds'] = [
            __('Feeds server: Feeds'),
            [
                'desc'    => [true, __('Feed')],
                'period'  => [true, __('Frequency')],
                'update'  => [true, __('Last update')],
                'entries' => [true, __('Entries')],
            ],
        ];
        // feed posts
        $cols[My::id() . 'posts'] = [
            __('Feeds server: Entries'),
            [
                'date'     => [true, __('Date')],
                'category' => [true, __('Category')],
                'author'   => [true, __('Author')],
            ],
        ];
        // posts feed
        $cols['posts'][1]['feed'] = [true, __('Feed server')];
    }

    /**
     * Lists filter.
     */
    public static function adminFiltersListsV2(ArrayObject $sorts): void
    {
        // feeds
        $sorts[My::id() . 'feeds'] = [
            __('Feeds server: Feeds'),
            Combo::feedsSortby(),
            'lowername',
            'asc',
            [__('feeds per page'), 30],
        ];
        // feed posts
        $sorts[My::id() . 'posts'] = [
            __('Feeds server: Entries'),
            Combo::postsSortby(),
            'post_dt',
            'desc',
            [__('entries per page'), 30],
        ];
    }

    /**
     * Add head column to posts list.
     */
    public static function adminPostListHeaderV2(MetaRecord $rs, ArrayObject $cols): void
    {
        $cols['feed'] = '<th scope="col">' . __('Feed') . '</th>';
    }

    /**
     * Add body column to posts list.
     */
    public static function adminPostListValueV2(MetaRecord $rs, ArrayObject $cols): void
    {
        $rs_meta = dcCore::app()->meta->getMetadata(['post_id' => $rs->f('post_id'), 'meta_type' => My::META_PREFIX . 'id']);
        if ($rs_meta->isEmpty()) {
            $item = (new Text('', '-'));
        } else {
            $row  = new FeedRow(ZoneclearFeedServer::instance()->getFeeds(['feed_id' => $rs_meta->f('meta_id')]));
            $item = (new Link())
                ->href(dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), ['part' => 'feed', 'feed_id' => $row->id]) . '#feed')
                ->title(__('edit feed'))
                ->text(Html::escapeHTML($row->name));
        }
        $cols['feed'] = (new Para(null, 'td'))->class('nowrap')->items([$item])->render();
    }

    /**
     * Add info about feed on post page sidebar.
     */
    public static function adminPostHeaders(): string
    {
        return dcPage::jsModuleLoad(My::id() . '/js/post.js');
    }

    /**
     * Add info about feed on post page sidebar.
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        // nullsafe
        if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl)) {
            return;
        }

        // not feed on new post
        if ($post === null || $post->f('post_type') != 'post') {
            return;
        }

        $url = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'url',
            'limit'     => 1,
        ]);
        $url = $url->isEmpty() ? '' : $url->f('meta_id');
        if (!$url) {
            return;
        }

        $author = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'author',
            'limit'     => 1,
        ]);
        $author = $author->isEmpty() ? '' : $author->f('meta_id');

        $site = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'site',
            'limit'     => 1,
        ]);
        $site = $site->isEmpty() ? '' : $site->f('meta_id');

        $sitename = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'sitename',
            'limit'     => 1,
        ]);
        $sitename = $sitename->isEmpty() ? '' : $sitename->f('meta_id');

        $edit = (new Text('', ''));
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
            dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
        ]), dcCore::app()->blog->id)
        ) {
            $fid = dcCore::app()->meta->getMetadata([
                'post_id'   => $post->f('post_id'),
                'meta_type' => My::META_PREFIX . 'id',
                'limit'     => 1,
            ]);
            if (!$fid->isEmpty()) {
                $edit = (new Link())
                    ->href(dcCore::app()->adminurl->get(
                        'admin.plugin.' . My::id(),
                        ['part' => 'feed', 'feed_id' => $fid->f('meta_id')]
                    ))
                    ->text(__('Edit this feed'));
            }
        }

        $sidebar_items['options-box']['items'][My::id()] = (new Div('zcfs'))
                ->items([
                    (new Text('h5', __('Feed source'))),
                    (new Para())
                        ->separator('<br />')
                        ->items([
                            (new Link())
                                ->href($url)
                                ->title($author . ' - ' . $url)
                                ->text(__('feed URL')),
                            (new Link())
                                ->href($site)
                                ->title($sitename . ' - ' . $site)
                                ->text(__('site URL')),
                            $edit,
                        ]),
                ])
                ->render();
    }
}
