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
declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use adminGenericFilterV2;
use dcAdminFilters;
use dcCore;
use dcNsProcess;
use dcPage;
use Dotclear\Helper\Html\Form\{
    Div,
    Form,
    Label,
    Link,
    Para,
    Select,
    Submit,
    Text
};
use Exception;

/**
 * Backend feeds list manage page.
 */
class Manage extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && !is_null(dcCore::app()->auth)
            && !is_null(dcCore::app()->blog)
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
            ]), dcCore::app()->blog->id);

        // call period manage page
        if (($_REQUEST['part'] ?? 'feeds') === 'feed') {
            static::$init = ManageFeed::init();
        }

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        // not configured
        if (!$s->active || !$s->user) {
            dcCore::app()->error->add(__('Module is not wel configured'));

            return true;
        }

        // call period manage page
        if (($_REQUEST['part'] ?? 'feeds') === 'feed') {
            return ManageFeed::process();
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        if (is_null(dcCore::app()->adminurl)) {
            return;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        // not configured
        if (!$s->active || !$s->user) {
            dcPage::openModule(My::id());

            echo
            dcPage::breadcrumb([
                __('Plugins') => '',
                My::name()    => '',
            ]) .
            dcPage::notices();

            dcPage::closeModule();

            return;
        }

        // call feed manage page
        if (($_REQUEST['part'] ?? 'feeds') === 'feed') {
            ManageFeed::render();

            return;
        }

        // feeds actions
        $feeds_actions_page = new FeedsActions(
            'plugin.php',
            ['p' => My::id(), 'part' => 'feeds']
        );
        if ($feeds_actions_page->process()) {
            return;
        }

        // feeds filters
        $feeds_filter = new adminGenericFilterV2(My::id() . 'feeds');
        $feeds_filter->add('part', 'feeds');
        $feeds_filter->add(dcAdminFilters::getPageFilter());
        $feeds_filter->add(dcAdminFilters::getSearchFilter());
        $params = $feeds_filter->params();

        // feeds list
        try {
            $feeds         = $z->getFeeds($params);
            $feeds_counter = $z->getFeeds($params, true)->f(0);
            $feeds_list    = new FeedsList($feeds, $feeds_counter);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        dcPage::openModule(
            My::id(),
            (
                isset($feeds_list) && !dcCore::app()->error->flag() ?
                $feeds_filter->js(dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'feeds'], '&')) .
                    dcPage::jsModuleLoad(My::id() . '/js/list.js')
                : ''
            ) .
            dcPage::jsPageTabs()
        );

        echo
        dcPage::breadcrumb([
            __('Plugins')    => '',
            My::name()       => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
            __('Feeds list') => '',
        ]) .
        dcPage::notices();

        echo
        (new Para())
            ->class('top-add')
            ->items([
                (new Link())
                    ->class('button add')
                    ->text(__('New feed'))
                    ->href((string) dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'feed'])),
            ])
            ->render();

        if (isset($feeds_list)) {
            $feeds_filter->display(
                'admin.plugin.' . My::id(),
                dcCore::app()->adminurl->getHiddenFormFields('admin.plugin.' . My::id(), ['part' => 'feeds'])
            );

            $feeds_list->display(
                $feeds_filter,
                (new Form('form-feeds'))
                    ->method('post')
                    ->action(dcCore::app()->adminurl->get('admin.plugin.' . My::id(), ['part' => 'feeds']))
                    ->fields([
                        (new Text('', '%s')),
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())
                                    ->class('col checkboxes-helpers'),
                                (new Para())
                                    ->class('col right')
                                    ->items(array_merge(
                                        dcCore::app()->adminurl->hiddenFormFields('admin.plugin.' . My::id(), $feeds_filter->values(true)),
                                        [
                                            (new Label(__('Selected feeds action:'), Label::OUTSIDE_LABEL_BEFORE))
                                                ->for('action'),
                                            (new Select('action'))
                                                ->items($feeds_actions_page->getCombo()),
                                            (new Submit('feeds-action'))
                                                ->value(__('ok')),
                                            dcCore::app()->formNonce(false),

                                        ]
                                    )),
                            ]),
                    ])
                    ->render()
            );
        }

        dcPage::closeModule();
    }
}
