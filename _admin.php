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

# Namespace for settings
$core->blog->settings->addNamespace('zoneclearFeedServer');

# Check if latest version is installed
if ($core->getVersion('zoneclearFeedServer') != 
    $core->plugins->moduleInfo('zoneclearFeedServer', 'version')) {

    return null;
}

# Widgets
require_once dirname(__FILE__).'/_widgets.php';

# Admin menu
$_menu['Blog']->addItem(
    __('Feeds server'),
    'plugin.php?p=zoneclearFeedServer',
    'index.php?pf=zoneclearFeedServer/icon.png',
    preg_match(
        '/plugin.php\?p=zoneclearFeedServer(&.*)?$/',
        $_SERVER['REQUEST_URI']
    ),
    $core->auth->check('admin', $core->blog->id)
);

# Delete related info about feed post in meta table
$core->addBehavior(
    'adminBeforePostDelete',
    array('zcfsAdminBehaviors', 'adminBeforePostDelete')
);

if ($core->auth->check('admin', $core->blog->id)) {

    # Dashboard icon
    $core->addBehavior(
        'adminDashboardFavorites',
        array('zcfsAdminBehaviors', 'adminDashboardFavorites')
    );

    # Add info about feed on post page sidebar
    $core->addBehavior(
        'adminPostHeaders',
        array('zcfsAdminBehaviors', 'adminPostHeaders')
    );
    $core->addBehavior(
        'adminPostFormItems',
        array('zcfsAdminBehaviors', 'adminPostFormItems')
    );
}

# Take care about tweakurls (thanks Mathieu M.)
if (version_compare($core->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {

    $core->addbehavior(
        'zcfsAfterPostCreate',
        array('zoneclearFeedServer', 'tweakurlsAfterPostCreate')
    );
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - admin methods.
 * @since 2.6
 */
class zcfsAdminBehaviors
{
    /**
     * Favorites.
     *
     * @param    dcCore      $core dcCore instance
     * @param    arrayObject $favs Array of favorites
     */
    public static function adminDashboardFavorites(dcCore $core, $favs)
    {
        $favs->register('zcfs', array(
            'title'        => __('Feeds server'),
            'url'        => 'plugin.php?p=zoneclearFeedServer',
            'small-icon'    => 'index.php?pf=zoneclearFeedServer/icon.png',
            'large-icon'    => 'index.php?pf=zoneclearFeedServer/icon-big.png',
            'permissions'    => 'usage,contentadmin',
            'active_cb'    => array(
                'zcfsAdminBehaviors', 
                'adminDashboardFavoritesActive'
            ),
            'dashboard_cb' => array(
                'zcfsAdminBehaviors',
                'adminDashboardFavoritesCallback'
            )
        ));
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
        $count = $zcfs->getFeeds(array(
            'feed_status' => '0'
        ), true)->f(0);

        if (!$count) {

            return null;
        }

        $fav['title'] .= '<br />'.sprintf(
            __('%s feed disabled', '%s feeds disabled', $count),
            $count
        );
        $fav['url'] = 'plugin.php?p=zoneclearFeedServer&part=feeds'.
            '&sortby=feed_status&order=asc';
        $fav['large-icon'] = 'index.php?pf=zoneclearFeedServer'.
            '/icon-big-update.png';
    }

    /**
     * Add javascript for toggle to post edition page header.
     * 
     * @return string Page header
     */
    public static function adminPostHeaders()
    {
        return dcPage::jsLoad(
            'index.php?pf=zoneclearFeedServer/js/post.js'
        );
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

        $url = $core->meta->getMetadata(array(
            'post_id'        => $post->post_id,
            'meta_type'    => 'zoneclearfeed_url',
            'limit'        => 1
        ));
        $url = $url->isEmpty() ? '' : $url->meta_id;

        if (!$url) {

            return null;
        }

        $author = $core->meta->getMetadata(array(
            'post_id'        => $post->post_id,
            'meta_type'    => 'zoneclearfeed_author',
            'limit'        => 1
        ));
        $author = $author->isEmpty() ? '' : $author->meta_id;

        $site = $core->meta->getMetadata(array(
            'post_id'        => $post->post_id,
            'meta_type'    => 'zoneclearfeed_site',
            'limit'        => 1
        ));
        $site = $site->isEmpty() ? '' : $site->meta_id;

        $sitename = $core->meta->getMetadata(array(
            'post_id'        => $post->post_id,
            'meta_type'    => 'zoneclearfeed_sitename',
            'limit'        => 1
        ));
        $sitename = $sitename->isEmpty() ? '' : $sitename->meta_id;

        $edit = '';
        if ($core->auth->check('admin', $core->blog->id)) {
            $fid = $core->meta->getMetadata(array(
                'post_id'        => $post->post_id,
                'meta_type'    => 'zoneclearfeed_id',
                'limit'        => 1
            ));
            if (!$fid->isEmpty()) {
                $edit = 
                    '<p><a href="plugin.php?p=zoneclearFeedServer'.
                    '&amp;part=feed&amp;feed_id='.$fid->meta_id.
                    '">'.__('Edit this feed').'</a></p>';
            }
        }

        $sidebar_items['options-box']['items']['zcfs'] = 
            '<div id="zcfs">'.
            '<h5>'.__('Feed source').'</h5>'.
            '<p>'.
            '<a href="'.$url.'" title="'.$author.' - '.$url.'">'.__('feed URL').'</a> - '.
            '<a href="'.$site.'" title="'.$sitename.' - '.$site.'">'.__('site URL').'</a>'.
            '</p>'.
            $edit.
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
            'DELETE FROM '.$core->prefix.'meta '.
            'WHERE post_id = '.((integer) $post_id).' '.
            'AND meta_type '.$core->con->in(array(
                'zoneclearfeed_url',
                'zoneclearfeed_author',
                'zoneclearfeed_site',
                'zoneclearfeed_sitename',
                'zoneclearfeed_id'
            )).' '
        );
    }
}