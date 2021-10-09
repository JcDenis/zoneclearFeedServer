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

if ($core->getVersion('zoneclearFeedServer') != 
    $core->plugins->moduleInfo('zoneclearFeedServer', 'version')) {

    return null;
}

dcPage::check('admin');

$zcfs = new zoneclearFeedServer($core);

############################################################
#
# One feed
#
############################################################

if (isset($_REQUEST['part']) && $_REQUEST['part'] == 'feed') {

    $feed_id = '';
    $feed_name = '';
    $feed_desc = '';
    $feed_owner = '';
    $feed_tweeter = '';
    $feed_url = '';
    $feed_feed = '';
    $feed_lang = $core->auth->getInfo('user_lang');
    $feed_tags = '';
    $feed_get_tags = '0';
    $feed_cat_id = '';
    $feed_status = '0';
    $feed_upd_int = 3600;

    $can_view_page = true;

    $feed_headlink = '<link rel="%s" title="%s" href="'.$p_url.'&amp;part=feed&amp;feed_id=%s" />';
    $feed_link = '<a href="'.$p_url.'&amp;part=feed&amp;feed_id=%s" title="%s">%s</a>';

    $next_link = $prev_link = $next_headlink = $prev_headlink = null;

    # Combos
    $combo_langs = l10n::getISOcodes(true);
    $combo_status = $zcfs->getAllStatus();
    $combo_upd_int = $zcfs->getAllUpdateInterval();
    $combo_categories = array('-' => '');
    try {
        $categories = $core->blog->getCategories(array(
            'post_type' => 'post'
        ));
        while ($categories->fetch()) {
            $combo_categories[
                str_repeat('&nbsp;&nbsp;', $categories->level-1).
                '&bull; '.html::escapeHTML($categories->cat_title)
            ] = $categories->cat_id;
        }
    }
    catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    # Get entry informations
    if (!empty($_REQUEST['feed_id'])) {
        $feed = $zcfs->getFeeds(array('feed_id' => $_REQUEST['feed_id']));

        if ($feed->isEmpty()) {
            $core->error->add(__('This feed does not exist.'));
            $can_view_page = false;
        }
        else {
            $feed_id = $feed->feed_id;
            $feed_name = $feed->feed_name;
            $feed_desc = $feed->feed_desc;
            $feed_owner = $feed->feed_owner;
            $feed_tweeter = $feed->feed_tweeter;
            $feed_url = $feed->feed_url;
            $feed_feed = $feed->feed_feed;
            $feed_lang = $feed->feed_lang;
            $feed_tags = $feed->feed_tags;
            $feed_get_tags = $feed->feed_get_tags;
            $feed_cat_id = $feed->cat_id;
            $feed_status = $feed->feed_status;
            $feed_upd_int = $feed->feed_upd_int;

            $next_params = array(
                'sql' => 'AND feed_id < '.$feed_id.' ',
                'limit' => 1
            );
            $next_rs = $zcfs->getFeeds($next_params);
            $prev_params = array(
                'sql' => 'AND feed_id > '.$feed_id.' ',
                'limit' => 1
            );
            $prev_rs = $zcfs->getFeeds($prev_params);

            if (!$next_rs->isEmpty()) {
                $next_link = sprintf($feed_link,$next_rs->feed_id,
                    html::escapeHTML($next_rs->feed_name), __('next feed').'&nbsp;&#187;');
                $next_headlink = sprintf($feed_headlink, 'next',
                    html::escapeHTML($next_rs->feed_name), $next_rs->feed_id);
            }

            if (!$prev_rs->isEmpty()) {
                $prev_link = sprintf($feed_link,$prev_rs->feed_id,
                    html::escapeHTML($prev_rs->feed_name), '&#171;&nbsp;'.__('previous feed'));
                $prev_headlink = sprintf($feed_headlink, 'previous',
                    html::escapeHTML($prev_rs->feed_name), $prev_rs->feed_id);
            }
        }
    }

    if (!empty($_POST['action']) && $_POST['action'] == 'savefeed') {
        try {
            $feed_name    = $_POST['feed_name'];
            $feed_desc    = $_POST['feed_desc'];
            $feed_owner    = $_POST['feed_owner'];
            $feed_tweeter    = $_POST['feed_tweeter'];
            $feed_url        = $_POST['feed_url'];
            $feed_feed    = $_POST['feed_feed'];
            $feed_lang    = $_POST['feed_lang'];
            $feed_tags    = $_POST['feed_tags'];
            $feed_get_tags    = empty($_POST['feed_get_tags']) ? 0 : 1;
            $feed_cat_id    = $_POST['feed_cat_id'];
            if (isset($_POST['feed_status'])) {
                $feed_status = (integer) $_POST['feed_status'];
            }
            $feed_upd_int    = $_POST['feed_upd_int'];

            $testfeed_params['feed_feed'] = $feed_feed;
            if ($feed_id) {
                $testfeed_params['sql'] ='AND feed_id <> '.$feed_id.' ';
            }
            if ($zcfs->getFeeds($testfeed_params, true)->f(0)) {
                throw new Exception(__('Record with same feed URL already exists.'));
            }
            if (empty($feed_name)) {
                throw new Exception(__('You must provide a name.'));
            }
            if (empty($feed_owner)) {
                throw new Exception(__('You must provide an owner.'));
            }
            if (!zoneclearFeedServer::validateURL($feed_url)) {
                throw new Exception(__('You must provide valid site URL.'));
            }
            if (!zoneclearFeedServer::validateURL($feed_feed)) {
                throw new Exception(__('You must provide valid feed URL.'));
            }
            $get_feed_cat_id = $core->blog->getCategory($feed_cat_id);
            if ($feed_cat_id != '' && !$get_feed_cat_id) {
                throw new Exception(__('You must provide valid category.'));
            }        
        }
        catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    if (!empty($_POST['action']) && $_POST['action'] == 'savefeed' 
     && !$core->error->flag()
    ) {

        $cur = $zcfs->openCursor();
        $cur->feed_name = $feed_name;
        $cur->feed_desc = $feed_desc;
        $cur->feed_owner = $feed_owner;
        $cur->feed_tweeter = $feed_tweeter;
        $cur->feed_url = $feed_url;
        $cur->feed_feed = $feed_feed;
        $cur->feed_lang = $feed_lang;
        $cur->feed_tags = $feed_tags;
        $cur->feed_get_tags = (integer) $feed_get_tags;
        $cur->cat_id = $feed_cat_id != '' ? (integer) $feed_cat_id : null;
        $cur->feed_status = (integer) $feed_status;
        $cur->feed_upd_int = (integer) $feed_upd_int;

        # Update feed
        if ($feed_id) {
            try {
                # --BEHAVIOR-- adminBeforeZoneclearFeedServerFeedUpdate
                $core->callBehavior(
                    'adminBeforeZoneclearFeedServerFeedUpdate',
                    $cur,
                    $feed_id
                );

                $zcfs->updFeed($feed_id, $cur);

                # --BEHAVIOR-- adminAfterZoneclearFeedServerFeedUpdate
                $core->callBehavior(
                    'adminAfterZoneclearFeedServerFeedUpdate',
                    $cur,
                    $feed_id
                );

                dcPage::addSuccessNotice(
                    __('Feed successfully updated.')
                );
                http::redirect(
                    $p_url.'&part=feed&feed_id='.$feed_id
                );
            }
            catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }
        else {        
            try {
                # --BEHAVIOR-- adminBeforeZoneclearFeedServerFeedCreate
                $core->callBehavior(
                    'adminBeforeZoneclearFeedServerFeedCreate',
                    $cur
                );

                $return_id = $zcfs->addFeed($cur);

                # --BEHAVIOR-- adminAfterZoneclearFeedServerFeedCreate
                $core->callBehavior(
                    'adminAfterZoneclearFeedServerFeedCreate',
                    $cur,
                    $return_id
                );

                dcPage::addSuccessNotice(
                    __('Feed successfully created.')
                );
                http::redirect(
                    $p_url.'&part=feed&feed_id='.$return_id
                );
            }
            catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }
    }

    # Prepared entries list
    if ($feed_id && $can_view_page) {
        try {
            # Getting categories
            $categories = $core->blog->getCategories(array(
                'post_type' => 'post'
            ));

            # Getting authors
            $users = $core->blog->getPostsUsers();

            # Getting dates
            $dates = $core->blog->getDates(array(
                'type' => 'month'
            ));

            # Getting langs
            $langs = $core->blog->getLangs();
        }
        catch (Exception $e) {
            $core->error->add($e->getMessage());
        }

        # Creating filter combo boxes
        if (!$core->error->flag()) {

            $users_combo = array_merge(
                array('-' => ''),
                dcAdminCombos::getUsersCombo($users)
            );

            $categories_combo = array_merge(
                array(
                    new formSelectOption('-', ''),
                    new formSelectOption(__('(No cat)'), 'NULL')
                ),
                dcAdminCombos::getCategoriesCombo($categories, false)
            );
            $categories_values = array();
            foreach ($categories_combo as $cat) {
                if (isset($cat->value)) {
                    $categories_values[$cat->value] = true;
                }
            }

            $status_combo = array_merge(
                array('-' => ''),
                dcAdminCombos::getPostStatusesCombo()    
            );

            $selected_combo = array(
                '-'                => '',
                __('Selected')        => '1',
                __('Not selected')    => '0'
            );

            $dt_m_combo = array_merge(
                array('-' => ''),
                dcAdminCombos::getDatesCombo($dates)
            );

            $lang_combo = array_merge(
                array('-' => ''),
                dcAdminCombos::getLangsCombo($langs,false)    
            );

            $sortby_combo = array(
                __('Date')    => 'post_dt',
                __('Title')    => 'post_title',
                __('Category')    => 'cat_title',
                __('Author')    => 'user_id',
                __('Status')    => 'post_status',
                __('Selected')    => 'post_selected'
            );

            $order_combo = array(
                __('Descending')    => 'desc',
                __('Ascending')    => 'asc'
            );
        }

        # Posts action
        $posts_actions_page = new dcPostsActionsPage(
            $core,
            'plugin.php',
            array(
                'p'        => 'zoneclearFeedServer',
                'part'    => 'feed',
                'feed_id'    => $feed_id,
                '_ANCHOR'    => 'entries'
            )
        );

        if ($posts_actions_page->process()) {
            return null;
        }

        /* Get posts
        -------------------------------------------------------- */
        $user_id = !empty($_GET['user_id']) ?    $_GET['user_id'] : '';
        $cat_id = !empty($_GET['cat_id']) ?    $_GET['cat_id'] : '';
        $status = isset($_GET['status']) ?        $_GET['status'] : '';
        $selected = isset($_GET['selected']) ?    $_GET['selected'] : '';
        $month = !empty($_GET['month']) ?        $_GET['month'] : '';
        $lang = !empty($_GET['lang']) ?        $_GET['lang'] : '';
        $sortby = !empty($_GET['sortby']) ?    $_GET['sortby'] : 'post_dt';
        $order = !empty($_GET['order']) ?        $_GET['order'] : 'desc';

        $show_filters = false;

        $page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
        $nb_per_page =  30;

        if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
            if ($nb_per_page != $_GET['nb']) {
                $show_filters = true;
            }
            $nb_per_page = (integer) $_GET['nb'];
        }

        $params['limit'] = array((($page-1)*$nb_per_page), $nb_per_page);
        $params['no_content'] = true;

        # - User filter
        if ($user_id !== '' && in_array($user_id, $users_combo)) {
            $params['user_id'] = $user_id;
            $show_filters = true;
        }
        # - Categories filter
        if ($cat_id !== '' && in_array($cat_id, $categories_combo)) {
            $params['cat_id'] = $cat_id;
            $show_filters = true;
        }
        # - Status filter
        if ($status !== '' && in_array($status, $status_combo)) {
            $params['post_status'] = $status;
            $show_filters = true;
        }
        # - Selected filter
        if ($selected !== '' && in_array($selected, $selected_combo)) {
            $params['post_selected'] = $selected;
            $show_filters = true;
        }
        # - Month filter
        if ($month !== '' && in_array($month, $dt_m_combo)) {
            $params['post_month'] = substr($month, 4, 2);
            $params['post_year'] = substr($month, 0, 4);
            $show_filters = true;
        }
        # - Lang filter
        if ($lang !== '' && in_array($lang, $lang_combo)) {
            $params['post_lang'] = $lang;
            $show_filters = true;
        }
        # - Sortby and order filter
        if ($sortby !== '' && in_array($sortby, $sortby_combo)) {
            if ($order !== '' && in_array($order, $order_combo)){
                $params['order'] = $sortby.' '.$order;
            }
            if ($sortby != 'post_dt' || $order != 'desc') {
                $show_filters = true;
            }
        }

        # Get posts
        try {
            $params['feed_id'] = $feed_id;
            $posts = $zcfs->getPostsByFeed($params);
            $counter = $zcfs->getPostsByFeed($params,true);
            $post_list = new zcfsEntriesList(
                $core,
                $posts,
                $counter->f(0)
            );
        }
        catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    # Display
    echo 
    '<html><head><title>'.__('Feeds server').'</title>'.
    ($feed_id && !$core->error->flag() ?
        dcPage::jsLoad(
            'index.php?pf=periodical/js/postsfilter.js'
        ).
        '<script type="text/javascript">'."\n".
        "//<![CDATA["."\n".
        dcPage::jsVar(
            'dotclear.msg.show_filters',
            $show_filters ? 'true':'false'
        )."\n".
        dcPage::jsVar(
            'dotclear.msg.filter_posts_list',
            __('Show filters and display options')
        )."\n".
        dcPage::jsVar(
            'dotclear.msg.cancel_the_filter',
            __('Cancel filters and display options')
        )."\n".
        "//]]>\n".
        "</script>\n"
    : '').
    dcPage::jsPageTabs().
    $next_headlink."\n".$prev_headlink.

    # --BEHAVIOR-- packmanAdminHeader
    $core->callBehavior('zcfsAdminHeader', $core).

    '</head><body>'.

    dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Feeds server') => '',
            __('Feeds') => $p_url,
            ($feed_id ? __('Edit feed') : __('New feed')) => ''
        )
    ).
    dcPage::notices() .
    ($feed_id ? '<h3>' . sprintf(__('Edit feed "%s"'), $feed_name) .'</h3>' : '');

    # Feed
    if ($can_view_page) {

        # nav link
        if ($feed_id && ($next_link || $prev_link)) {
            echo '<p>';
            if ($prev_link) {
                echo $prev_link;
            }
            if ($next_link && $prev_link) {
                echo ' - ';
            }
            if ($next_link) {
                echo $next_link;
            }
            echo '</p>';
        }

        echo '
        <div' . ($feed_id ? ' class="multi-part" title="'.__('Feed').'"' : '') . ' id="edit-entry">
        <form method="post" action="plugin.php">

        <div class="two-cols">'.

        '<div class="col70">'.
        '<h4>'.__('Feed information').'</h4>'.

        '<p><label for="feed_name" class="required">
        <abbr title="'.__('Required field').'">*</abbr>'.
        __('Name:').'</label>'.
        form::field('feed_name', 60, 255, $feed_name, 'maximal').
        '</p>'.

        '<p><label for="feed_owner" class="required">
        <abbr title="'.__('Required field').'">*</abbr>'.
        __('Owner:').'</label>'.
        form::field('feed_owner', 60, 255, $feed_owner, 'maximal').
        '</p>'.

        // move this away
        '<p><label for="feed_tweeter">'.
        __('Tweeter or Identica ident:').'</label>'.
        form::field('feed_tweeter', 60, 64, $feed_tweeter, 'maximal').
        '</p>'.

        '<p><label for="feed_url" class="required">
        <abbr title="'.__('Required field').'">*</abbr>'.
        __('Site URL:').'</label>'.
        form::field('feed_url', 60, 255, $feed_url, 'maximal').
        '</p>'.

        '<p><label for="feed_feed" class="required">
        <abbr title="'.__('Required field').'">*</abbr>'.
        __('Feed URL:').'</label>'.
        form::field('feed_feed', 60, 255, $feed_feed, 'maximal').
        '</p>'.

        '<p><label for="feed_desc">'.__('Description:').'</label>'.
        form::field('feed_desc', 60, 255, $feed_desc, 'maximal').
        '</p>'.

        '<p><label for="feed_tags">'.__('Tags:').'</label>'.
        form::field('feed_tags', 60, 255, $feed_tags, 'maximal').
        '</p>'.

        # --BEHAVIOR-- zoneclearFeedServerFeedForm
        $core->callBehavior('zoneclearFeedServerFeedForm', $core, $feed_id).

        '</div>'.

        '<div class="col30">'.
        '<h4>'.__('Local settings').'</h4>'.

        '<p><label for="feed_cat_id">'.__('Category:').'</label>'.
        form::combo('feed_cat_id', $combo_categories, $feed_cat_id, 'maximal').
        '</p>'.

        '<p><label for="feed_status">'.__('Status:').'</label>'.
        form::combo('feed_status', $combo_status, $feed_status, 'maximal').
        '</p>'.

        '<p><label for="feed_upd_int">'.__('Update:').'</label>'.
        form::combo('feed_upd_int', $combo_upd_int, $feed_upd_int, 'maximal').
        '</p>'.

        '<p><label for="feed_lang">'.__('Lang:').'</label>'.
        form::combo('feed_lang', $combo_langs, $feed_lang, 'maximal').
        '</p>'.

        '<p><label for="feed_get_tags" class="classic">'.
        form::checkbox('feed_get_tags', 1, $feed_get_tags).
        __('Import tags from feed').'</label></p>'.

        '</div>'.

        '</div>'.

        '<p class="clear">'.
        form::hidden(array('action'), 'savefeed').
        form::hidden(array('feed_id'), $feed_id).
        form::hidden(array('p'), 'zoneclearFeedServer').
        form::hidden(array('part'), 'feed').
        $core->formNonce().
        '<input type="submit" name="save" value="'.__('Save').'" /></p>
        </form>
        </div>';
    }

    # Entries
    if ($feed_id && $can_view_page && !$core->error->flag()) {
        echo 
        '<div class="multi-part" title="'.__('Entries').'" id="entries">'.

        '<form action="'.$p_url.'&amp;part=feed#entries" method="get" id="filters-form">'.

        '<h3 class="out-of-screen-if-js">'.
        __('Cancel filters and display options').
        '</h3>'.

        '<div class="table">'.
        '<div class="cell">'.
        '<h4>'.__('Filters').'</h4>'.
        '<p><label for="user_id" class="ib">'.__('Author:').'</label> '.
        form::combo('user_id',$users_combo,$user_id).'</p>'.
        '<p><label for="cat_id" class="ib">'.__('Category:').'</label> '.
        form::combo('cat_id',$categories_combo,$cat_id).'</p>'.
        '<p><label for="status" class="ib">'.__('Status:').'</label> ' .
        form::combo('status',$status_combo,$status).'</p> '.
        '</div>'.

        '<div class="cell filters-sibling-cell">'.
        '<p><label for="selected" class="ib">'.__('Selected:').'</label> '.
        form::combo('selected',$selected_combo,$selected).'</p>'.
        '<p><label for="month" class="ib">'.__('Month:').'</label> '.
        form::combo('month',$dt_m_combo,$month).'</p>'.
        '<p><label for="lang" class="ib">'.__('Lang:').'</label> '.
        form::combo('lang',$lang_combo,$lang).'</p> '.
        '</div>'.

        '<div class="cell filters-options">'.
        '<h4>'.__('Display options').'</h4>'.
        '<p><label for="sortby" class="ib">'.__('Order by:').'</label> '.
        form::combo('sortby',$sortby_combo,$sortby).'</p>'.
        '<p><label for="order" class="ib">'.__('Sort:').'</label> '.
        form::combo('order',$order_combo,$order).'</p>'.
        '<p><span class="label ib">'.__('Show').'</span> <label for="nb" class="classic">'.
        form::field('nb', 3, 3, $nb_per_page).' '.
        __('entries per page').'</label></p>'.
        '</div>'.
        '</div>'.

        '<p><input type="submit" value="'.__('Apply filters and display options').'" />'.
        form::hidden(array('p'), 'zoneclearFeedServer').
        form::hidden(array('part'), 'feed').
        form::hidden(array('feed_id') ,$feed_id).
        '<br class="clear" />'. //Opera sucks
        '</p>'.
        '</form>'.

        # Show posts
        $post_list->display($page, $nb_per_page,

            $p_url.
            '&amp;part=feed'.
            '&amp;tab=entries'.
            '&amp;feed_id='.$feed_id.
            '&amp;user_id='.$user_id.
            '&amp;cat_id='.$cat_id.
            '&amp;status='.$status.
            '&amp;selected='.$selected.
            '&amp;month='.$month.
            '&amp;lang='.$lang.
            '&amp;sortby='.$sortby.
            '&amp;order='.$order.
            '&amp;nb='.$nb_per_page.
            '&amp;page=%s',

            '<form action="'.$p_url.'&amp;part=feed#entries" method="post" id="form-entries">'.
            '%s'.

            '<div class="two-cols">'.
            '<p class="col checkboxes-helpers"></p>'.

            '<p class="col right">'.__('Selected entries action:').' '.
            form::combo('action', $posts_actions_page->getCombo()).
            '<input type="submit" name="save" value="'.__('ok').'" /></p>'.
            form::hidden(array('part'), 'feed').
            form::hidden(array('feed_id'), $feed_id).
            form::hidden(array('user_id'), $user_id).
            form::hidden(array('cat_id'), $cat_id).
            form::hidden(array('status'), $status).
            form::hidden(array('selected'), $selected).
            form::hidden(array('month'), $month).
            form::hidden(array('lang'), $lang).
            form::hidden(array('sortby'), $sortby).
            form::hidden(array('order'), $order).
            form::hidden(array('page'), $page).
            form::hidden(array('nb'), $nb_per_page).
            $core->formNonce().
            '</div>'.
            '</form>'
        );

        echo 
        '</div>';
    }
}

