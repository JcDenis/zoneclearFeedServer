<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Database\Statement\{
    SelectStatement,
    UpdateStatement
};
use Exception;

/**
 * @brief       zoneclearFeedServer upgrade class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Upgrade
{
    public static function preUpgrade(): void
    {
        $current = App::version()->getVersion(My::id());
        if (!is_string($current) || empty($current)) {
            return;
        }

        if (version_compare($current, '2022.12.10', '<')) {
            self::preUpgrade20221210();
        }

        if (version_compare($current, '2023.05.05', '<')) {
            self::preUpgrade20230505();
        }
    }

    protected static function preUpgrade20221210(): void
    {
        // Rename settings
        $setting_ids = [
            'zoneclearFeedServer_active'           => 'active',
            'zoneclearFeedServer_pub_active'       => 'pub_active',
            'zoneclearFeedServer_post_status_new'  => 'post_new_status',
            'zoneclearFeedServer_bhv_pub_upd'      => 'bhv_pub_upd',
            'zoneclearFeedServer_update_limit'     => 'update_limit',
            'zoneclearFeedServer_keep_empty_feed'  => 'keep_empty_feed',
            'zoneclearFeedServer_tag_case'         => 'tag_case',
            'zoneclearFeedServer_user'             => 'user',
            'zoneclearFeedServer_post_full_tpl'    => 'post_full_tpl',
            'zoneclearFeedServer_post_title_redir' => 'post_title_redir',
        ];

        $cur = App::blogWorkspace()->openBlogWorkspaceCursor();
        foreach ($setting_ids as $old => $new) {
            $cur->clean();
            $cur->setField('setting_id', $new);
            $cur->setField('setting_ns', My::id());

            $sql = new UpdateStatement();
            $sql
                ->where('setting_id = ' . $sql->quote($old))
                ->and('setting_ns = ' . $sql->quote('zoneclearFeedServer'))
                ->update($cur);
        }

        // use json rather than serialise for settings array
        $sql    = new SelectStatement();
        $record = $sql
            ->from(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->where('setting_ns = ' . $sql->quote(My::id()))
            ->select();

        if (is_null($record)) {
            return;
        }

        $setting_values = [
            'post_full_tpl'    => ['post', 'category', 'tag', 'archive'],
            'post_title_redir' => ['feed'],
        ];

        $cur = App::blogWorkspace()->openBlogWorkspaceCursor();
        while ($record->fetch()) {
            foreach ($setting_values as $key => $default) {
                try {
                    $value = @unserialize($record->__get($key));
                } catch(Exception) {
                    $value = $default;
                }

                $cur->clean();
                $cur->setField('setting_value', json_encode(!is_array($value) ? $default : $value));

                $sql = new UpdateStatement();
                $sql
                    ->where('setting_id = ' . $sql->quote($key))
                    ->and('setting_ns = ' . $sql->quote($record->f('setting_ns')))
                    ->and('blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ('= ' . $sql->quote($record->f('blog_id')))))
                    ->update($cur);
            }
        }
    }

    protected static function preUpgrade20230505(): void
    {
        // change settings type of json string to array
        $sql = new UpdateStatement();
        $sql
            ->ref(App::con()->prefix() . App::blogWorkspace()::NS_TABLE_NAME)
            ->column('setting_type')
            ->value('array')
            ->where('setting_id ' . $sql->in([
                'post_full_tpl',
                'post_title_redir',
            ]))
            ->update();
    }
}
