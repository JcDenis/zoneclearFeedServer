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
    'zoneclearFeedServer'         => __DIR__ . '/inc/class.zoneclearfeedserver.php',
    'zcfsAdminBehaviors'          => __DIR__ . '/inc/class.zcfsadminbehaviors.php',
    'zcfsPostFilter'              => __DIR__ . '/inc/class.zcfspostfilter.php',
    'zcfsEntriesList'             => __DIR__ . '/inc/class.zcfsentrieslist.php',
    'zcfsFeedsList'               => __DIR__ . '/inc/class.zcfsfeedslist.php',
    'zcfsFeedsActions'            => __DIR__ . '/inc/class.zcfsfeedsactions.php',
    'zcfsDefaultFeedsActions'     => __DIR__ . '/inc/class.zcfsdefaultfeedsactions.php',
    'zcfsTemplate'                => __DIR__ . '/inc/class.zcfstemplate.php',
    'zcfsPublicBehaviors'         => __DIR__ . '/inc/class.zcfspublicbehaviors.php',
    'zcfsRsExtPosts'              => __DIR__ . '/inc/class.zcfsrsextposts.php',
    'zcfsUrlHandler'              => __DIR__ . '/inc/class.zcfsurlhandler.php',
    'zcfsActivityReportBehaviors' => __DIR__ . '/inc/class.zcfsactivityreportbehaviors.php',
    'zcfsUpgrade'                 => __DIR__ . '/inc/class.zcfsupgrade.php',
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
    zcfsActivityReportBehaviors::init();
}
