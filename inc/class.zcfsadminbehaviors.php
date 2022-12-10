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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - admin methods.
 * @since 2.6
 */
class zcfsAdminBehaviors
{
    public static function basename()
    {
    }

    public static function feedsSortbyCombo()
    {
        return [
            __('Date')        => 'feed_upddt',
            __('Name')        => 'lowername',
            __('Frequency')   => 'feed_upd_int',
            __('Update date') => 'feed_upd_last',
            __('Status')      => 'feed_status',
        ];
    }

    public static function entriesSortbyCombo()
    {
        return [
            __('Date')     => 'post_dt',
            __('Title')    => 'post_title',
            __('Category') => 'cat_title',
            __('Author')   => 'user_id',
            __('Status')   => 'post_status',
        ];
    }

    /**
     * Favorites.
     *
     * @param    dcFavorites $favs Array of favorites
     */
    public static function adminDashboardFavoritesV2(dcFavorites $favs)
    {
        $favs->register('zcfs', [
            'title'       => __('Feeds server'),
            'url'         => dcCore::app()->adminurl->get('admin.plugin.' . basename(dirname('../' . __DIR__))),
            'small-icon'  => dcPage::getPF(basename(dirname('../' . __DIR__)) . '/icon.svg'),
            'large-icon'  => dcPage::getPF(basename(dirname('../' . __DIR__)) . '/icon.svg'),
            'permissions' => dcCore::app()->auth->makePermissions([
                dcAuth::PERMISSION_USAGE,
                dcAuth::PERMISSION_CONTENT_ADMIN,
            ]),
            'dashboard_cb' => ['zcfsAdminBehaviors', 'adminDashboardFavoritesCallback'],
        ]);
    }

    /**
     * Favorites hack.
     *
     * @param    arrayObject $fav  Fav attributes
     */
    public static function adminDashboardFavoritesCallback($fav)
    {
        $zcfs = new zoneclearFeedServer();

        $count = $zcfs->getFeeds(['feed_status' => '0'], true)->f(0);
        if (!$count) {
            return null;
        }

        $fav['title'] .= '<br />' . sprintf(__('%s feed disabled', '%s feeds disabled', $count), $count);
        $fav['large-icon'] = dcPage::getPF(basename(dirname('../' . __DIR__)) . '/icon-update.svg');
        $fav['url']        = dcCore::app()->adminurl->get(
            'admin.plugin.' . basename(dirname('../' . __DIR__)),
            ['part' => 'feeds', 'sortby' => 'feed_status', 'order' => 'asc']
        );
    }

    /**
     * User pref columns lists.
     *
     * @param    arrayObject $cols Columns
     */
    public static function adminColumnsListsV2($cols)
    {
        $cols['zcfs_feeds'] = [
            __('Feeds server: Feeds'),
            [
                'desc'    => [true, __('Feed')],
                'period'  => [true, __('Frequency')],
                'update'  => [true, __('Last update')],
                'entries' => [true, __('Entries')],
            ],
        ];
        $cols['zcfs_entries'] = [
            __('Feeds server: Entries'),
            [
                'date'     => [true, __('Date')],
                'category' => [true, __('Category')],
                'author'   => [true, __('Author')],
            ],
        ];
    }

    /**
     * User pref filters options.
     *
     * @param    arrayObject $sorts Sort options
     */
    public static function adminFiltersListsV2($sorts)
    {
        $sorts['zcfs_feeds'] = [
            __('Feeds server: Feeds'),
            self::feedsSortbyCombo(),
            'lowername',
            'asc',
            [__('feeds per page'), 30],
        ];
        $sorts['zcfs_entries'] = [
            __('Feeds server: Entries'),
            self::entriesSortbyCombo(),
            'post_dt',
            'desc',
            [__('entries per page'), 30],
        ];
    }

    /**
     * Add javascript for toggle to post edition page header.
     *
     * @return string Page header
     */
    public static function adminPostHeaders()
    {
        return dcPage::jsLoad(dcPage::getPF(basename(dirname('../' . __DIR__)) . '/js/post.js'));
    }

    /**
     * Add form to post sidebar.
     *
     * @param  ArrayObject $main_items    Main items
     * @param  ArrayObject $sidebar_items Sidebar items
     * @param  record      $post          Post record or null
     */
    public static function adminPostFormItems(ArrayObject $main_items, ArrayObject $sidebar_items, $post)
    {
        if ($post === null || $post->post_type != 'post') {
            return null;
        }

        $url = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_url',
            'limit'     => 1,
        ]);
        $url = $url->isEmpty() ? '' : $url->meta_id;
        if (!$url) {
            return null;
        }

        $author = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_author',
            'limit'     => 1,
        ]);
        $author = $author->isEmpty() ? '' : $author->meta_id;

        $site = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_site',
            'limit'     => 1,
        ]);
        $site = $site->isEmpty() ? '' : $site->meta_id;

        $sitename = dcCore::app()->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_sitename',
            'limit'     => 1,
        ]);
        $sitename = $sitename->isEmpty() ? '' : $sitename->meta_id;

        $edit = '';
        if (dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CONTENT_ADMIN]), dcCore::app()->blog->id)) {
            $fid = dcCore::app()->meta->getMetadata([
                'post_id'   => $post->post_id,
                'meta_type' => 'zoneclearfeed_id',
                'limit'     => 1,
            ]);
            if (!$fid->isEmpty()) {
                $edit = sprintf(
                    '<p><a href="%s">%s</a></p>',
                    dcCore::app()->adminurl->get(
                        'admin.plugin.' . basename(dirname('../' . __DIR__)),
                        ['part' => 'feed', 'feed_id' => $fid->meta_id]
                    ),
                    __('Edit this feed')
                );
            }
        }

        $sidebar_items['options-box']['items']['zcfs'] = '<div id="zcfs">' .
            '<h5>' . __('Feed source') . '</h5>' .
            '<p>' .
            '<a href="' . $url . '" title="' . $author . ' - ' . $url . '">' . __('feed URL') . '</a> - ' .
            '<a href="' . $site . '" title="' . $sitename . ' - ' . $site . '">' . __('site URL') . '</a>' .
            '</p>' .
            $edit .
            '</div>';
    }

    /**
     * Delete related info about feed post in meta table.
     *
     * @param  integer $post_id Post id
     */
    public static function adminBeforePostDelete($post_id)
    {
        dcCore::app()->con->execute(
            'DELETE FROM ' . dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' ' .
            'WHERE post_id = ' . ((int) $post_id) . ' ' .
            'AND meta_type ' . dcCore::app()->con->in([
                'zoneclearfeed_url',
                'zoneclearfeed_author',
                'zoneclearfeed_site',
                'zoneclearfeed_sitename',
                'zoneclearfeed_id',
            ]) . ' '
        );
    }
}
