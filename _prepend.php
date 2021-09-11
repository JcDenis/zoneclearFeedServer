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

if ($core->getVersion('zoneclearFeedServer') != 
    $core->plugins->moduleInfo('zoneclearFeedServer', 'version')) {

    return null;
}

$d = dirname(__FILE__).'/inc/';

$__autoload['zoneclearFeedServer'] = $d.'class.zoneclear.feed.server.php';
$__autoload['zcfsFeedsList'] = $d.'lib.zcfs.list.php';
$__autoload['zcfsEntriesList'] = $d.'lib.zcfs.list.php';
$__autoload['zcfsFeedsActionsPage'] = $d.'class.zcfs.feedsactions.php';
$__autoload['zcfsDefaultFeedsActions'] = $d.'class.zcfs.feedsactions.php';

# public url for page of description of the flux
$core->url->register(
    'zoneclearFeedsPage',
    'zcfeeds',
    '^zcfeeds(.*?)$',
    array('zcfsUrlHandler', 'zcFeedsPage')
);
/*
# Add to plugn soCialMe (writer part)
$__autoload['zcfsSoCialMeWriter'] = $d.'lib.zcfs.socialmewriter.php';
$core->addBehavior(
    'soCialMeWriterMarker',
    array('zcfsSoCialMeWriter', 'soCialMeWriterMarker')
);
$core->addBehavior(
    'zoneclearFeedServerAfterFeedUpdate',
    array('zcfsSoCialMeWriter', 'zoneclearFeedServerAfterFeedUpdate')
);
//*/
# Add to report on plugin activityReport
if (defined('ACTIVITY_REPORT')) {
    require_once $d.'lib.zcfs.activityreport.php';
}