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

$core->blog->settings->addNamespace('zoneclearFeedServer');

require_once dirname(__FILE__) . '/_widgets.php';

$_menu['Plugins']->addItem(
    __('Feeds server'),
    $core->adminurl->get('admin.plugin.zoneclearFeedServer'),
    dcPage::getPF('zoneclearFeedServer/icon.png'),
    preg_match(
        '/' . preg_quote($core->adminurl->get('admin.plugin.zoneclearFeedServer')) . '(&.*)?$/', 
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->check('admin', $core->blog->id)
);

# Delete related info about feed post in meta table
$core->addBehavior('adminBeforePostDelete',['zcfsAdminBehaviors', 'adminBeforePostDelete']);

if ($core->auth->check('admin', $core->blog->id)) {

    # Dashboard icon
    $core->addBehavior('adminDashboardFavorites', ['zcfsAdminBehaviors', 'adminDashboardFavorites']);
    # User pref
    $core->addBehavior('adminColumnsLists', ['zcfsAdminBehaviors', 'adminColumnsLists']);
    $core->addBehavior('adminFiltersLists', ['zcfsAdminBehaviors', 'adminFiltersLists']);
    # Add info about feed on post page sidebar
    $core->addBehavior('adminPostHeaders', ['zcfsAdminBehaviors', 'adminPostHeaders']);
    $core->addBehavior('adminPostFormItems', ['zcfsAdminBehaviors', 'adminPostFormItems']);
}

# Take care about tweakurls (thanks Mathieu M.)
if (version_compare($core->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {

    $core->addbehavior('zcfsAfterPostCreate', ['zoneclearFeedServer', 'tweakurlsAfterPostCreate']);
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - admin methods.
 * @since 2.6
 */
class zcfsAdminBehaviors
{
    public static function feedsSortbyCombo()
    {
        return [
            __('Date')           => 'feed_upddt',
            __('Name')           => 'lowername',
            __('Frequency')      => 'feed_upd_int',
            __('Date of update') => 'feed_upd_last',
            __('Status')         => 'feed_status'
        ];
    }

    public static function entriesSortbyCombo()
    {
        return [
            __('Date')     => 'post_dt',
            __('Title')    => 'post_title',
            __('Category') => 'cat_title',
            __('Author')   => 'user_id',
            __('Status')   => 'post_status'
        ];
    }

    /**
     * Favorites.
     *
     * @param    dcCore      $core dcCore instance
     * @param    arrayObject $favs Array of favorites
     */
    public static function adminDashboardFavorites(dcCore $core, dcFavorites $favs)
    {
        $favs->register('zcfs', [
            'title'        => __('Feeds server'),
            'url'          => $core->adminurl->get('admin.plugin.zoneclearFeedServer'),
            'small-icon'   => dcPage::getPF('zoneclearFeedServer/icon.png'),
            'large-icon'   => dcPage::getPF('zoneclearFeedServer/icon-big.png'),
            'permissions'  => 'usage,contentadmin',
            'active_cb'    => ['zcfsAdminBehaviors', 'adminDashboardFavoritesActive'],
            'dashboard_cb' => ['zcfsAdminBehaviors', 'adminDashboardFavoritesCallback']
        ]);
    }

    /**
     * Favorites selection.
     *
     * @param    string $request Requested page
     * @param    array  $params  Requested parameters
     */
    public static function adminDashboardFavoritesActive($request, $params)
    {
        return $request == 'plugin.php' 
            && isset($params['p']) 
            && $params['p'] == 'zoneclearFeedServer';
    }

    /**
     * Favorites hack.
     *
     * @param    dcCore      $core dcCore instance
     * @param    arrayObject $fav  Fav attributes
     */
    public static function adminDashboardFavoritesCallback(dcCore $core, $fav)
    {
        $zcfs = new zoneclearFeedServer($core);

        $count = $zcfs->getFeeds(['feed_status' => '0'], true)->f(0);
        if (!$count) {
            return null;
        }

        $fav['title'] .= '<br />'.sprintf(__('%s feed disabled', '%s feeds disabled', $count), $count);
        $fav['large-icon'] = dcPage::getPF('zoneclearFeedServer/icon-big-update.png');
        $fav['url'] = $core->adminurl->get(
            'admin.plugin.zoneclearFeedServer', 
            ['part' => 'feeds', 'sortby' => 'feed_status', 'order' => 'asc']
        );
    }

    /**
     * User pref columns lists.
     *
     * @param    dcCore      $core dcCore instance
     * @param    arrayObject $cols Columns
     */
    public static function adminColumnsLists(dcCore $core, $cols)
    {
        $cols['zcfs_feeds'] = [
            __('Feeds server: Feeds'),
            [
                'desc'   => [true, __('Feed')],
                'period' => [true, __('Frequency')],
                'update' => [true, __('Last update')],
                'entries'  => [true, __('Entries')]
            ]
        ];
        $cols['zcfs_entries'] = [
            __('Feeds server: Entries'),
            [
                'date'       => [true, __('Date')],
                'category'   => [true, __('Category')],
                'author'     => [true, __('Author')]
            ]
        ];
    }

    /**
     * User pref filters options.
     *
     * @param    dcCore      $core  dcCore instance
     * @param    arrayObject $sorts Sort options
     */
    public static function adminFiltersLists(dcCore $core, $sorts)
    {
        $sorts['zcfs_feeds'] = [
            __('Feeds server: Feeds'),
            self::feedsSortbyCombo(),
            'lowername',
            'asc',
            [__('feeds per page'), 30]
        ];
        $sorts['zcfs_entries'] = [
            __('Feeds server: Entries'),
            self::entriesSortbyCombo(),
            'post_dt',
            'desc',
            [__('entries per page'), 30]
        ];
    }

    /**
     * Add javascript for toggle to post edition page header.
     * 
     * @return string Page header
     */
    public static function adminPostHeaders()
    {
        return dcPage::jsLoad(dcPage::getPF('zoneclearFeedServer/js/post.js'));
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

        global $core;

        $url = $core->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_url',
            'limit'     => 1
        ]);
        $url = $url->isEmpty() ? '' : $url->meta_id;
        if (!$url) {
            return null;
        }

        $author = $core->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_author',
            'limit'     => 1
        ]);
        $author = $author->isEmpty() ? '' : $author->meta_id;

        $site = $core->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_site',
            'limit'     => 1
        ]);
        $site = $site->isEmpty() ? '' : $site->meta_id;

        $sitename = $core->meta->getMetadata([
            'post_id'   => $post->post_id,
            'meta_type' => 'zoneclearfeed_sitename',
            'limit'     => 1
        ]);
        $sitename = $sitename->isEmpty() ? '' : $sitename->meta_id;

        $edit = '';
        if ($core->auth->check('admin', $core->blog->id)) {
            $fid = $core->meta->getMetadata([
                'post_id'   => $post->post_id,
                'meta_type' => 'zoneclearfeed_id',
                'limit'     => 1
            ]);
            if (!$fid->isEmpty()) {
                $edit = sprintf(
                    '<p><a href="%s">%s</a></p>',
                    $core->adminurl->get(
                        'admin.plugin.zoneclearFeedServer', 
                        ['part' => 'feed', 'feed_id' => $fid->meta_id]
                    ),
                    __('Edit this feed')
                );
            }
        }

        $sidebar_items['options-box']['items']['zcfs'] = 
            '<div id="zcfs">'.
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
        global $core;

        $core->con->execute(
            'DELETE FROM ' . $core->prefix . 'meta ' .
            'WHERE post_id = ' . ((integer) $post_id) . ' ' .
            'AND meta_type ' . $core->con->in([
                'zoneclearfeed_url',
                'zoneclearfeed_author',
                'zoneclearfeed_site',
                'zoneclearfeed_sitename',
                'zoneclearfeed_id'
            ]).' '
        );
    }
}