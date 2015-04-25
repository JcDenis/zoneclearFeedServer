#!/usr/bin/env php
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

# This file is highly based cron-script.php
# From Dotclear extension called planet
# By Olivier Meunier and contributors
# Licensed under the GPL version 2.0 license

$opts = getopt('d:c:b:u:h');

function help($status=0)
{
	echo
	"Options: \n".
	" -h shows this help\n".
	" -d DotClear root path\n".
	" -c DotClear conf path\n".
	" -b Blog ID\n".
	" -u User ID\n\n";
	exit($status);
}

if (isset($opts['h'])) {
	help();
}

$dc_root = null;
$dc_conf = null;
$blog_id = null;

if (isset($opts['d'])) {
	$dc_root = $opts['d'];
} elseif (isset($_SERVER['DC_ROOT'])) {
	$dc_root = $_SERVER['DC_ROOT'];
}

if (isset($opts['c'])) {
	$dc_conf = realpath($opts['c']);
} elseif (isset($_SERVER['DC_RC_PATH'])) {
	$dc_conf = realpath($_SERVER['DC_RC_PATH']);
}

if (isset($opts['b'])) {
	$blog_id = $opts['b'];
} elseif (isset($_SERVER['DC_BLOG_ID'])) {
	$blog_id = $opts['DC_BLOG_ID'];
}

if (!$dc_root || !is_dir($dc_root)) {
	fwrite(STDERR,"DotClear root path is not defined\n\n");
	help(1);
}

if (!$dc_conf || !is_readable($dc_conf)) {
	fwrite(STDERR,"DotClear configuration not found\n\n");
	help(1);
}

if (!$blog_id) {
	fwrite(STDERR,"Blog ID is not defined\n\n");
	help(1);
}

$_SERVER['DC_RC_PATH'] = $dc_conf;
unset($dc_conf);

define('DC_BLOG_ID',$blog_id);
unset($blog_id);

require $dc_root.'/inc/prepend.php';
unset($dc_root);

$core->setBlog(DC_BLOG_ID);
if ($core->blog->id == null) {
	fwrite(STDERR,"Blog is not defined\n");
	exit(1);
}

if (!isset($opts['u']) || !$core->auth->checkUser($opts['u'])) {
	fwrite(STDERR,"Unable to set user\n");
	exit(1);
}

$core->plugins->loadModules(DC_PLUGINS_ROOT);

$core->blog->settings->addNamespace('zoneclearFeedServer');

try {
	$zc = new zoneclearFeedServer($core);
	$zc->checkFeedsUpdate();
} catch (Exception $e) {
	fwrite(STDERR,$e->getMessage()."\n");
	exit(1);
}