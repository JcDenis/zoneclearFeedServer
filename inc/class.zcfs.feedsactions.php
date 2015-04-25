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

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - actions methods
 * @since 2.6
 * @see  dcPostsActionsPage for more info
 */
class zcfsFeedsActionsPage extends dcActionsPage
{
	public $zcfs;

	public function __construct(dcCore $core, $uri, $redirect_args=array())
	{
		$this->zcfs = new zoneclearFeedServer($core);

		parent::__construct($core, $uri, $redirect_args);
		$this->redirect_fields = array(
			'sortby', 'order', 'page', 'nb'
		);
		$this->field_entries = 'feeds';
		$this->caller_title = __('Feeds');
		$this->loadDefaults();
	}

	protected function loadDefaults()
	{
		zcfsDefaultFeedsActions::zcfsFeedsActionsPage($this->core, $this);
		$this->core->callBehavior('zcfsFeedsActionsPage', $this->core, $this);
	}

	public function beginPage($breadcrumb='', $head='')
	{
		echo 
		'<html><head><title>'.__('Feeds server').'</title>'.
		dcPage::jsLoad('js/_posts_actions.js').
		$head.
		'</script></head><body>'.
		$breadcrumb.
		'<p><a class="back" href="'.$this->getRedirection(true).'">'.
		__('Back to feeds list').'</a></p>';
	}

	public function endPage()
	{
		echo 
		'</body></html>';
	}

	public function error(Exception $e)
	{
		$this->core->error->add($e->getMessage());
		$this->beginPage(dcPage::breadcrumb(array(
			html::escapeHTML($this->core->blog->name) => '',
			$this->getCallerTitle() => $this->getRedirection(true),
			__('Feeds actions') => ''
		)));
		$this->endPage();
	}

	protected function fetchEntries($from)
	{
		if (!empty($from['feeds'])) {

			$params['feed_id'] = $from['feeds'];

			$feeds = $this->zcfs->getFeeds($params);
			while ($feeds->fetch())	{
				$this->entries[$feeds->feed_id] = $feeds->feed_name;
			}
			$this->rs = $feeds;
		}
		else {
			$this->rs = $this->core->con->select(
				"SELECT blog_id FROM ".
				$this->core->prefix."blog WHERE false"
			);
		}
	}
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - Default actions methods
 * @since 2.6
 * @see  dcDefaultPostsActionsPage for mor info
 */
class zcfsDefaultFeedsActions
{
	public static function zcfsFeedsActionsPage(dcCore $core, zcfsFeedsActionsPage $ap)
	{
		$ap->addAction(
			array(__('Change category') => 'changecat'),
			array('zcfsDefaultFeedsActions', 'doChangeCategory')
		);
		$ap->addAction(
			array(__('Change update interval') => 'changeint'),
			array('zcfsDefaultFeedsActions', 'doChangeInterval')
		);
		$ap->addAction(
			array(__('Disable feed update') => 'disablefeed'),
			array('zcfsDefaultFeedsActions', 'doEnableFeed')
		);
		$ap->addAction(
			array(__('Enable feed update') => 'enablefeed'),
			array('zcfsDefaultFeedsActions', 'doEnableFeed')
		);
		$ap->addAction(
			array(__('Reset last update') => 'resetupdlast'),
			array('zcfsDefaultFeedsActions', 'doResetUpdate')
		);
		$ap->addAction(
			array(__('Update (check) feed') => 'updatefeed'),
			array('zcfsDefaultFeedsActions', 'doUpdateFeed')
		);
		$ap->addAction(
			array(__('Delete related posts') => 'deletepost'),
			array('zcfsDefaultFeedsActions', 'doDeletePost')
		);
		$ap->addAction(
			array(__('Delete feed (without related posts)') => 'deletefeed'),
			array('zcfsDefaultFeedsActions', 'doDeleteFeed')
		);
	}

	public static function doEnableFeed(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		$enable = $ap->getAction() == 'enablefeed';
		$ids = $ap->getIDs();

		if (empty($ids)) {
			throw new Exception(__('No feeds selected'));
		}

		foreach($ids as $id) {
			$ap->zcfs->enableFeed($id, $enable);
		}

		dcPage::addSuccessNotice(sprintf(
			$enable ?
				__(
					'%d feed has been successfully enabled.',
					'%d feeds have been successfully enabled.',
					count($ids)
				)
			:
				__(
					'%d feed has been successfully disabled.',
					'%d feeds have been successfully disabled.',
					count($ids)
				)
			,
			count($ids)
		));
		$ap->redirect(true);
	}

	public static function doDeletePost(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		$types = array(
			'zoneclearfeed_url',
			'zoneclearfeed_author',
			'zoneclearfeed_site',
			'zoneclearfeed_sitename',
			'zoneclearfeed_id'
		);

		$ids = $ap->getIDs();

		if (empty($ids)) {
			throw new Exception(__('No feeds selected'));
		}

		foreach($ids as $id) {

			$posts = $ap->zcfs->getPostsByFeed(array(
				'feed_id' => $id
			));

			while($posts->fetch()) {

				$core->blog->delPost($posts->post_id);
				$core->con->execute(
					'DELETE FROM '.$core->prefix.'meta '.
					'WHERE post_id = '.$posts->post_id.' '.
					'AND meta_type '.$core->con->in($types).' '
				);
			}
		}

		dcPage::addSuccessNotice(
				__('Entries have been successfully deleted.')
		);
		$ap->redirect(true);
	}

