<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Database\Structure;
use Exception;

/**
 * @brief       zoneclearFeedServer install class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Install extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            // Upgrade existing install
            Upgrade::preUpgrade();

            // Tables
            $s = new Structure(App::con(), App::con()->prefix());
            $s->__get(My::TABLE_NAME)
                ->field('feed_id', 'bigint', 0, false)
                ->field('feed_creadt', 'timestamp', 0, false, 'now()')
                ->field('feed_upddt', 'timestamp', 0, false, 'now()')
                ->field('feed_type', 'varchar', 32, false, "'feed'")
                ->field('blog_id', 'varchar', 32, false)
                ->field('cat_id', 'bigint', 0, true)
                ->field('feed_upd_int', 'integer', 0, false, 3600)
                ->field('feed_upd_last', 'integer', 0, false, 0)
                ->field('feed_status', 'smallint', 0, false, 0)
                ->field('feed_name', 'varchar', 255, false)
                ->field('feed_desc', 'text', null, true) //!pgsql reserved 'desc'
                ->field('feed_url', 'varchar', 255, false)
                ->field('feed_feed', 'varchar', 255, false)
                ->field('feed_tags', 'varchar', 255, true)
                ->field('feed_get_tags', 'smallint', 0, false, 1)
                ->field('feed_owner', 'varchar', 255, false)
                ->field('feed_tweeter', 'varchar', 64, false) // tweeter ident
                ->field('feed_lang', 'varchar', 5, true)
                ->field('feed_nb_out', 'integer', 0, false, 0)
                ->field('feed_nb_in', 'integer', 0, false, 0)

                ->primary('pk_zcfs', 'feed_id')
                ->index('idx_zcfs_type', 'btree', 'feed_type')
                ->index('idx_zcfs_blog', 'btree', 'blog_id');

            (new Structure(App::con(), App::con()->prefix()))->synchronize($s);

            // Settings
            $s = My::settings();
            $s->put('active', false, 'boolean', 'Enable zoneclearBlogServer', false, true);
            $s->put('pub_active', false, 'boolean', 'Enable public page of list of feeds', false, true);
            $s->put('post_status_new', true, 'boolean', 'Enable auto publish new posts', false, true);
            $s->put('bhv_pub_upd', 2, 'string', 'Auto update on public side (disable/before/after)', false, true);
            $s->put('update_limit', 1, 'integer', 'Number of feeds to update at one time', false, true);
            $s->put('keep_empty_feed', false, 'boolean', 'Keep active empty feeds', false, true);
            $s->put('tag_case', 0, 'integer', 'How to transform imported tags', false, true);
            $s->put('user', '', 'string', 'User id that has right on post', false, true);
            $s->put('post_full_tpl', ['post', 'category', 'tag', 'archive'], 'array', 'List of templates types for full feed', false, true);
            $s->put('post_title_redir', ['feed'], 'array', 'List of templates types for redirection to original post', false, true);

            return true;
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return false;
        }
    }
}
