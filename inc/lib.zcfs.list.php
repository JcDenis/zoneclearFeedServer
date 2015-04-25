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

if (!defined('DC_CONTEXT_ADMIN')) {

	return null;
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - feeds list methods
 * @since 2.6
 * @see  adminGenericList for more info
 */
class zcfsFeedsList extends adminGenericList
{
	public function feedsDisplay($page, $nb_per_page, $url, $enclose='')
	{
		if ($this->rs->isEmpty()) {

			return '<p><strong>'.__('There is no feed').'</strong></p>';
		}

		$pager = new dcPager($page, $this->rs_count ,$nb_per_page, 10);

		$pager->base_url = $url;

		$html_block =
		'<div class="table-outer">'.
		'<table class="clear">'.
		'<thead>'.
		'<tr>'.
		'<th class="nowrap first" colspan="2">'.__('Name').'</th>'.
		'<th class="nowrap">'.__('Feed').'</th>'.
		'<th class="nowrap">'.__('Frequency').'</th>'.
		'<th class="nowrap">'.__('Last update').'</th>'.
		'<th class="nowrap">'.__('Entries').'</th>'.
		'<th class="nowrap">'.__('Status').'</th>'.
		'</tr>'.
		'</thead>'.
		'<tbody>%s</tbody>'.
		'</table>'.
		'</div>';

		$res = '';
		while ($this->rs->fetch()) {
			$res .= $this->feedsLine();
		}

		return 
			$pager->getLinks().
			sprintf($enclose, sprintf($html_block, $res)).
			$pager->getLinks();
	}
	
	private function feedsLine()
	{
		$combo_status = zoneclearFeedServer::getAllStatus();
		$combo_upd_int = zoneclearFeedServer::getAllUpdateInterval();
		$status = $this->rs->feed_status ? 
			'<img src="images/check-on.png" alt="enable" />' :
			'<img src="images/check-off.png" alt="disable" />';
		$category = $this->rs->cat_id ? 
			$this->rs->cat_title : __('no categories');

		$entries_count = $this->rs->zc->getPostsByFeed(array('feed_id' => $this->rs->feed_id), true)->f(0);
		$shunk_feed = $this->rs->feed_feed;
		if (strlen($shunk_feed) > 83) {
			$shunk_feed = substr($shunk_feed,0,50).'...'.substr($shunk_feed,-20);
		}

		$url = 'plugin.php?p=zoneclearFeedServer&amp;part=feed&amp;feed_id='.$this->rs->feed_id;

		return
		'<tr class="line">'."\n".
		'<td class="nowrap">'.
			form::checkbox(array('feeds[]'), $this->rs->feed_id, 0).
		'</td>'.
		'<td class="nowrap">'.
		'<a href="'.$url.'#feed" title="'.__('Edit').'">'.
		html::escapeHTML($this->rs->feed_name).'</a>'.
		"</td>\n".
		'<td class="maximal nowrap">'.
		'<a href="'.$this->rs->feed_feed.'" title="'.html::escapeHTML($this->rs->feed_desc).'">'.html::escapeHTML($shunk_feed).'</a>'.
		"</td>\n".
		'<td class="nowrap">'.
		array_search($this->rs->feed_upd_int,$combo_upd_int).
		"</td>\n".
		'<td class="nowrap">'.
		($this->rs->feed_upd_last < 1 ? 
			__('never') :
			dt::str(__('%Y-%m-%d %H:%M'), $this->rs->feed_upd_last,$this->rs->zc->core->auth->getInfo('user_tz'))
		).
		"</td>\n".
		'<td class="nowrap">'.
		($entries_count ? 
			'<a href="'.$url.'#entries" title="'.__('View entries').'">'.$entries_count.'</a>' :
			$entries_count
		).
		"</td>\n".
		'<td>'.
		$status.
		"</td>\n".
		'</tr>'."\n";
	}
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - Posts list methods
 * @since 2.6
 * @see  adminGenericList for more info
 */
class zcfsEntriesList extends adminGenericList
{
	public function display($page, $nb_per_page, $url, $enclose='')
	{
		if ($this->rs->isEmpty()) {

			return '<p><strong>'.__('No entry').'</strong></p>';
		}

		$pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);
		$pager->base_url	= $url;
		$pager->html_prev	= $this->html_prev;
		$pager->html_next	= $this->html_next;
		$pager->var_page	= 'page';

		$html_block =
		'<div class="table-outer">'.
		'<table class="clear"><tr>'.
		'<th colspan="2">'.__('Title').'</th>'.
		'<th>'.__('Date').'</th>'.
		'<th>'.__('Category').'</th>'.
		'<th>'.__('Author').'</th>'.
		'<th>'.__('Comments').'</th>'.
		'<th>'.__('Trackbacks').'</th>'.
		'<th>'.__('Status').'</th>'.
		'</tr>%s</table></div>';

		$res = '';
		while ($this->rs->fetch()) {
			$res .= $this->postLine();
		}

		return 
			$pager->getLinks().
			sprintf($enclose, sprintf($html_block, $res)).
			$pager->getLinks();
	}

	private function postLine()
	{
		$cat_link = $this->core->auth->check('categories', $this->core->blog->id) ?
			'<a href="category.php?id=%s" title="'.__('Edit category').'">%s</a>' 
			: '%2$s';

		$cat_title = $this->rs->cat_title ? 
			sprintf($cat_link,$this->rs->cat_id, html::escapeHTML($this->rs->cat_title)) 
			: __('None');

		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:
				$img_status = sprintf($img, __('published'), 'check-on.png');
				break;
			case 0:
				$img_status = sprintf($img, __('unpublished'), 'check-off.png');
				break;
			case -1:
				$img_status = sprintf($img, __('scheduled'), 'scheduled.png');
				break;
			case -2:
				$img_status = sprintf($img, __('pending'), 'check-wrn.png');
				break;
		}

		return 
		'<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
		' id="p'.$this->rs->post_id.'">'.
		'<td class="nowrap">'.
		form::checkbox(array('entries[]'), $this->rs->post_id, '', '', '', !$this->rs->isEditable()).'</td>'.
		'<td class="maximal"><a href="'.$this->core->getPostAdminURL($this->rs->post_type, $this->rs->post_id).
		'" title="'.__('Edit entry').'">'.
		html::escapeHTML($this->rs->post_title).'</a></td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt).'</td>'.
		'<td class="nowrap">'.$cat_title.'</td>'.
		'<td class="nowrap">'.$this->rs->user_id.'</td>'.
		'<td class="nowrap">'.$this->rs->nb_comment.'</td>'.
		'<td class="nowrap">'.$this->rs->nb_trackback.'</td>'.
		'<td class="nowrap status">'.$img_status.'</td>'.
		'</tr>';
	}
}