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

try {
    // Check module version
    if (!dcCore::app()->newVersion(
        basename(__DIR__),
        dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
    )) {
        return null;
    }

    // Upgrade existing install
    zcfsUpgrade::preUpgrade();

    // Tables
    $t = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $t->{initZoneclearFeedServer::TABLE_NAME}
        ->feed_id('bigint', 0, false)
        ->feed_creadt('timestamp', 0, false, 'now()')
        ->feed_upddt('timestamp', 0, false, 'now()')
        ->feed_type('varchar', 32, false, "'feed'")
        ->blog_id('varchar', 32, false)
        ->cat_id('bigint', 0, true)
        ->feed_upd_int('integer', 0, false, 3600)
        ->feed_upd_last('integer', 0, false, 0)
        ->feed_status('smallint', 0, false, 0)
        ->feed_name('varchar', 255, false)
        ->feed_desc('text', null, true) //!pgsql reserved 'desc'
        ->feed_url('varchar', 255, false)
        ->feed_feed('varchar', 255, false)
        ->feed_tags('varchar', 255, true)
        ->feed_get_tags('smallint', 0, false, 1)
        ->feed_owner('varchar', 255, false)
        ->feed_tweeter('varchar', 64, false) // tweeter ident
        ->feed_lang('varchar', 5, true)
        ->feed_nb_out('integer', 0, false, 0)
        ->feed_nb_in('integer', 0, false, 0)

        ->primary('pk_zcfs', 'feed_id')
        ->index('idx_zcfs_type', 'btree', 'feed_type')
        ->index('idx_zcfs_blog', 'btree', 'blog_id');

    $ti      = new dbStruct(dcCore::app()->con, dcCore::app()->prefix);
    $changes = $ti->synchronize($t);

    // Settings
    dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
    $s = dcCore::app()->blog->settings->__get(basename(__DIR__));
    $s->put('active', false, 'boolean', 'Enable zoneclearBlogServer', false, true);
    $s->put('pub_active', false, 'boolean', 'Enable public page of list of feeds', false, true);
    $s->put('post_status_new', true, 'boolean', 'Enable auto publish new posts', false, true);
    $s->put('bhv_pub_upd', 2, 'string', 'Auto update on public side (disable/before/after)', false, true);
    $s->put('update_limit', 1, 'integer', 'Number of feeds to update at one time', false, true);
    $s->put('keep_empty_feed', false, 'boolean', 'Keep active empty feeds', false, true);
    $s->put('tag_case', 0, 'integer', 'How to transform imported tags', false, true);
    $s->put('user', '', 'string', 'User id that has right on post', false, true);
    $s->put('post_full_tpl', json_encode(['post', 'category', 'tag', 'archive']), 'string', 'List of templates types for full feed', false, true);
    $s->put('post_title_redir', json_encode(['feed']), 'string', 'List of templates types for redirection to original post', false, true);

    return true;
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());

    return false;
}
