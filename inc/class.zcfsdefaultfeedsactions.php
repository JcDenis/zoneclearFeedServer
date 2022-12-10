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
if (!defined('DC_CONTEXT_ADMIN')) {
    return null;
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - Default actions methods
 * @since 2.6
 * @see  dcDefaultPostsActionsPage for mor info
 */
class zcfsDefaultFeedsActions
{
    public static function zcfsFeedsActions(zcfsFeedsActions $ap)
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

    public static function doEnableFeed(zcfsFeedsActions $ap, $post)
    {
        $enable = $ap->getAction() == 'enablefeed';
        $ids    = $ap->getIDs();

        if (empty($ids)) {
            throw new Exception(__('No feeds selected'));
        }

        foreach ($ids as $id) {
            $ap->zcfs->enableFeed($id, $enable);
        }

        dcAdminNotices::addSuccessNotice(sprintf(
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
                ),
            count($ids)
        ));
        $ap->redirect(true);
    }

    public static function doDeletePost(zcfsFeedsActions $ap, $post)
    {
        $types = [
            'zoneclearfeed_url',
            'zoneclearfeed_author',
            'zoneclearfeed_site',
            'zoneclearfeed_sitename',
            'zoneclearfeed_id',
        ];

        $ids = $ap->getIDs();

        if (empty($ids)) {
            throw new Exception(__('No feeds selected'));
        }

        foreach ($ids as $id) {
            $posts = $ap->zcfs->getPostsByFeed([
                'feed_id' => $id,
            ]);

            while ($posts->fetch()) {
                dcCore::app()->blog->delPost($posts->post_id);
                dcCore::app()->con->execute(
                    'DELETE FROM ' . dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' ' .
                    'WHERE post_id = ' . $posts->post_id . ' ' .
                    'AND meta_type ' . dcCore::app()->con->in($types) . ' '
                );
            }
        }

        dcAdminNotices::addSuccessNotice(
            __('Entries have been successfully deleted.')
        );
        $ap->redirect(true);
    }

    public static function doDeleteFeed(zcfsFeedsActions $ap, $post)
    {
        $ids = $ap->getIDs();

        if (empty($ids)) {
            throw new Exception(__('No feeds selected'));
        }

        foreach ($ids as $id) {
            $ap->zcfs->delFeed($id);
        }

        dcAdminNotices::addSuccessNotice(sprintf(
            __(
                '%d feed has been successfully deleted.',
                '%d feeds have been successfully deleted.',
                count($ids)
            ),
            count($ids)
        ));
        $ap->redirect(true);
    }

    public static function doUpdateFeed(zcfsFeedsActions $ap, $post)
    {
        $ids = $ap->getIDs();

        if (empty($ids)) {
            throw new Exception(__('No feeds selected'));
        }

        foreach ($ids as $id) {
            $ap->zcfs->checkFeedsUpdate($id, true);
        }

        dcAdminNotices::addSuccessNotice(sprintf(
            __(
                '%d feed has been successfully updated.',
                '%d feeds have been successfully updated.',
                count($ids)
            ),
            count($ids)
        ));
        $ap->redirect(true);
    }

    public static function doResetUpdate(zcfsFeedsActions $ap, $post)
    {
        $ids = $ap->getIDs();

        if (empty($ids)) {
            throw new Exception(__('No feeds selected'));
        }

        foreach ($ids as $id) {
            $cur                = $ap->zcfs->openCursor();
            $cur->feed_upd_last = 0;
            $ap->zcfs->updFeed($id, $cur);
            $ap->zcfs->checkFeedsUpdate($id, true);
        }

        dcAdminNotices::addSuccessNotice(sprintf(
            __(
                'Last update of %s feed successfully reseted.',
                'Last update of %s feeds successfully reseted.',
                count($ids)
            ),
            count($ids)
        ));
        $ap->redirect(true);
    }

    public static function doChangeCategory(zcfsFeedsActions $ap, $post)
    {
        if (isset($post['upd_cat_id'])) {
            $ids = $ap->getIDs();

            if (empty($ids)) {
                throw new Exception(__('No feeds selected'));
            }

            $cat_id = abs((int) $post['upd_cat_id']);

            foreach ($ids as $id) {
                $cur         = $ap->zcfs->openCursor();
                $cur->cat_id = $cat_id == 0 ? null : $cat_id;
                $ap->zcfs->updFeed($id, $cur);
            }

            dcAdminNotices::addSuccessNotice(sprintf(
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
                dcCore::app()->blog->getCategories()
            );

            $ap->beginPage(
                dcPage::breadcrumb([
                    html::escapeHTML(dcCore::app()->blog->name) => '',
                    __('Feeds server')                          => '',
                    $ap->getCallerTitle()                       => $ap->getRedirection(true),
                    __('Change category for this selection')    => '',
                ])
            );

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="upd_cat_id" class="classic">' . __('Category:') . '</label> ' .
            form::combo(['upd_cat_id'], $categories_combo, '') .
            dcCore::app()->formNonce() .
            $ap->getHiddenFields() .
            form::hidden(['action'], 'changecat') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
            '</form>';

            $ap->endPage();
        }
    }

    public static function doChangeInterval(zcfsFeedsActions $ap, $post)
    {
        if (isset($post['upd_upd_int'])) {
            $ids = $ap->getIDs();

            if (empty($ids)) {
                throw new Exception(__('No feeds selected'));
            }

            $upd_int = abs((int) $post['upd_upd_int']);

            foreach ($ids as $id) {
                $cur               = $ap->zcfs->openCursor();
                $cur->feed_upd_int = $upd_int;
                $ap->zcfs->updFeed($id, $cur);
            }

            dcAdminNotices::addSuccessNotice(sprintf(
                __(
                    'Update frequency of %s feed successfully changed.',
                    'Update frequency of %s feeds successfully changed.',
                    count($ids)
                ),
                count($ids)
            ));
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                dcPage::breadcrumb(
                    [
                        html::escapeHTML(dcCore::app()->blog->name)      => '',
                        __('Feeds server')                               => '',
                        $ap->getCallerTitle()                            => $ap->getRedirection(true),
                        __('Change update frequency for this selection') => '',
                    ]
                )
            );

            echo
            '<form action="' . $ap->getURI() . '" method="post">' .
            $ap->getCheckboxes() .
            '<p><label for="upd_upd_int" class="classic">' . __('Frequency:') . '</label> ' .
            form::combo(['upd_upd_int'], $ap->zcfs->getAllUpdateInterval(), '') .
            dcCore::app()->formNonce() .
            $ap->getHiddenFields() .
            form::hidden(['action'], 'changeint') .
            '<input type="submit" value="' . __('Save') . '" /></p>' .
            '</form>';

            $ap->endPage();
        }
    }
}
