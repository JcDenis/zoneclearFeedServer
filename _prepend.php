<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of zoneclearFeedServer, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2015 Jean-Christian Denis and contributors
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

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

# Add to report on plugin activityReport
if (defined('ACTIVITY_REPORT')) {
	require_once $d.'lib.zcfs.activityreport.php';
}