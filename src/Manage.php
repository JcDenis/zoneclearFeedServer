<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Core\Backend\Filter\{
    Filters,
    FiltersLibrary
};
use Dotclear\Core\Backend\{
    Notices,
    Page
};
use Dotclear\Core\Process;
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
 * @brief       zoneclearFeedServer backend feeds manage class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Manage extends Process
{
    public static function init(): bool
    {
        self::status(My::checkContext(My::MANAGE));

        // call period manage page
        if (($_REQUEST['part'] ?? 'feeds') === 'feed') {
            self::status(ManageFeed::init());
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        // not configured
        if (!$s->active || !$s->user) {
            App::error()->add(__('Module is not wel configured'));

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
        if (!self::status()) {
            return;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        // not configured
        if (!$s->active || !$s->user) {
            Page::openModule(My::id());

            echo
            Page::breadcrumb([
                __('Plugins') => '',
                My::name()    => '',
            ]) .
            Notices::getNotices();

            Page::closeModule();

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
        $feeds_filter = new Filters(My::id() . 'feeds');
        $feeds_filter->add('part', 'feeds');
        $feeds_filter->add(FiltersLibrary::getPageFilter());
        $feeds_filter->add(FiltersLibrary::getSearchFilter());
        $params = $feeds_filter->params();

        // feeds list
        try {
            $feeds         = $z->getFeeds($params);
            $feeds_counter = $z->getFeeds($params, true)->f(0);
            $feeds_list    = new FeedsList($feeds, $feeds_counter);
        } catch (Exception $e) {
            App::error()->add($e->getMessage());
        }

        Page::openModule(
            My::id(),
            (
                isset($feeds_list) && !App::error()->flag() ?
                $feeds_filter->js(My::manageUrl(['part' => 'feeds'], '&')) .
                    My::jsLoad('feeds')
                : ''
            ) .
            Page::jsPageTabs()
        );

        echo
        Page::breadcrumb([
            __('Plugins')    => '',
            My::name()       => My::manageUrl(),
            __('Feeds list') => '',
        ]) .
        Notices::getNotices();

        echo
        (new Para())
            ->class('top-add')
            ->items([
                (new Link())
                    ->class('button add')
                    ->text(__('New feed'))
                    ->href((string) My::manageUrl(['part' => 'feed'])),
            ])
            ->render();

        if (isset($feeds_list)) {
            $feeds_filter->display(
                'admin.plugin.' . My::id(),
                My::parsedHiddenFields(['part' => 'feeds'])
            );

            $feeds_list->display(
                $feeds_filter,
                (new Form('form-feeds'))
                    ->method('post')
                    ->action(My::manageUrl(['part' => 'feeds']))
                    ->fields([
                        (new Text('', '%s')),
                        (new Div())
                            ->class('two-cols')
                            ->items([
                                (new Para())
                                    ->class('col checkboxes-helpers'),
                                (new Para())
                                    ->class('col right')
                                    ->items([
                                        (new Label(__('Selected feeds action:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('action'),
                                        (new Select('action'))
                                            ->items($feeds_actions_page->getCombo()),
                                        (new Submit('feeds-action'))
                                            ->value(__('ok')),
                                        ... My::hiddenFields($feeds_filter->values(true)),

                                    ]),
                            ]),
                    ])
                    ->render()
            );
        }

        Page::closeModule();
    }
}