	public static function doDeleteFeed(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		$ids = $ap->getIDs();

		if (empty($ids)) {
			throw new Exception(__('No feeds selected'));
		}

		foreach($ids as $id) {
			$ap->zcfs->delFeed($id);
		}

		dcPage::addSuccessNotice(sprintf(
			__(
				'%d feed has been successfully deleted.',
				'%d feeds have been successfully deleted.',
				count($ids)
			),
			count($ids)
		));
		$ap->redirect(true);
	}

	public static function doUpdateFeed(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		$ids = $ap->getIDs();

		if (empty($ids)) {
			throw new Exception(__('No feeds selected'));
		}

		foreach($ids as $id) {
			$ap->zcfs->checkFeedsUpdate($id, true);
		}

		dcPage::addSuccessNotice(sprintf(
			__(
				'%d feed has been successfully updated.',
				'%d feeds have been successfully updated.',
				count($ids)
			),
			count($ids)
		));
		$ap->redirect(true);
	}

	public static function doResetUpdate(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		$ids = $ap->getIDs();

		if (empty($ids)) {
			throw new Exception(__('No feeds selected'));
		}

		foreach($ids as $id) {
			$cur = $ap->zcfs->openCursor();
			$cur->feed_upd_last = 0;
			$ap->zcfs->updFeed($id, $cur);
			$ap->zcfs->checkFeedsUpdate($id, true);
		}

		dcPage::addSuccessNotice(sprintf(
			__(
				'Last update of %s feed successfully reseted.',
				'Last update of %s feeds successfully reseted.',
				count($ids)
			),
			count($ids)
		));
		$ap->redirect(true);
	}

	public static function doChangeCategory(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		if (isset($post['upd_cat_id'])) {
			$ids = $ap->getIDs();

			if (empty($ids)) {
				throw new Exception(__('No feeds selected'));
			}

			$cat_id = abs((integer) $post['upd_cat_id']);

			foreach($ids as $id) {
				$cur = $ap->zcfs->openCursor();
				$cur->cat_id = $cat_id == 0 ? null : $cat_id;
				$ap->zcfs->updFeed($id, $cur);
			}

			dcPage::addSuccessNotice(sprintf(
				__(
					'Category of %s feed successfully changed.',
					'Category of %s feeds successfully changed.',
					count($ids)
				),
				count($ids)
			));
			$ap->redirect(true);
		}
		else {

			$categories_combo = dcAdminCombos::getCategoriesCombo(
				$core->blog->getCategories()
			);

			$ap->beginPage(
				dcPage::breadcrumb(
					array(
						html::escapeHTML($core->blog->name) => '',
						__('Feeds server') => '',
						$ap->getCallerTitle() => $ap->getRedirection(true),
						__('Change category for this selection') => ''
			)));

			echo
			'<form action="'.$ap->getURI().'" method="post">'.
			$ap->getCheckboxes().
			'<p><label for="upd_cat_id" class="classic">'.__('Category:').'</label> '.
			form::combo(array('upd_cat_id'), $categories_combo, '').
			$core->formNonce().
			$ap->getHiddenFields().
			form::hidden(array('action'), 'changecat').
			'<input type="submit" value="'.__('Save').'" /></p>'.
			'</form>';

			$ap->endPage();
		}
	}

	public static function doChangeInterval(dcCore $core, zcfsFeedsActionsPage $ap, $post)
	{
		if (isset($post['upd_upd_int'])) {
			$ids = $ap->getIDs();

			if (empty($ids)) {
				throw new Exception(__('No feeds selected'));
			}

			$upd_int = abs((integer) $post['upd_upd_int']);

			foreach($ids as $id) {
				$cur = $ap->zcfs->openCursor();
				$cur->feed_upd_int = $upd_int;
				$ap->zcfs->updFeed($id, $cur);
			}

			dcPage::addSuccessNotice(sprintf(
				__(
					'Update frequency of %s feed successfully changed.',
					'Update frequency of %s feeds successfully changed.',
					count($ids)
				),
				count($ids)
			));
			$ap->redirect(true);
		}
		else {

			$ap->beginPage(
				dcPage::breadcrumb(
					array(
						html::escapeHTML($core->blog->name) => '',
						__('Feeds server') => '',
						$ap->getCallerTitle() => $ap->getRedirection(true),
						__('Change update frequency for this selection') => ''
			)));

			echo
			'<form action="'.$ap->getURI().'" method="post">'.
			$ap->getCheckboxes().
			'<p><label for="upd_upd_int" class="classic">'.__('Frequency:').'</label> '.
			form::combo(array('upd_upd_int'), $ap->zcfs->getAllUpdateInterval(), '').
			$core->formNonce().
			$ap->getHiddenFields().
			form::hidden(array('action'), 'changeint').
			'<input type="submit" value="'.__('Save').'" /></p>'.
			'</form>';

			$ap->endPage();
		}
	}
}