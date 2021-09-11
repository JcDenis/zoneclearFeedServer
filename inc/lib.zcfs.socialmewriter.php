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

if (!defined('DC_RC_PATH')) {

    return null;
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - soCialMe methods
 * @since 2.6
 *
 * Add ability to send social messages when a feed is update
 */
class zcfsSoCialMeWriter
{
    public static function soCialMeWriterMarker($rs)
    {
        $rs['zcfscreate'] = array(
            'name'        => __('New Zoneclear post'),
            'description'    => __('When a feed has new entry'),
            'action'        => array('Message','Link'),
            'format'        => array('Message'),
            'wildcards'    => array('Message' => array(
                '%posttitle%',
                '%postlink%',
                '%postauthor%',
                '%posttweeter%',
                '%sitetitle%',
                '%sitelink%',
                '%tags'
            ))
        );
    }

    public static function zoneclearFeedServerAfterFeedUpdate($core, $is_new_published_entry, $post, $meta)
    {
        // for now only new post
        if(!$is_new_published_entry) {

            return null;
        }

        $key = 'zcfscreate';

        # Is install
        if (!$core->plugins->moduleExists('soCialMe')) {

            return null;
        }

        # Is active
        if (!$core->blog->settings->soCialMeWriter->active) {

            return null;
        }

        # Load services
        $soCialMeWriter = new soCialMeWriter($core);

        # List of service per action
        $actions = $soCialMeWriter->getMarker('action');

        # List of format per type
        $formats = $soCialMeWriter->getMarker('format');

        # prepare data
        $shortposturl = soCialMeUtils::reduceURL($meta->url);
        $shortposturl = $shortposturl ? $shortposturl : $meta->url;

        $shortsiteurl = soCialMeUtils::reduceURL($meta->site);
        $shortsiteurl = $shortsiteurl ? $shortsiteurl : $meta->site;

         // need this?
        foreach($meta->tags as $k => $tag) {
            $tags[$k] = '#'.$tag;
        }

        # sendMessage
        if (!empty($formats[$key]['Message']) 
         && !empty($actions[$key]['Message'])
        ) {
            // parse message
            $message_txt = str_replace(
                array(
                    '%posttitle%',
                    '%postlink%',
                    '%postauthor%',
                    '%posttweeter%',
                    '%sitetitle%',
                    '%sitelink%',
                    '%tags'
                ),
                array(
                    $post->post_title,
                    $shortposturl,
                    $meta->author,
                    $meta->tweeter,
                    $meta->sitename,
                    $shortsiteurl,
                    implode(',', $meta->tags)
                ),
                $formats[$key]['Message']
            );

            // send message
            if (!empty($message_txt)) {
                foreach($actions[$key]['Message'] as $service_id) {
                    $soCialMeWriter->play(
                        $service_id,
                        'Message',
                        'Content',
                        $message_txt
                    );
                }
            }
        }

        # sendLink
        if (!empty($actions[$key]['Link'])) {
            foreach($actions[$key]['Link'] as $service_id) {
                $soCialMeWriter->play(
                    $service_id,
                    'Link',
                    'Content',
                    $cur->post_title,$shortposturl
                );
            }
        }

        # sendData
        // not yet implemented

        #sendArticle
        // not yet implemented
    }
}