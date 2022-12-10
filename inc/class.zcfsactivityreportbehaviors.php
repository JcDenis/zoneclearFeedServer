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

class zcfsActivityReportBehaviors
{
    public static function init()
    {
        # This file is used with plugin activityReport
        dcCore::app()->activityReport->addGroup(
            'zoneclearFeedServer',
            __('Plugin zoneclearFeedServer')
        );

        # from BEHAVIOR zoneclearFeedServerAfterAddFeed in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
        dcCore::app()->activityReport->addAction(
            'zoneclearFeedServer',
            'create',
            __('feed creation'),
            __('A new feed named "%s" point to "%s" was added by "%s"'),
            'zoneclearFeedServerAfterAddFeed',
            ['zoneclearFeedServerActivityReportBehaviors', 'addFeed']
        );
        # from BEHAVIOR zoneclearFeedServerAfterUpdFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
        dcCore::app()->activityReport->addAction(
            'zoneclearFeedServer',
            'updatefeedinfo',
            __('updating feed info'),
            __('Feed named "%s" point to "%s" has been updated by "%s"'),
            'zoneclearFeedServerAfterUpdFeed',
            ['zoneclearFeedServerActivityReportBehaviors', 'updFeedInfo']
        );
        # from BEHAVIOR zoneclearFeedServerAfterUpdFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
        dcCore::app()->activityReport->addAction(
            'zoneclearFeedServer',
            'updatefeedrecords',
            __('updating feed records'),
            __('Records of the feed named "%s" have been updated automatically'),
            'zoneclearFeedServerAfterUpdFeed',
            ['zoneclearFeedServerActivityReportBehaviors', 'updFeedRecord']
        );
        # from BEHAVIOR zoneclearFeedServerAfterDelFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
        dcCore::app()->activityReport->addAction(
            'zoneclearFeedServer',
            'delete',
            __('feed deletion'),
            __('Feed named "%s" point to "%s" has been deleted by "%s"'),
            'zoneclearFeedServerAfterDelFeed',
            ['zoneclearFeedServerActivityReportBehaviors', 'delFeed']
        );
        # from BEHAVIOR zoneclearFeedServerAfterEnableFeed in in zoneclearFeedServer/inc/class.zoneclear.feed.server.php
        dcCore::app()->activityReport->addAction(
            'zoneclearFeedServer',
            'status',
            __('feed status'),
            __('Feed named "%s" point to "%s" has been set to "%s"'),
            'zoneclearFeedServerAfterEnableFeed',
            ['zoneclearFeedServerActivityReportBehaviors', 'enableFeed']
        );
    }

    public static function addFeed($cur)
    {
        $logs = [
            $cur->feed_name,
            $cur->feed_feed,
            dcCore::app()->auth->getInfo('user_cn'),
        ];

        dcCore::app()->activityReport->addLog(
            'zoneclearFeedServer',
            'create',
            $logs
        );
    }

    public static function updFeedInfo($cur, $id)
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            $zc = new zoneclearFeedServer();
            $rs = $zc->getFeeds(['feed_id' => $id]);

            $logs = [
                $rs->feed_name,
                $rs->feed_feed,
                dcCore::app()->auth->getInfo('user_cn'),
            ];

            dcCore::app()->activityReport->addLog(
                'zoneclearFeedServer',
                'updatefeedinfo',
                $logs
            );
        }
    }

    public static function updFeedRecord($cur, $id)
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            $zc = new zoneclearFeedServer();
            $rs = $zc->getFeeds(['feed_id' => $id]);

            $logs = [
                $rs->feed_name,
            ];

            dcCore::app()->activityReport->addLog(
                'zoneclearFeedServer',
                'updatefeedrecords',
                $logs
            );
        }
    }

    public static function delFeed($id)
    {
        $zc = new zoneclearFeedServer();
        $rs = $zc->getFeeds(['feed_id' => $id]);

        $logs = [
            $rs->feed_name,
            $rs->feed_feed,
            dcCore::app()->auth->getInfo('user_cn'),
        ];

        dcCore::app()->activityReport->addLog(
            'zoneclearFeedServer',
            'delete',
            $logs
        );
    }

    public static function enableFeed($id, $enable, $time)
    {
        $zc = new zoneclearFeedServer();
        $rs = $zc->getFeeds(['feed_id' => $id]);

        $logs = [
            $rs->feed_name,
            $rs->feed_feed,
            $enable ? 'enable' : 'disable',
        ];

        dcCore::app()->activityReport->addLog(
            'zoneclearFeedServer',
            'status',
            $logs
        );
    }
}