############################################################
#
# All feeds
#
############################################################

else {

    # Actions page
    $feeds_actions_page = new zcfsFeedsActionsPage(
        $core,
        'plugin.php',
        array('p' => 'zoneclearFeedServer', 'part' => 'feeds')
    );

    if ($feeds_actions_page->process()) {
        return null;
    }

    $feeds_filter = new adminGenericFilter($core, 'zcfs_feeds');
    $feeds_filter->add('part', 'feeds');
    $feeds_filter->add(dcAdminFilters::getPageFilter());
    $params = $feeds_filter->params();

    try {
        $feeds = $zcfs->getFeeds($params);
        $feeds_counter = $zcfs->getFeeds($params, true)->f(0);
        $feeds_list = new zcfsFeedsList(
            $core,
            $feeds,
            $feeds_counter
        );
    }
    catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    # Display
    echo 
    '<html><head><title>'.__('Feeds server').'</title>'.
    dcPage::jsVars(['dotclear.filter_reset_url' => $core->adminurl->get('admin.plugin.zoneclearFeedServer', ['part' => 'feeds'])]) .
    dcPage::jsFilterControl($feeds_filter->show()) .
    dcPage::jsLoad(dcPage::getPF('zoneclearFeedServer/js/feedsfilter.js')) .
    dcPage::jsPageTabs().

    # --BEHAVIOR-- packmanAdminHeader
    $core->callBehavior('zcfsAdminHeader', $core).

    '</head><body>'.

    dcPage::breadcrumb(
        array(
            html::escapeHTML($core->blog->name) => '',
            __('Feeds server') => '',
            __('Feeds') => ''
        )
    ).
    dcPage::notices().

    '<p class="top-add">'.
    '<a class="button add" href="'.$p_url.'&amp;part=feed">'.
    __('New feed').'</a></p>';

    $feeds_filter->display('admin.plugin.zoneclearFeedServer', form::hidden('p', 'zoneclearFeedServer') . form::hidden('part', 'feeds'));

    $feeds_list->feedsDisplay($feeds_filter->page, $feeds_filter->nb, 
        '<form action="'.$p_url.'&amp;part=feeds" method="post" id="form-actions">'.
        '%s'.
        '<div class="two-cols">'.
        '<p class="col checkboxes-helpers"></p>'.
        '<p class="col right">'.__('Selected feeds action:').' '.
        form::combo(['action'], $feeds_actions_page->getCombo()).
        '<input type="submit" value="'.__('ok').'" />'.
        $core->adminurl->getHiddenFormFields('admin.plugin.zoneclearFeedServer', $feeds_filter->values(true)) .
        $core->formNonce().
        '</p>'.
        '</div>'.
        '</form>',
        false
    );
}

echo '</body></html>';