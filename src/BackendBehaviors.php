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
use dcSettings;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Link,
    Number,
    Para,
    Select,
    Text
};
use Dotclear\Helper\Html\Html;

/**
 * Backend behaviors.
 */
class BackendBehaviors
{
    /**
     * Module settings save.
     *
     * Used in blog settings and module config.
     */
    public static function adminBeforeBlogSettingsUpdate(?dcSettings $blog_settings): void
    {
        // read settings
        $s = ZoneclearFeedServer::instance()->settings;

        // write settings
        foreach ($s->dump() as $key => $value) {
            $s->set($key, $_POST[My::id() . $key] ?? $value);
        }
    }

    /**
     * Module settings form.
     *
     * Used in blog settings and module config.
     */
    public static function adminBlogPreferencesFormV2(?dcSettings $blog_settings): void
    {
        // nullsafe
        if (is_null(dcCore::app()->blog)) {
            return;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        $msg = [];
        if (!is_writable(DC_TPL_CACHE)) {
            $msg[] = (new Para())
                ->class('error')
                ->text(__('Dotclear cache is not writable or not well configured!'));
        }
        if ($s->pub_active) {
            $msg[] = (new Para())
                ->items([
                    (new Link())
                        ->class('onblog_link outgoing')
                        ->text(__('View the public list of feeds') . ' <img alt="" src="images/outgoing-link.svg">')
                        ->href(dcCore::app()->blog->url . dcCore::app()->url->getBase('zoneclearFeedsPage')),
                ]);
        }

        $titles = [];
        foreach ($z->getPublicUrlTypes() as $k => $v) {
            $titles[] = (new Para(null, 'li'))
                ->items([
                    (new Checkbox([My::id() . 'post_title_redir[]', My::id() . 'post_title_redir_' . $v], in_array($v, $s->post_title_redir)))
                        ->value($v),
                    (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for(My::id() . 'post_title_redir_' . $v),
                ]);
        }

        $contents = [];
        foreach ($z->getPublicUrlTypes() as $k => $v) {
            $contents[] = (new Para(null, 'li'))
                ->items([
                    (new Checkbox([My::id() . 'post_full_tpl_[]', My::id() . 'post_full_tpl_' . $v], in_array($v, $s->post_full_tpl)))
                        ->value($v),
                    (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for(My::id() . 'post_full_tpl_' . $v),
                ]);
        }

        echo
        (new Div())->class('fieldset')
            ->items([
                !is_null($blog_settings) ?
                    (new Text('h4', My::name()))
                        ->id('disclaimerParam') :
                    (new Text()),
                (new Div())
                    ->items($msg),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'active', $s->active))
                            ->value(1),
                        (new Label(__('Enable plugin'), Label::OUTSIDE_LABEL_AFTER))
                            ->class('classic')
                            ->for(My::id() . 'active'),
                    ]),
                (new Div())
                    ->class('clear two-cols')
                    ->items([
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Label(__('Status of new posts:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'post_status_new'),
                                        (new Select(My::id() . 'post_status_new'))
                                            ->items(Combo::postsStatus())
                                            ->default((string) $s->post_status_new),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('Owner of entries created by the feed server:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'user'),
                                        (new Select(My::id() . 'user'))
                                            ->items($z->getAllBlogAdmins())
                                            ->default($s->user),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('How to transform imported tags:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'tag_case'),
                                        (new Select(My::id() . 'tag_case'))
                                            ->items(Combo::tagCase())
                                            ->default((string) $s->tag_case),
                                    ]),
                            ]),
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Label(__('Update feeds on public side:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'bhv_pub_upd'),
                                        (new Select(My::id() . 'bhv_pub_upd'))
                                            ->items(Combo::pubUpdate())
                                            ->default((string) $s->bhv_pub_upd),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('Number of feeds to update at one time:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'update_limit'),
                                        (new Number(My::id() . 'update_limit'))
                                            ->min(0)
                                            ->max(20)
                                            ->value($s->update_limit),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'keep_empty_feed', $s->keep_empty_feed))
                                            ->value(1),
                                        (new Label(__('Keep active empty feeds'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for(My::id() . 'keep_empty_feed'),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'pub_active', $s->pub_active))
                                            ->value(1),
                                        (new Label(__('Enable public page'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for(My::id() . 'pub_active'),
                                    ]),
                            ]),
                    ]),
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Text('p', __('Redirect to original post on:'))),
                                (new Para(null, 'ul'))
                                    ->items($titles),
                            ]),
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Text('p', __('Show full content on:'))),
                                (new Para(null, 'ul'))
                                    ->items($contents),
                            ]),
                    ]),
                (new Div())->class('clear')->items(
                    !is_null($blog_settings) && $s->active ?
                        [(new Para())
                            ->items([
                                (new Link())
                                    ->href(dcCore::app()->adminurl?->get('admin.plugin.' . My::id()))
                                    ->text(__('Configure feeds')),
                            ])] :
                        [],
                ),
                (new Div())->class('clear'),
            ])
            ->render();
    }

    /**
     * User dashboard favorites icon.
     */
    public static function adminDashboardFavoritesV2(dcFavorites $favs): void
    {
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
        $cols['feed'] = (new Para(null, 'th'))->text(__('Feed'))->extra('scope="col"')->render();
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
        if (is_null(dcCore::app()->blog)) {
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
