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

class zcfsFeedsActionsPage extends dcActionsPage
{
    public $zcfs;

    public function __construct(dcCore $core, $uri, $redirect_args = [])
    {
        $this->zcfs = new zoneclearFeedServer($core);

        parent::__construct($core, $uri, $redirect_args);
        $this->redirect_fields = [
            'sortby', 'order', 'page', 'nb'
        ];
        $this->field_entries = 'feeds';
        $this->caller_title = __('Feeds');
        $this->loadDefaults();
    }

    protected function loadDefaults()
    {
        zcfsDefaultFeedsActions::zcfsFeedsActionsPage($this->core, $this);
        $this->core->callBehavior('zcfsFeedsActionsPage', $this->core, $this);
    }

    public function beginPage($breadcrumb = '', $head = '')
    {
        echo 
        '<html><head><title>' . __('Feeds server') . '</title>' .
        dcPage::jsLoad('js/_posts_actions.js') .
        $head .
        '</script></head><body>' .
        $breadcrumb .
        '<p><a class="back" href="' . $this->getRedirection(true) . '">' .
        __('Back to feeds list') . '</a></p>';
    }

    public function endPage()
    {
        echo '</body></html>';
    }

    public function error(Exception $e)
    {
        $this->core->error->add($e->getMessage());
        $this->beginPage(
            dcPage::breadcrumb([
                html::escapeHTML($this->core->blog->name) => '',
                $this->getCallerTitle() => $this->getRedirection(true),
                __('Feeds actions') => ''
            ])
        );
        $this->endPage();
    }

    protected function fetchEntries($from)
    {
        if (!empty($from['feeds'])) {
            $params['feed_id'] = $from['feeds'];

            $feeds = $this->zcfs->getFeeds($params);
            while ($feeds->fetch())    {
                $this->entries[$feeds->feed_id] = $feeds->feed_name;
            }
            $this->rs = $feeds;
        } else {
            $this->rs = $this->core->con->select(
                "SELECT blog_id FROM " . $this->core->prefix . "blog WHERE false"
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
            [__('Change category') => 'changecat'],
            ['zcfsDefaultFeedsActions', 'doChangeCategory']
        );
        $ap->addAction(
            [__('Change update interval') => 'changeint'],
            ['zcfsDefaultFeedsActions', 'doChangeInterval']
        );
        $ap->addAction(
            [__('Disable feed update') => 'disablefeed'],
            ['zcfsDefaultFeedsActions', 'doEnableFeed']
        );
        $ap->addAction(
            [__('Enable feed update') => 'enablefeed'],
            ['zcfsDefaultFeedsActions', 'doEnableFeed']
        );
        $ap->addAction(
            [__('Reset last update') => 'resetupdlast'],
            ['zcfsDefaultFeedsActions', 'doResetUpdate']
        );
        $ap->addAction(
            [__('Update (check) feed') => 'updatefeed'],
            ['zcfsDefaultFeedsActions', 'doUpdateFeed']
        );
        $ap->addAction(
            [__('Delete related posts') => 'deletepost'],
            ['zcfsDefaultFeedsActions', 'doDeletePost']
        );
        $ap->addAction(
            [__('Delete feed (without related posts)') => 'deletefeed'],
            ['zcfsDefaultFeedsActions', 'doDeleteFeed']
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
        $types = [
            'zoneclearfeed_url',
            'zoneclearfeed_author',
            'zoneclearfeed_site',
            'zoneclearfeed_sitename',
            'zoneclearfeed_id'
        ];

        $ids = $ap->getIDs();

        if (empty($ids)) {
            throw new Exception(__('No feeds selected'));
        }

        foreach($ids as $id) {

            $posts = $ap->zcfs->getPostsByFeed([
                'feed_id' => $id
            ]);

            while($posts->fetch()) {

                $core->blog->delPost($posts->post_id);
                $core->con->execute(
                    'DELETE FROM ' . $core->prefix . 'meta ' .
                    'WHERE post_id = ' . $posts->post_id . ' ' .
                    'AND meta_type ' . $core->con->in($types) . ' '
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
        } else {
            $categories_combo = dcAdminCombos::getCategoriesCombo(
                $core->blog->getCategories()
            );

            $ap->beginPage(
                dcPage::breadcrumb([
                        html::escapeHTML($core->blog->name) => '',
                        __('Feeds server') => '',
                        $ap->getCallerTitle() => $ap->getRedirection(true),
                        __('Change category for this selection') => ''
            ]));

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="upd_cat_id" class="classic">' . __('Category:') . '</label> ' .
            form::combo(['upd_cat_id'], $categories_combo, '') .
            $core->formNonce() .
            $ap->getHiddenFields() .
            form::hidden(['action'], 'changecat') . 
            '<input type="submit" value="' . __('Save') . '" /></p>' .
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
                    [
                        html::escapeHTML($core->blog->name) => '',
                        __('Feeds server') => '',
                        $ap->getCallerTitle() => $ap->getRedirection(true),
                        __('Change update frequency for this selection') => ''
            ]));

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="upd_upd_int" class="classic">' . __('Frequency:') . '</label> ' .
            form::combo(['upd_upd_int'], $ap->zcfs->getAllUpdateInterval(), '') .
            $core->formNonce() .
            $ap->getHiddenFields() .
            form::hidden(['action'], 'changeint') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
            '</form>';

            $ap->endPage();
        }
    }
}