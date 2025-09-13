<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       zoneclearFeedServer feeds list default actions.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FeedsDefaultActions
{
    /**
     * Add feeds list actions.
     */
    public static function addDefaultFeedsActions(FeedsActions $ap): void
    {
        $ap->addAction(
            [__('Change category') => 'changecat'],
            self::doChangeCategory(...)
        );
        $ap->addAction(
            [__('Change update interval') => 'changeint'],
            self::doChangeInterval(...)
        );
        $ap->addAction(
            [__('Disable feed update') => 'disablefeed'],
            self::doEnableFeed(...)
        );
        $ap->addAction(
            [__('Enable feed update') => 'enablefeed'],
            self::doEnableFeed(...)
        );
        $ap->addAction(
            [__('Reset last update') => 'resetupdlast'],
            self::doResetUpdate(...)
        );
        $ap->addAction(
            [__('Update (check) feed') => 'updatefeed'],
            self::doUpdateFeed(...)
        );
        $ap->addAction(
            [__('Delete related posts') => 'deletepost'],
            self::doDeletePost(...)
        );
        $ap->addAction(
            [__('Delete feed (without related posts)') => 'deletefeed'],
            self::doDeleteFeed(...)
        );
    }

    /**
     * Enable / disable feeds.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doEnableFeed(FeedsActions $ap, ArrayObject $post): void
    {
        $enable = $ap->getAction() == 'enablefeed';
        $ids    = $ap->getIDs();

        if (empty($ids)) {
            $ap->error(new Exception(__('No feeds selected')));

            return;
        }

        foreach ($ids as $id) {
            $ap->zcfs->enableFeed((int) $id, $enable);
        }

        Notices::addSuccessNotice(sprintf(
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

    /**
     * Delete feeds posts.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doDeletePost(FeedsActions $ap, ArrayObject $post): void
    {
        $types = [
            My::META_PREFIX . 'url',
            My::META_PREFIX . 'author',
            My::META_PREFIX . 'site',
            My::META_PREFIX . 'sitename',
            My::META_PREFIX . 'id',
        ];

        $ids = $ap->getIDs();

        if (empty($ids)) {
            $ap->error(new Exception(__('No feeds selected')));

            return;
        }

        foreach ($ids as $id) {
            $posts = $ap->zcfs->getPostsByFeed([
                'feed_id' => $id,
            ]);

            while ($posts->fetch()) {
                if (is_numeric($posts->f('post_id'))) {
                    App::blog()->delPost((int) $posts->f('post_id'));
                    $sql = new DeleteStatement();
                    $sql
                        ->from(App::db()->con()->prefix() . App::meta()::META_TABLE_NAME)
                        ->where('post_id = ' . $posts->f('post_id'))
                        ->and('meta_type ' . $sql->in($types))
                        ->delete();
                }
            }
        }

        Notices::addSuccessNotice(
            __('Entries have been successfully deleted.')
        );
        $ap->redirect(true);
    }

    /**
     * Delete feeds.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doDeleteFeed(FeedsActions $ap, ArrayObject $post): void
    {
        $ids = $ap->getIDs();

        if (empty($ids)) {
            $ap->error(new Exception(__('No feeds selected')));

            return;
        }

        foreach ($ids as $id) {
            $ap->zcfs->deleteFeed((int) $id);
        }

        Notices::addSuccessNotice(sprintf(
            __(
                '%d feed has been successfully deleted.',
                '%d feeds have been successfully deleted.',
                count($ids)
            ),
            count($ids)
        ));
        $ap->redirect(true);
    }

    /**
     * Update feeds properties.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doUpdateFeed(FeedsActions $ap, ArrayObject $post): void
    {
        $ids = $ap->getIDs();

        if (empty($ids)) {
            $ap->error(new Exception(__('No feeds selected')));

            return;
        }

        foreach ($ids as $id) {
            $ap->zcfs->checkFeedsUpdate((int) $id, true);
        }

        Notices::addSuccessNotice(sprintf(
            __('%d feed has been successfully updated.', '%d feeds have been successfully updated.', count($ids)),
            count($ids)
        ));
        $ap->redirect(true);
    }

    /**
     * Reset feeds update timer.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doResetUpdate(FeedsActions $ap, ArrayObject $post): void
    {
        $ids = $ap->getIDs();

        if (empty($ids)) {
            $ap->error(new Exception(__('No feeds selected')));

            return;
        }

        $cur = $ap->zcfs->openCursor();
        foreach ($ids as $id) {
            $cur->clean();
            $cur->setField('feed_upd_last', 0);
            $ap->zcfs->updateFeed((int) $id, $cur);
            //$ap->zcfs->checkFeedsUpdate($id, true);
        }

        Notices::addSuccessNotice(sprintf(
            __('Last update of %s feed successfully reseted.', 'Last update of %s feeds successfully reseted.', count($ids)),
            count($ids)
        ));
        $ap->redirect(true);
    }

    /**
     * Change feeds categories.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doChangeCategory(FeedsActions $ap, ArrayObject $post): void
    {
        if (isset($post['upd_cat_id'])) {
            $ids = $ap->getIDs();

            if (empty($ids)) {
                $ap->error(new Exception(__('No feeds selected')));

                return;
            }

            $cat_id = is_numeric($post['upd_cat_id']) ? abs((int) $post['upd_cat_id']) : null;

            $cur = $ap->zcfs->openCursor();
            foreach ($ids as $id) {
                $cur->clean();
                $cur->setField('cat_id', $cat_id == 0 ? null : $cat_id);
                $ap->zcfs->updateFeed((int) $id, $cur);
            }

            Notices::addSuccessNotice(sprintf(
                __('Category of %s feed successfully changed.', 'Category of %s feeds successfully changed.', count($ids)),
                count($ids)
            ));
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                Page::breadcrumb([
                    Html::escapeHTML(App::blog()->name())    => '',
                    __('Feeds server')                       => '',
                    $ap->getCallerTitle()                    => $ap->getRedirection(true),
                    __('Change category for this selection') => '',
                ])
            );

            echo
            (new Form('form-action'))
                ->method('post')
                ->action($ap->getURI())
                ->fields([
                    (new Text('', $ap->getCheckboxes())),
                    (new Para())
                        ->items(array_merge(
                            $ap->hiddenFields(),
                            [
                                (new Label(__('Category:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('upd_cat_id'),
                                (new Select('upd_cat_id'))
                                    ->items(Combo::postCategories()),
                                (new Submit('do-action'))
                                    ->value(__('Save')),
                                (new Hidden(['action'], 'changecat')),
                                App::nonce()->formNonce(),
                            ]
                        )),

                ])
                ->render();

            $ap->endPage();
        }
    }

    /**
     * Change feeds update interval.
     *
     * @param   ArrayObject<string, mixed>  $post
     */
    public static function doChangeInterval(FeedsActions $ap, ArrayObject $post): void
    {
        if (isset($post['upd_upd_int'])) {
            $ids = $ap->getIDs();

            if (empty($ids)) {
                $ap->error(new Exception(__('No feeds selected')));

                return;
            }

            $upd_int = is_numeric($post['upd_upd_int']) ? abs((int) $post['upd_upd_int']) : 0;

            $cur = $ap->zcfs->openCursor();
            foreach ($ids as $id) {
                $cur->clean();
                $cur->setField('feed_upd_int', $upd_int);
                $ap->zcfs->updateFeed((int) $id, $cur);
            }

            Notices::addSuccessNotice(sprintf(
                __('Update frequency of %s feed successfully changed.', 'Update frequency of %s feeds successfully changed.', count($ids)),
                count($ids)
            ));
            $ap->redirect(true);
        } else {
            $ap->beginPage(
                Page::breadcrumb(
                    [
                        Html::escapeHTML(App::blog()->name())            => '',
                        __('Feeds server')                               => '',
                        $ap->getCallerTitle()                            => $ap->getRedirection(true),
                        __('Change update frequency for this selection') => '',
                    ]
                )
            );

            echo
            (new Form('form-action'))
                ->method('post')
                ->action($ap->getURI())
                ->fields([
                    (new Text('', $ap->getCheckboxes())),
                    (new Para())
                        ->items(array_merge(
                            $ap->hiddenFields(),
                            [
                                (new Label(__('Frequency:'), Label::OUTSIDE_LABEL_BEFORE))
                                    ->for('upd_upd_int'),
                                (new Select('upd_upd_int'))
                                    ->items(array_map(fn ($v): string => (string) $v, Combo::updateInterval())),
                                (new Submit('do-action'))
                                    ->value(__('Save')),
                                (new Hidden(['action'], 'changeint')),
                                App::nonce()->formNonce(),
                            ]
                        )),

                ])
                ->render();

            $ap->endPage();
        }
    }
}
