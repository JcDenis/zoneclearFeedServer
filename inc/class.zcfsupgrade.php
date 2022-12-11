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

class zcfsUpgrade
{
    public static function preUpgrade()
    {
        $current = dcCore::app()->getVersion(basename(dirname('../' . __DIR__)));
        if ($current && version_compare($current, '2022.12.10', '<')) {
            self::preUpgrade20221210();
        }
    }

    protected static function preUpgrade20221210()
    {
        // Rename settings
        $setting_ids = [
            'zoneclearFeedServer_active'           => 'active',
            'zoneclearFeedServer_pub_active'       => 'pub_active',
            'zoneclearFeedServer_post_status_new'  => 'psot_new_status',
            'zoneclearFeedServer_bhv_pub_upd'      => 'bhv_pub_upd',
            'zoneclearFeedServer_update_limit'     => 'update_limit',
            'zoneclearFeedServer_keep_empty_feed'  => 'keep_empty_feed',
            'zoneclearFeedServer_tag_case'         => 'tag_case',
            'zoneclearFeedServer_user'             => 'user',
            'zoneclearFeedServer_post_full_tpl'    => 'post_full_tpl',
            'zoneclearFeedServer_post_title_redir' => 'post_title_redir',
        ];

        foreach ($setting_ids as $old => $new) {
            $cur             = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
            $cur->setting_id = $new;
            $cur->update("WHERE setting_id = '" . $old . "' and setting_ns = 'zoneclearFeedServer' ");
        }

        // use json rather than serialise for settings array
        $setting_values = [
            'post_full_tpl'    => ['post', 'category', 'tag', 'archive'],
            'post_title_redir' => ['feed'],
        ];

        $record = dcCore::app()->con->select(
            'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
            "WHERE setting_ns = '" . dcCore::app()->con->escape(basename(dirname('../' . __DIR__))) . "' "
        );

        while ($record->fetch()) {
            foreach ($setting_values as $key => $default) {
                try {
                    $value = @unserialize($record->__get($key));
                } catch(Exception) {
                    $value = $default;
                }

                $cur                = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                $cur->setting_value = json_encode(!is_array($value) ? $default : $value);
                $cur->update(
                    "WHERE setting_id = '" . $key . "' and setting_ns = '" . dcCore::app()->con->escape($record->setting_ns) . "' " .
                    'AND blog_id ' . (null === $record->blog_id ? 'IS NULL ' : ("= '" . dcCore::app()->con->escape($record->blog_id) . "' "))
                );
            }
        }
    }
}
