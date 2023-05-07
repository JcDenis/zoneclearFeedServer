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

$redir = empty($_REQUEST['redir']) ? dcCore::app()->admin->list->getURL() . '#plugins' : $_REQUEST['redir'];

# -- Get settings --
dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
$s = dcCore::app()->blog->settings->__get(basename(__DIR__));

$active           = (bool) $s->active;
$pub_active       = (bool) $s->pub_active;
$post_status_new  = (bool) $s->post_status_new;
$bhv_pub_upd      = (int) $s->bhv_pub_upd;
$update_limit     = (int) $s->update_limit;
$keep_empty_feed  = (bool) $s->keep_empty_feed;
$tag_case         = (int) $s->tag_case;
$post_full_tpl    = json_decode($s->post_full_tpl);
$post_title_redir = json_decode($s->post_title_redir);
$feeduser         = (string) $s->user;

if ($update_limit < 1) {
    $update_limit = 10;
}
if (!is_array($post_full_tpl)) {
    $post_full_tpl = [];
}
if (!is_array($post_title_redir)) {
    $post_title_redir = [];
}

$zc = new zoneclearFeedServer();

# -- Set settings --
if (!empty($_POST['save'])) {
    try {
        $active           = !empty($_POST['active']);
        $pub_active       = !empty($_POST['pub_active']);
        $post_status_new  = !empty($_POST['post_status_new']);
        $bhv_pub_upd      = (int) $_POST['bhv_pub_upd'];
        $limit            = abs((int) $_POST['update_limit']);
        $keep_empty_feed  = !empty($_POST['keep_empty_feed']);
        $tag_case         = (int) $_POST['tag_case'];
        $post_full_tpl    = $_POST['post_full_tpl'];
        $post_title_redir = $_POST['post_title_redir'];
        $feeduser         = (string) $_POST['feeduser'];

        if ($limit < 1) {
            $limit = 10;
        }

        $s->put('active', $active);
        $s->put('pub_active', $pub_active);
        $s->put('post_status_new', $post_status_new);
        $s->put('bhv_pub_upd', $bhv_pub_upd);
        $s->put('update_limit', $limit);
        $s->put('keep_empty_feed', $keep_empty_feed);
        $s->put('tag_case', $tag_case);
        $s->put('post_full_tpl', json_encode($post_full_tpl));
        $s->put('post_title_redir', json_encode($post_title_redir));
        $s->put('user', $feeduser);

        dcCore::app()->blog->triggerBlog();

        dcAdminNotices::addSuccessNotice(
            __('Configuration successfully updated.')
        );
        dcCore::app()->adminurl->redirect(
            'admin.plugins',
            ['module' => basename(__DIR__), 'conf' => 1, 'redir' => dcCore::app()->admin->list->getRedir()]
        );
    } catch (Exception $e) {
        dcCore::app()->error->add($e->getMessage());
    }
}

# -- Form combos --
$combo_admins = $zc->getAllBlogAdmins();
$combo_pubupd = [
    __('Disable')        => 0,
    __('Before display') => 1,
    __('After display')  => 2,
    __('Through Ajax')   => 3,
];
$combo_status = [
    __('Unpublished') => 0,
    __('Published')   => 1,
];
$combo_tagcase = [
    __('Keep source case') => 0,
    __('First upper case') => 1,
    __('All lower case')   => 2,
    __('All upper case')   => 3,
];

$pub_page_url = dcCore::app()->blog->url . dcCore::app()->url->getBase('zoneclearFeedsPage');

# -- Display form --

if (!is_writable(DC_TPL_CACHE)) {
    echo '<p class="error">' . __('Dotclear cache is not writable or not well configured!') . '</p>';
}

echo '
<div class="fieldset">
<h4>' . __('Activation') . '</h4>

<p><label for="active">' .
form::checkbox('active', 1, $active) .
__('Enable plugin') . '</label></p>
</div>

<div class="fieldset">';

if ($s->pub_active) {
    echo sprintf(
        '<p><a class="onblog_link outgoing" href="%s" title="%s">%s <img alt="" src="images/outgoing-link.svg"></a></p>',
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

<p><label for="feeduser">' .
__('Owner of entries created by zoneclearFeedServer:') . '</label>' .
form::combo('feeduser', $combo_admins, $feeduser) . '</p>

<p><label for="tag_case">' . __('How to transform imported tags:') . '</label>' .
form::combo('tag_case', $combo_tagcase, $tag_case) . '</p>

</div><div class="two-boxes">

<p><label for="bhv_pub_upd">' . __('Update feeds on public side:') . '</label>' .
form::combo('bhv_pub_upd', $combo_pubupd, $bhv_pub_upd) . '</p>

<p class="classic"><label for="update_limit" class="ib">' . sprintf(
    __('Update %s feed(s) at a time.'),
    form::number('update_limit', ['min' => 0, 'max' => 20, 'default' => $update_limit])
) . '</label></p>

<p><label for="keep_empty_feed">' .
form::checkbox('keep_empty_feed', 1, $keep_empty_feed) . __('Keep active empty feeds') . '</label></p>

<p><label for="pub_active">' .
form::checkbox('pub_active', 1, $pub_active) . __('Enable public page') . '</label></p>

</div><div class="two-boxes">

<p>' . __('Redirect to original post on:') . '</p><ul>';

foreach ($zc->getPublicUrlTypes() as $k => $v) {
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

foreach ($zc->getPublicUrlTypes() as $k => $v) {
    echo sprintf(
        '<li><label for="post_full_tpl_%s">%s%s</label></li>',
        $v,
        form::checkbox(['post_full_tpl[]', 'post_full_tpl_' . $v], $v, in_array($v, $post_full_tpl)),
        __($k)
    );
}

echo '</ul></div></div>';

dcPage::helpBlock('zoneclearFeedServer');
