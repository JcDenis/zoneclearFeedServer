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
        //$current = dcCore::app()->plugins->moduleInfo(basename(dirname('../' . __DIR__)), 'version');
        $current = dcCore::app()->getVersion(basename(dirname('../' . __DIR__)));
        if ($current && version_compare($current, '2022.12.10', '<')) {
            self::preUpgrade20221210();
        }
    }

    public static function postUpgrade()
    {
    }

    // Rename settings
    protected static function preUpgrade20221210()
    {
        $settings_ids = [
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

        foreach ($settings_ids as $old => $new) {
            $cur             = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
            $cur->setting_id = $new;
            $cur->update("WHERE setting_id = '" . $old . "' and setting_ns = 'zoneclearFeedServer' ");
        }
    }
}
