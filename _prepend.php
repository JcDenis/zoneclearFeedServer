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

Clearbricks::lib()->autoload([
    'zoneclearFeedServer'     => __DIR__ . '/inc/class.zoneclear.feed.server.php',
    'zcfsFeedsList'           => __DIR__ . '/inc/lib.zcfs.list.php',
    'zcfsEntriesList'         => __DIR__ . '/inc/lib.zcfs.list.php',
    'adminZcfsPostFilter'     => __DIR__ . '/inc/lib.zcfs.list.php',
    'zcfsFeedsActionsPage'    => __DIR__ . '/inc/class.zcfs.feedsactions.php',
    'zcfsDefaultFeedsActions' => __DIR__ . '/inc/class.zcfs.feedsactions.php',
]);

// public url for page of description of the flux
dcCore::app()->url->register(
    'zoneclearFeedsPage',
    'zcfeeds',
    '^zcfeeds(.*?)$',
    ['zcfsUrlHandler', 'zcFeedsPage']
);

// Add to report on plugin activityReport
if (defined('ACTIVITY_REPORT_V2')) {
    require_once __DIR__ . '/inc/lib.zcfs.activityreport.php';
}
