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
declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use dcCore;
use Dotclear\Database\Cursor;
use Dotclear\Plugin\activityReport\{
    Action,
    ActivityReport,
    Group
};

/**
 * Add feeds actions to the plugin activity report.
 */
class ActivityReportActions
{
    public static function init(): void
    {
        $group = new Group(My::id(), My::name());

        $group->add(new Action(
            'updateFeed',
            __('Feed properties update'),
            __('Feed named "%s" point to "%s" has been updated by "%s"'),
            'zoneclearFeedServerAfterUpdateFeed',
            function (Cursor $cur, int $id): void {
                $user = dcCore::app()->auth?->getInfo('user_cn');
                if (!is_string($user)) {
                    return;
                }

                $rs = ZoneclearFeedServer::instance()->getFeeds(['feed_id' => $id]);
                if ($rs->isEmpty()) {
                    return;
                }
                $row = new FeedRow($rs);

                $logs = [
                    $row->name,
                    $row->feed,
                    $user,
                ];

                ActivityReport::instance()->addLog(My::id(), 'updateFeed', $logs);
            }
        ));

        $group->add(new Action(
            'addFeed',
            __('Feed creation'),
            __('A new feed named "%s" point to "%s" was added by "%s"'),
            'zoneclearFeedServerAfterAddFeed',
            function (Cursor $cur, int $id): void {
                $user = dcCore::app()->auth?->getInfo('user_cn');
                if (!is_string($user)) {
                    return;
                }
                $logs = [
                    $cur->getField('feed_name'),
                    $cur->getField('feed_feed'),
                    $user,
                ];

                ActivityReport::instance()->addLog(My::id(), 'addFeed', $logs);
            }
        ));

        $group->add(new Action(
            'enableFeed',
            __('Feed status'),
            __('Feed named "%s" point to "%s" has been set to "%s"'),
            'zoneclearFeedServerAfterEnableFeed',
            function (int $id, bool $enable, int $time): void {
                $rs = ZoneclearFeedServer::instance()->getFeeds(['feed_id' => $id]);
                if ($rs->isEmpty()) {
                    return;
                }
                $row = new FeedRow($rs);

                $logs = [
                    $row->name,
                    $row->feed,
                    $enable ? 'enabled' : 'disabled',
                ];

                ActivityReport::instance()->addLog(My::id(), 'enableFeed', $logs);
            }
        ));

        $group->add(new Action(
            'deleteFeed',
            __('Feed deletion'),
            __('Feed named "%s" point to "%s" has been deleted by "%s"'),
            'zoneclearFeedServerBeforeDeleteFeed',
            function (int $id): void {
                $rs = ZoneclearFeedServer::instance()->getFeeds(['feed_id' => $id]);
                if ($rs->isEmpty()) {
                    return;
                }
                $row = new FeedRow($rs);

                $user = dcCore::app()->auth?->getInfo('user_cn');
                if (!is_string($user)) {
                    return;
                }

                $logs = [
                    $row->name,
                    $row->feed,
                    $user,
                ];

                ActivityReport::instance()->addLog(My::id(), 'deleteFeed', $logs);
            }
        ));

        $group->add(new Action(
            'checkFeedUpdate',
            __('Check feed update'),
            __('Feed named "%s" has been updated automatically'),
            'zoneclearFeedServerAfterCheckFeedUpdate',
            function (FeedRow $row): void {
                $logs = [
                    $row->name,
                ];

                ActivityReport::instance()->addLog(My::id(), 'checkFeedUpdate', $logs);
            }
        ));

        ActivityReport::instance()->groups->add($group);
    }
}
