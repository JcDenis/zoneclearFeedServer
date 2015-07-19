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

$this->registerModule(
	/* Name */
	"zoneclearFeedServer",
	/* Description*/
	"Mix your blog with a feeds planet",
	/* Author */
	"Jean-Christian Denis, BG, Pierre Van Glabeke",
	/* Version */
	'2015.07.19',
	/* Properies */
	array(
		'permissions' => 'admin',
		'type' => 'plugin',
		'dc_min' => '2.8',
		'support' => 'http://forum.dotclear.org/viewtopic.php?pid=331158',
		'details' => 'http://plugins.dotaddict.org/dc2/details/zoneclearFeedServer'
	)
);