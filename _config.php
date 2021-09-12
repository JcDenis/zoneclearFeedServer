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

if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

$redir = empty($_REQUEST['redir']) ? $list->getURL() . '#plugins' : $_REQUEST['redir'];

# -- Get settings --
$core->blog->settings->addNamespace('zoneclearFeedServer');
$s = $core->blog->settings->zoneclearFeedServer;

$active = (boolean) $s->zoneclearFeedServer_active;
$pub_active = (boolean) $s->zoneclearFeedServer_pub_active;
$post_status_new = (boolean) $s->zoneclearFeedServer_post_status_new;
$bhv_pub_upd = (integer) $s->zoneclearFeedServer_bhv_pub_upd;
$update_limit = (integer) $s->zoneclearFeedServer_update_limit;
$keep_empty_feed = (boolean) $s->zoneclearFeedServer_keep_empty_feed;
$tag_case = (integer) $s->zoneclearFeedServer_tag_case;
$post_full_tpl = @unserialize($s->zoneclearFeedServer_post_full_tpl);
$post_title_redir = @unserialize($s->zoneclearFeedServer_post_title_redir);
$feeduser = (string) $s->zoneclearFeedServer_user;

if ($update_limit < 1) {
    $update_limit = 10;
}
if (!is_array($post_full_tpl)) {
    $post_full_tpl = [];
}
if (!is_array($post_title_redir)) {
    $post_title_redir = [];
}

$zc = new zoneclearFeedServer($core);

# -- Set settings --
if (!empty($_POST['save'])) {
    try {
        $active = !empty($_POST['active']);
        $pub_active = !empty($_POST['pub_active']);
        $post_status_new = !empty($_POST['post_status_new']);
        $bhv_pub_upd = (integer) $_POST['bhv_pub_upd'];
        $limit = abs((integer) $_POST['update_limit']);
        $keep_empty_feed = !empty($_POST['keep_empty_feed']);
        $tag_case = (integer) $_POST['tag_case'];
        $post_full_tpl = $_POST['post_full_tpl'];
        $post_title_redir = $_POST['post_title_redir'];
        $feeduser = (string) $_POST['feeduser'];

        if ($limit < 1) {
            $limit = 10;
        }

        $s->put('zoneclearFeedServer_active', $active);
        $s->put('zoneclearFeedServer_pub_active', $pub_active);
        $s->put('zoneclearFeedServer_post_status_new', $post_status_new);
        $s->put('zoneclearFeedServer_bhv_pub_upd', $bhv_pub_upd);
        $s->put('zoneclearFeedServer_update_limit', $limit);
        $s->put('zoneclearFeedServer_keep_empty_feed', $keep_empty_feed);
        $s->put('zoneclearFeedServer_tag_case', $tag_case);
        $s->put('zoneclearFeedServer_post_full_tpl', serialize($post_full_tpl));
        $s->put('zoneclearFeedServer_post_title_redir', serialize($post_title_redir));
        $s->put('zoneclearFeedServer_user', $feeduser);

        $core->blog->triggerBlog();

        dcPage::addSuccessNotice(
            __('Configuration successfully updated.')
        );
        $core->adminurl->redirect(
            'admin.plugins', 
            ['module' => 'zoneclearFeedServer', 'conf' => 1, 'redir' => $list->getRedir()]
        );
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

# -- Form combos --
$combo_admins = $zc->getAllBlogAdmins();
$combo_pubupd = [
    __('Disable')        => 0,
    __('Before display') => 1,
    __('After display')  => 2,
    __('Through Ajax')   => 3
];
$combo_status = [
    __('Unpublished') => 0,
    __('Published')   => 1
];
$combo_tagcase = [
    __('Keep source case') => 0,
    __('First upper case') => 1,
    __('All lower case')   => 2,
    __('All upper case')   => 3
];

$pub_page_url = $core->blog->url.$core->url->getBase('zoneclearFeedsPage');

# -- Display form --

if (!is_writable(DC_TPL_CACHE)) {
    echo '<p class="error">'. __('Dotclear cache is not writable or not well configured!') . '</p>';
    }

echo '
<div class="fieldset">
<h4>' . __('Activation') . '</h4>

<p><label for="active">' .
form::checkbox('active', 1, $active) .
__('Enable plugin') . '</label></p>
</div>

<div class="fieldset">';

if ($core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_pub_active) {
    echo sprintf(
        '<p><a class="onblog_link" href="%s" title="%s">%s</a></p>',
        $pub_page_url,
        $pub_page_url,
        __('View the public list of feeds')
    );
}

echo '
<h4>' . __('Rules') . '</h4>

<div class="two-boxes">

<p><label for="post_status_new">' . __('Status of new posts:') . '</label>' .
form::combo('post_status_new', $combo_status, $post_status_new) . '</p>

<p><label for="feeduser">'.
__('Owner of entries created by zoneclearFeedServer:') . '</label>' .
form::combo('feeduser', $combo_admins, $feeduser) . '</p>

<p><label for="tag_case">' . __('How to transform imported tags:') . '</label>' .
form::combo('tag_case', $combo_tagcase, $tag_case) . '</p>

</div><div class="two-boxes">

<p><label for="bhv_pub_upd">' . __('Update feeds on public side:') . '</label>' .
form::combo('bhv_pub_upd', $combo_pubupd, $bhv_pub_upd) . '</p>

<p><label for="update_limit">' . __('Number of feeds to update at one time:') . '</label>' .
form::field('update_limit', 6, 4, $update_limit) . '</p>

<p><label for="keep_empty_feed">' .
form::checkbox('keep_empty_feed', 1, $keep_empty_feed) . __('Keep active empty feeds') . '</label></p>

<p><label for="pub_active">' .
form::checkbox('pub_active', 1, $pub_active) . __('Enable public page') . '</label></p>

</div><div class="two-boxes">

<p>' . __('Redirect to original post on:') . '</p><ul>';

foreach($zc->getPublicUrlTypes($core) as $k => $v) {
    echo sprintf(
        '<li><label for="post_title_redir_%s">%s%s</label></li>',
        $v,
        form::checkbox(['post_title_redir[]', 'post_title_redir_' . $v], $v, in_array($v, $post_title_redir)),
        __($k)
    );
}
echo '
</ul>

</div><div class="two-boxes">

<p>' . __('Show full content on:') . '</p><ul>';

foreach($zc->getPublicUrlTypes($core) as $k => $v) {
    echo sprintf(
        '<li><label for="post_full_tpl_%s">%s%s</label></li>',
        $v,
        form::checkbox(['post_full_tpl[]', 'post_full_tpl_' . $v], $v, in_array($v, $post_full_tpl)),
        __($k)
    );
}

echo '</ul></div></div>';

dcPage::helpBlock('zoneclearFeedServer');