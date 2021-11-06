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

if ($core->getVersion('zoneclearFeedServer') != $core->plugins->moduleInfo('zoneclearFeedServer', 'version')
) {
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
    $feed_id       = '';
    $feed_name     = '';
    $feed_desc     = '';
    $feed_owner    = '';
    $feed_tweeter  = '';
    $feed_url      = '';
    $feed_feed     = '';
    $feed_lang     = $core->auth->getInfo('user_lang');
    $feed_tags     = '';
    $feed_get_tags = '0';
    $feed_cat_id   = '';
    $feed_status   = '0';
    $feed_upd_int  = 3600;

    $can_view_page = true;

    $feed_headlink = '<link rel="%s" title="%s" href="' . $p_url . '&amp;part=feed&amp;feed_id=%s" />';
    $feed_link     = '<a href="' . $p_url . '&amp;part=feed&amp;feed_id=%s" title="%s">%s</a>';

    $next_link = $prev_link = $next_headlink = $prev_headlink = null;

    # Combos
    $combo_langs      = l10n::getISOcodes(true);
    $combo_status     = $zcfs->getAllStatus();
    $combo_upd_int    = $zcfs->getAllUpdateInterval();
    $combo_categories = ['-' => ''];

    try {
        $categories = $core->blog->getCategories(['post_type' => 'post']);
        while ($categories->fetch()) {
            $combo_categories[
                str_repeat('&nbsp;&nbsp;', $categories->level - 1) .
                '&bull; ' . html::escapeHTML($categories->cat_title)
            ] = $categories->cat_id;
        }
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    # Get entry informations
    if (!empty($_REQUEST['feed_id'])) {
        $feed = $zcfs->getFeeds(['feed_id' => $_REQUEST['feed_id']]);

        if ($feed->isEmpty()) {
            $core->error->add(__('This feed does not exist.'));
            $can_view_page = false;
        } else {
            $feed_id       = $feed->feed_id;
            $feed_name     = $feed->feed_name;
            $feed_desc     = $feed->feed_desc;
            $feed_owner    = $feed->feed_owner;
            $feed_tweeter  = $feed->feed_tweeter;
            $feed_url      = $feed->feed_url;
            $feed_feed     = $feed->feed_feed;
            $feed_lang     = $feed->feed_lang;
            $feed_tags     = $feed->feed_tags;
            $feed_get_tags = $feed->feed_get_tags;
            $feed_cat_id   = $feed->cat_id;
            $feed_status   = $feed->feed_status;
            $feed_upd_int  = $feed->feed_upd_int;

            $next_params = [
                'sql'   => 'AND feed_id < ' . $feed_id . ' ',
                'limit' => 1
            ];
            $next_rs     = $zcfs->getFeeds($next_params);
            $prev_params = [
                'sql'   => 'AND feed_id > ' . $feed_id . ' ',
                'limit' => 1
            ];
            $prev_rs = $zcfs->getFeeds($prev_params);

            if (!$next_rs->isEmpty()) {
                $next_link = sprintf(
                    $feed_link,
                    $next_rs->feed_id,
                    html::escapeHTML($next_rs->feed_name),
                    __('next feed') . '&nbsp;&#187;'
                );
                $next_headlink = sprintf(
                    $feed_headlink,
                    'next',
                    html::escapeHTML($next_rs->feed_name),
                    $next_rs->feed_id
                );
            }

            if (!$prev_rs->isEmpty()) {
                $prev_link = sprintf(
                    $feed_link,
                    $prev_rs->feed_id,
                    html::escapeHTML($prev_rs->feed_name),
                    '&#171;&nbsp;' . __('previous feed')
                );
                $prev_headlink = sprintf(
                    $feed_headlink,
                    'previous',
                    html::escapeHTML($prev_rs->feed_name),
                    $prev_rs->feed_id
                );
            }
        }
    }

    if (!empty($_POST['action']) && $_POST['action'] == 'savefeed') {
        try {
            $feed_name     = $_POST['feed_name'];
            $feed_desc     = $_POST['feed_desc'];
            $feed_owner    = $_POST['feed_owner'];
            $feed_tweeter  = $_POST['feed_tweeter'];
            $feed_url      = $_POST['feed_url'];
            $feed_feed     = $_POST['feed_feed'];
            $feed_lang     = $_POST['feed_lang'];
            $feed_tags     = $_POST['feed_tags'];
            $feed_get_tags = empty($_POST['feed_get_tags']) ? 0 : 1;
            $feed_cat_id   = $_POST['feed_cat_id'];
            $feed_upd_int  = $_POST['feed_upd_int'];
            if (isset($_POST['feed_status'])) {
                $feed_status = (int) $_POST['feed_status'];
            }

            $testfeed_params['feed_feed'] = $feed_feed;
            if ($feed_id) {
                $testfeed_params['sql'] = 'AND feed_id <> ' . $feed_id . ' ';
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
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    if (!empty($_POST['action']) && $_POST['action'] == 'savefeed' && !$core->error->flag()) {
        $cur                = $zcfs->openCursor();
        $cur->feed_name     = $feed_name;
        $cur->feed_desc     = $feed_desc;
        $cur->feed_owner    = $feed_owner;
        $cur->feed_tweeter  = $feed_tweeter;
        $cur->feed_url      = $feed_url;
        $cur->feed_feed     = $feed_feed;
        $cur->feed_lang     = $feed_lang;
        $cur->feed_tags     = $feed_tags;
        $cur->feed_get_tags = (int) $feed_get_tags;
        $cur->cat_id        = $feed_cat_id != '' ? (int) $feed_cat_id : null;
        $cur->feed_status   = (int) $feed_status;
        $cur->feed_upd_int  = (int) $feed_upd_int;

        # Update feed
        if ($feed_id) {
            try {
                # --BEHAVIOR-- adminBeforeZoneclearFeedServerFeedUpdate
                $core->callBehavior('adminBeforeZoneclearFeedServerFeedUpdate', $cur, $feed_id);

                $zcfs->updFeed($feed_id, $cur);

                # --BEHAVIOR-- adminAfterZoneclearFeedServerFeedUpdate
                $core->callBehavior('adminAfterZoneclearFeedServerFeedUpdate', $cur, $feed_id);

                dcPage::addSuccessNotice(
                    __('Feed successfully updated.')
                );
                $core->adminurl->redirect(
                    'admin.plugin.zoneclearFeedServer',
                    ['part' => 'feed', 'feed_id' => $feed_id]
                );
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        } else {
            try {
                # --BEHAVIOR-- adminBeforeZoneclearFeedServerFeedCreate
                $core->callBehavior('adminBeforeZoneclearFeedServerFeedCreate', $cur);

                $return_id = $zcfs->addFeed($cur);

                # --BEHAVIOR-- adminAfterZoneclearFeedServerFeedCreate
                $core->callBehavior('adminAfterZoneclearFeedServerFeedCreate', $cur, $return_id);

                dcPage::addSuccessNotice(
                    __('Feed successfully created.')
                );
                $core->adminurl->redirect(
                    'admin.plugin.zoneclearFeedServer',
                    ['part' => 'feed', 'feed_id' => $return_id]
                );
            } catch (Exception $e) {
                $core->error->add($e->getMessage());
            }
        }
    }

    # Prepared entries list
    if ($feed_id && $can_view_page) {
        # action
        $posts_actions_page = new dcPostsActionsPage(
            $core,
            'plugin.php',
            [
                'p'       => 'zoneclearFeedServer',
                'part'    => 'feed',
                'feed_id' => $feed_id,
                '_ANCHOR' => 'entries'
            ]
        );
        if ($posts_actions_page->process()) {
            return null;
        }

        # filters
        $post_filter = new adminZcfsPostFilter($core);
        $post_filter->add('part', 'feed');
        $post_filter->add('feed_id', $feed_id);
        $params = $post_filter->params();

        # lexical sort
        $sortby_lex = [
            // key in sorty_combo (see above) => field in SQL request
            'post_title' => 'post_title',
            'cat_title'  => 'cat_title',
            'user_id'    => 'P.user_id'];

        # --BEHAVIOR-- adminPostsSortbyLexCombo
        $core->callBehavior('adminPostsSortbyLexCombo', [& $sortby_lex]);

        $params['no_content'] = true;
        $params['feed_id']    = $feed_id;
        $params['order']      = (array_key_exists($post_filter->sortby, $sortby_lex) ?
            $core->con->lexFields($sortby_lex[$post_filter->sortby]) :
            $post_filter->sortby) . ' ' . $post_filter->order;

        # posts
        try {
            $posts     = $zcfs->getPostsByFeed($params);
            $counter   = $zcfs->getPostsByFeed($params, true);
            $post_list = new zcfsEntriesList(
                $core,
                $posts,
                $counter->f(0)
            );
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }

    # display
    echo
    '<html><head><title>' . __('Feeds server') . '</title>' .
    ($feed_id && !$core->error->flag() ?
        $post_filter->js($core->adminurl->get('admin.plugin.zoneclearFeedServer', ['part' => 'feed', 'feed_id' => $feed_id], '&') . '#entries') .
        dcPage::jsLoad(dcPage::getPF('zoneclearFeedServer/js/list.js'))
    : '') .
    dcPage::jsPageTabs() .
    $next_headlink . "\n" . $prev_headlink .

    # --BEHAVIOR-- adminZoneclearFeedServerHeader
    $core->callBehavior('adminZoneclearFeedServerHeader', $core) .

    '</head><body>' .

    dcPage::breadcrumb([
        __('Plugins')                                 => '',
        __('Feeds server')                            => $p_url,
        ($feed_id ? __('Edit feed') : __('New feed')) => ''
    ]) .
    dcPage::notices() .
    ($feed_id ? '<h3>' . sprintf(__('Edit feed "%s"'), $feed_name) . '</h3>' : '');

    # Feed
    if ($can_view_page) {
        # nav link
        if ($feed_id && ($next_link || $prev_link)) {
            echo '<p class="nav_prevnext">';
            if ($prev_link) {
                echo $prev_link;
            }
            if ($next_link && $prev_link) {
                echo ' | ';
            }
            if ($next_link) {
                echo $next_link;
            }
            echo '</p>';
        }

        echo '
        <div' . ($feed_id ? ' class="multi-part" title="' . __('Feed') . '"' : '') . ' id="edit-entry">
        <form method="post" action="plugin.php">

        <div class="two-cols">' .

        '<div class="col70">' .
        '<h4>' . __('Feed information') . '</h4>' .

        '<p><label for="feed_name" class="required">
        <abbr title="' . __('Required field') . '">*</abbr>' .
        __('Name:') . '</label>' .
        form::field('feed_name', 60, 255, $feed_name, 'maximal') .
        '</p>' .

        '<p><label for="feed_owner" class="required">
        <abbr title="' . __('Required field') . '">*</abbr>' .
        __('Owner:') . '</label>' .
        form::field('feed_owner', 60, 255, $feed_owner, 'maximal') .
        '</p>' .

        // move this away
        '<p><label for="feed_tweeter">' .
        __('Tweeter or Identica ident:') . '</label>' .
        form::field('feed_tweeter', 60, 64, $feed_tweeter, 'maximal') .
        '</p>' .

        '<p><label for="feed_url" class="required">
        <abbr title="' . __('Required field') . '">*</abbr>' .
        __('Site URL:') . '</label>' .
        form::field('feed_url', 60, 255, $feed_url, 'maximal') .
        '</p>' .

        '<p><label for="feed_feed" class="required">
        <abbr title="' . __('Required field') . '">*</abbr>' .
        __('Feed URL:') . '</label>' .
        form::field('feed_feed', 60, 255, $feed_feed, 'maximal') .
        '</p>' .

        '<p><label for="feed_desc">' . __('Description:') . '</label>' .
        form::field('feed_desc', 60, 255, $feed_desc, 'maximal') .
        '</p>' .

        '<p><label for="feed_tags">' . __('Tags:') . '</label>' .
        form::field('feed_tags', 60, 255, $feed_tags, 'maximal') .
        '</p>' .

        # --BEHAVIOR-- adminZoneclearFeedServerFeedForm
        $core->callBehavior('adminZoneclearFeedServerFeedForm', $core, $feed_id) .

        '</div>' .

        '<div class="col30">' .
        '<h4>' . __('Local settings') . '</h4>' .

        '<p><label for="feed_cat_id">' . __('Category:') . '</label>' .
        form::combo('feed_cat_id', $combo_categories, $feed_cat_id, 'maximal') .
        '</p>' .

        '<p><label for="feed_status">' . __('Status:') . '</label>' .
        form::combo('feed_status', $combo_status, $feed_status, 'maximal') .
        '</p>' .

        '<p><label for="feed_upd_int">' . __('Update:') . '</label>' .
        form::combo('feed_upd_int', $combo_upd_int, $feed_upd_int, 'maximal') .
        '</p>' .

        '<p><label for="feed_lang">' . __('Lang:') . '</label>' .
        form::combo('feed_lang', $combo_langs, $feed_lang, 'maximal') .
        '</p>' .

        '<p><label for="feed_get_tags" class="classic">' .
        form::checkbox('feed_get_tags', 1, $feed_get_tags) .
        __('Import tags from feed') . '</label></p>' .

        '</div>' .

        '</div>' .

        '<p class="clear">
        <input type="submit" name="save" value="' . __('Save') . ' (s)" accesskey="s"/>' .
        $core->adminurl->getHiddenFormFields('admin.plugin.zoneclearFeedServer', [
            'part'    => 'feed',
            'feed_id' => $feed_id,
            'action'  => 'savefeed'
        ]) .
        $core->formNonce() .
        '</p>
        </form>
        </div>';
    }

    # entries
    if ($feed_id && $can_view_page && !$core->error->flag()) {
        echo '<div class="multi-part" title="' . __('Entries') . '" id="entries">';

        # show filters
        $post_filter->display(
            ['admin.plugin.zoneclearFeedServer','#entries'],
            $core->adminurl->getHiddenFormFields('admin.plugin.zoneclearFeedServer', [
                'part'    => 'feed',
                'feed_id' => $feed_id
            ])
        );

        # fix pager url
        $args = $post_filter->values();
        unset($args['page']);
        $args['page'] = '%s';

        # show posts
        $post_list->display(
            $post_filter->page,
            $post_filter->nb,
            $core->adminurl->get('admin.plugin.zoneclearFeedServer', $args, '&') . '#entries',
            '<form action="' . $core->adminurl->get('admin.plugin.zoneclearFeedServer', ['part' => 'feed']) . '#entries" method="post" id="form-entries">' .
            '%s' .

            '<div class="two-cols">' .
            '<p class="col checkboxes-helpers"></p>' .

            '<p class="col right">' . __('Selected entries action:') . ' ' .
            form::combo('action', $posts_actions_page->getCombo()) .
            '<input type="submit" name="save" value="' . __('ok') . '" /></p>' .
            $core->adminurl->getHiddenFormFields('admin.plugin.zoneclearFeedServer', $post_filter->values()) .
            form::hidden('redir', $core->adminurl->get('admin.plugin.zoneclearFeedServer', $post_filter->values())) .
            $core->formNonce() .
            '</div>' .
            '</form>',
            $post_filter->show()
        );

        echo '</div>';
    }

    ############################################################
#
# All feeds
#
############################################################
} else {
    # actions
    $feeds_actions_page = new zcfsFeedsActionsPage(
        $core,
        'plugin.php',
        ['p' => 'zoneclearFeedServer', 'part' => 'feeds']
    );
    if ($feeds_actions_page->process()) {
        return null;
    }

    # filters
    $feeds_filter = new adminGenericFilter($core, 'zcfs_feeds');
    $feeds_filter->add('part', 'feeds');
    $feeds_filter->add(dcAdminFilters::getPageFilter());
    $feeds_filter->add(dcAdminFilters::getSearchFilter());
    $params = $feeds_filter->params();

    # feeds
    try {
        $feeds         = $zcfs->getFeeds($params);
        $feeds_counter = $zcfs->getFeeds($params, true)->f(0);
        $feeds_list    = new zcfsFeedsList(
            $core,
            $feeds,
            $feeds_counter
        );
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }

    # display
    echo
    '<html><head><title>' . __('Feeds server') . '</title>' .
    $feeds_filter->js($core->adminurl->get('admin.plugin.zoneclearFeedServer', ['part' => 'feeds'], '&')) .
    dcPage::jsLoad(dcPage::getPF('zoneclearFeedServer/js/list.js')) .
    dcPage::jsPageTabs() .

    # --BEHAVIOR-- adminZoneclearFeedServerHeader
    $core->callBehavior('adminZoneclearFeedServerHeader', $core) .

    '</head><body>' .

    dcPage::breadcrumb([
        __('Plugins')      => '',
        __('Feeds server') => ''
    ]) .
    dcPage::notices() .

    '<p class="top-add">' .
    '<a class="button add" href="' . $core->adminurl->get('admin.plugin.zoneclearFeedServer', ['part' => 'feed']) . '">' .
    __('New feed') . '</a></p>';

    $feeds_filter->display(
        'admin.plugin.zoneclearFeedServer',
        $core->adminurl->getHiddenFormFields('admin.plugin.zoneclearFeedServer', ['part', 'feeds'])
    );

    $feeds_list->feedsDisplay(
        $feeds_filter->page,
        $feeds_filter->nb,
        '<form action="' . $core->adminurl->get('admin.plugin.zoneclearFeedServer', ['part', 'feeds']) . '" method="post" id="form-actions">' .
        '%s' .
        '<div class="two-cols">' .
        '<p class="col checkboxes-helpers"></p>' .
        '<p class="col right">' . __('Selected feeds action:') . ' ' .
        form::combo(['action'], $feeds_actions_page->getCombo()) .
        '<input type="submit" value="' . __('ok') . '" />' .
        $core->adminurl->getHiddenFormFields('admin.plugin.zoneclearFeedServer', $feeds_filter->values(true)) .
        $core->formNonce() .
        '</p>' .
        '</div>' .
        '</form>',
        $feeds_filter->show()
    );
}

echo '</body></html>';
