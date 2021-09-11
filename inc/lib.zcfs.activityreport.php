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

# This file is used with plugin activityReport
$core->activityReport->addGroup(
    'zoneclearFeedServer',
    __('Plugin zoneclearFeedServer')
);

# from BEHAVIOR zoneclearFeedServerAfterAddFeed in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
$core->activityReport->addAction(
    'zoneclearFeedServer',
    'create',
    __('feed creation'),
    __('A new feed named "%s" point to "%s" was added by "%s"'),
    'zoneclearFeedServerAfterAddFeed',
    array('zoneclearFeedServerActivityReportBehaviors', 'addFeed')
);
# from BEHAVIOR zoneclearFeedServerAfterUpdFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
$core->activityReport->addAction(
    'zoneclearFeedServer',
    'updatefeedinfo',
    __('updating feed info'),
    __('Feed named "%s" point to "%s" has been updated by "%s"'),
    'zoneclearFeedServerAfterUpdFeed',
    array('zoneclearFeedServerActivityReportBehaviors', 'updFeedInfo')
);
# from BEHAVIOR zoneclearFeedServerAfterUpdFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
$core->activityReport->addAction(
    'zoneclearFeedServer',
    'updatefeedrecords',
    __('updating feed records'),
    __('Records of the feed named "%s" have been updated automatically'),
    'zoneclearFeedServerAfterUpdFeed',
    array('zoneclearFeedServerActivityReportBehaviors', 'updFeedRecord')
);
# from BEHAVIOR zoneclearFeedServerAfterDelFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
$core->activityReport->addAction(
    'zoneclearFeedServer',
    'delete',
    __('feed deletion'),
    __('Feed named "%s" point to "%s" has been deleted by "%s"'),
    'zoneclearFeedServerAfterDelFeed',
    array('zoneclearFeedServerActivityReportBehaviors', 'delFeed')
);
# from BEHAVIOR zoneclearFeedServerAfterEnableFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
$core->activityReport->addAction(
    'zoneclearFeedServer',
    'status',
    __('feed status'),
    __('Feed named "%s" point to "%s" has been set to "%s"'),
    'zoneclearFeedServerAfterEnableFeed',
    array('zoneclearFeedServerActivityReportBehaviors', 'enableFeed')
);

class zoneclearFeedServerActivityReportBehaviors
{
    public static function addFeed($cur)
    {
        global $core;

        $logs = array(
            $cur->feed_name,
            $cur->feed_feed,
            $core->auth->getInfo('user_cn')
        );

        $core->activityReport->addLog(
            'zoneclearFeedServer',
            'create',
            $logs
        );
    }

    public static function updFeedInfo($cur, $id)
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            global $core;
            $zc = new zoneclearFeedServer($core);
            $rs = $zc->getFeeds(array('feed_id' => $id));

            $logs = array(
                $rs->feed_name,
                $rs->feed_feed,
                $core->auth->getInfo('user_cn')
            );

            $core->activityReport->addLog(
                'zoneclearFeedServer',
                'updatefeedinfo',
                $logs
            );
        }
    }

    public static function updFeedRecord($cur,$id)
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            global $core;
            $zc = new zoneclearFeedServer($core);
            $rs = $zc->getFeeds(array('feed_id' => $id));

            $logs = array(
                $rs->feed_name
            );

            $core->activityReport->addLog(
                'zoneclearFeedServer',
                'updatefeedrecords',
                $logs
            );
        }
    }

    public static function delFeed($id)
    {
        global $core;

        $zc = new zoneclearFeedServer($core);
        $rs = $zc->getFeeds(array('feed_id' => $id));

        $logs = array(
            $rs->feed_name,
            $rs->feed_feed,
            $core->auth->getInfo('user_cn')
        );

        $core->activityReport->addLog(
            'zoneclearFeedServer',
            'delete',
            $logs
        );
    }

    public static function enableFeed($id, $enable, $time)
    {
        global $core;

        $zc = new zoneclearFeedServer($core);
        $rs = $zc->getFeeds(array('feed_id' => $id));

        $logs = array(
            $rs->feed_name,
            $rs->feed_feed,
            $enable ? 'enable' : 'disable'
        );

        $core->activityReport->addLog(
            'zoneclearFeedServer',
            'status',
            $logs
        );
    }
}