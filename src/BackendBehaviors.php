<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\{
    Div,
    Link,
    Para,
    Text
};
use Dotclear\Helper\Html\Html;

/**
 * @brief       zoneclearFeedServer backend behaviors class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class BackendBehaviors
{
    /**
     * User dashboard favorites icon.
     */
    public static function adminDashboardFavoritesV2(Favorites $favs): void
    {
        $favs->register(My::id(), [
            'title'       => My::name(),
            'url'         => My::manageUrl(),
            'small-icon'  => My::icons(),
            'large-icon'  => My::icons(),
            'permissions' => App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ]),
            // update user dashboard favorites icon with nb of updated feeds
            'dashboard_cb' => function (ArrayObject $fav): void {
                $count = ZoneclearFeedServer::instance()->getFeeds(['feed_status' => '0'], true)->f(0);
                if (!$count || !is_numeric($count)) {
                    return;
                }

                $fav['title'] .= '<br />' . sprintf(__('%s feed disabled', '%s feeds disabled', (int) $count), (int) $count);
                $fav['large-icon'] = My::fileURL('icon-update.svg');
                $fav['url']        = My::manageUrl(['part' => 'feeds', 'sortby' => 'feed_status', 'order' => 'asc']);
            },
        ]);
    }

    /**
     * Lists columns user preference.
     *
     * @param   ArrayObject<string, mixed>  $cols
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
     *
     * @param   ArrayObject<string, mixed>  $sorts
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
     *
     * @param   ArrayObject<string, mixed>  $cols
     */
    public static function adminPostListHeaderV2(MetaRecord $rs, ArrayObject $cols): void
    {
        $cols['feed'] = (new Text('th', __('Feed')))->extra('scope="col"')->render();
    }

    /**
     * Add body column to posts list.
     *
     * @param   ArrayObject<string, mixed>  $cols
     */
    public static function adminPostListValueV2(MetaRecord $rs, ArrayObject $cols): void
    {
        $rs_meta = App::meta()->getMetadata(['post_id' => $rs->f('post_id'), 'meta_type' => My::META_PREFIX . 'id']);
        if ($rs_meta->isEmpty()) {
            $item = (new Text('', '-'));
        } else {
            $row  = new FeedRow(ZoneclearFeedServer::instance()->getFeeds(['feed_id' => $rs_meta->f('meta_id')]));
            $item = (new Link())
                ->href(My::manageUrl(['part' => 'feed', 'feed_id' => $row->id]) . '#feed')
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
        return My::jsLoad('post');
    }

    /**
     * Add info about feed on post page sidebar.
     *
     * @param   ArrayObject<string, mixed>  $main_items
     * @param   ArrayObject<string, mixed>  $sidebar_items
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, ?MetaRecord $post): void
    {
        // nullsafe
        if (!App::blog()->isDefined()) {
            return;
        }

        // not feed on new post
        if ($post === null || $post->f('post_type') != 'post') {
            return;
        }

        $url = App::meta()->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'url',
            'limit'     => 1,
        ]);
        $url = $url->isEmpty() ? '' : $url->f('meta_id');
        if (!$url) {
            return;
        }

        $author = App::meta()->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'author',
            'limit'     => 1,
        ]);
        $author = $author->isEmpty() ? '' : $author->f('meta_id');

        $site = App::meta()->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'site',
            'limit'     => 1,
        ]);
        $site = $site->isEmpty() ? '' : $site->f('meta_id');

        $sitename = App::meta()->getMetadata([
            'post_id'   => $post->f('post_id'),
            'meta_type' => My::META_PREFIX . 'sitename',
            'limit'     => 1,
        ]);
        $sitename = $sitename->isEmpty() ? '' : $sitename->f('meta_id');

        $edit = (new Text('', ''));
        if (App::auth()->check(App::auth()->makePermissions([
            App::auth()::PERMISSION_CONTENT_ADMIN,
        ]), App::blog()->id())
        ) {
            $fid = App::meta()->getMetadata([
                'post_id'   => $post->f('post_id'),
                'meta_type' => My::META_PREFIX . 'id',
                'limit'     => 1,
            ]);
            if (!$fid->isEmpty()) {
                $edit = (new Link())
                    ->href(My::manageUrl(['part' => 'feed', 'feed_id' => $fid->f('meta_id')]))
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
