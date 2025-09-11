<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Core\Backend\Action\ActionsPosts;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Exception;

/**
 * @brief       zoneclearFeedServer backend feeds posts manage class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ManageFeed
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE) && ($_REQUEST['part'] ?? 'feeds') === 'feed');
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // no action
        if (empty($_POST['action'])) {
            return true;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;
        $v = ManageFeedVars::instance();

        // save feed
        if ($_POST['action'] == 'savefeed') {
            // check values
            $testfeed_params['feed_feed'] = $v->feed;
            if ($v->id) {
                $testfeed_params['sql'] = 'AND feed_id <> ' . $v->id . ' ';
            }
            if ($z->getFeeds($testfeed_params, true)->f(0)) {
                App::error()->add(__('Record with same feed URL already exists.'));
            }
            if (empty($v->name)) {
                App::error()->add(__('You must provide a name.'));
            }
            if (empty($v->owner)) {
                App::error()->add(__('You must provide an owner.'));
            }
            if (!$z::validateURL($v->url)) {
                App::error()->add(__('You must provide valid site URL.'));
            }
            if (!$z::validateURL($v->feed)) {
                App::error()->add(__('You must provide valid feed URL.'));
            }
            if (null !== $v->cat_id && !App::blog()->getCategory($v->cat_id)) {
                App::error()->add(__('You must provide valid category.'));
            }

            // check failed
            if (App::error()->flag()) {
                return true;
            }

            // save feed
            try {
                $id = $v->save();
                if (!$id) {
                    throw new Exception(__('Failed to save feed.'));
                }

                Notices::addSuccessNotice(__('Feed successfully created.'));
                My::redirect(['part' => 'feed', 'feed_id' => $id]);
            } catch (Exception $e) {
                App::error()->add($e->getMessage());

                return true;
            }

            return true;
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
        $v = ManageFeedVars::instance();

        // Prepared entries list
        if ($v->id && $v->can_view_page) {
            // posts actions
            $posts_actions_page = new ActionsPosts(
                'plugin.php',
                [
                    'p'       => My::id(),
                    'part'    => 'feed',
                    'feed_id' => $v->id,
                    '_ANCHOR' => 'entries',
                ]
            );
            if ($posts_actions_page->process()) {
                return;
            }

            // posts filters
            $post_filter = new PostsFilter();
            $post_filter->add('part', 'feed');
            $post_filter->add('feed_id', $v->id);
            $params = $post_filter->params();

            // typehint
            $sortby = is_string($post_filter->value('sortby')) ? $post_filter->value('sortby') : 'post_creadt';
            $order  = is_string($post_filter->value('order')) ? $post_filter->value('order') : 'DESC';

            # lexical sort
            $sortby_lex = [
                // key in sorty_combo (see above) => field in SQL request
                'post_title' => 'post_title',
                'cat_title'  => 'cat_title',
                'user_id'    => 'P.user_id', ];

            # --BEHAVIOR-- adminPostsSortbyLexCombo
            App::behavior()->callBehavior('adminPostsSortbyLexCombo', [& $sortby_lex]);

            $params['no_content'] = true;
            $params['feed_id']    = $v->id;
            $params['order']      = (
                array_key_exists($sortby, $sortby_lex) ?
                App::db()->con()->lexFields($sortby_lex[$sortby]) :
                $sortby
            ) . ' ' . $order;

            # posts
            try {
                $posts     = $z->getPostsByFeed($params);
                $counter   = $z->getPostsByFeed($params, true);
                $post_list = new PostsList($posts, $counter->f(0));
            } catch (Exception $e) {
                App::error()->add($e->getMessage());
            }
        }

        // display
        Page::openModule(
            My::id(),
            (
                $v->id && isset($post_filter) && !App::error()->flag() ?
                $post_filter->js(My::manageUrl(['part' => 'feed', 'feed_id' => $v->id], '&') . '#entries') .
                    My::jsLoad('feed')
                : ''
            ) .
            Page::jsPageTabs() .
            $v->next_headlink . "\n" . $v->prev_headlink
        );

        echo
        Page::breadcrumb([
            __('Plugins')                               => '',
            My::name()                                  => My::manageUrl(),
            ($v->id ? __('Edit feed') : __('New feed')) => '',
        ]) .
        Notices::getNotices() .
        (new Text('h3', ($v->id ? sprintf(__('Edit feed "%s"'), Html::escapeHTML($v->name)) : __('New feed'))))->render();

        if ($v->can_view_page) {
            # nav link
            if ($v->id && ($v->next_link || $v->prev_link)) {
                $text = '';
                if ($v->prev_link) {
                    $text .= $v->prev_link;
                }
                if ($v->next_link && $v->prev_link) {
                    $text .= ' | ';
                }
                if ($v->next_link) {
                    $text .= $v->next_link;
                }
                echo (new Text('p', $text))->class('nav_prevnext')->render();
            }

            echo
            (new Div('edit-entry'))
                ->class($v->id ? 'multi-part' : '')
                ->title($v->id ? __('Feed') : '')
                ->items([
                    (new Form('edit-entry-form'))
                        ->method('post')
                        ->action(My::manageUrl())
                        ->fields([
                            (new Div())
                                ->class('two-cols')
                                ->items([
                                    (new Div())
                                        ->class('col70')
                                        ->items([
                                            (new Text('h4', __('Feed information'))),
                                            // feed_name
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Name:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->class('required')
                                                        ->for('feed_name'),
                                                    (new Input('feed_name'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->name),
                                                ]),
                                            // feed_owner
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Owner:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->class('required')
                                                        ->for('feed_owner'),
                                                    (new Input('feed_owner'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->owner),
                                                ]),
                                            // feed_url
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Site URL:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->class('required')
                                                        ->for('feed_url'),
                                                    (new Input('feed_url'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->url),
                                                ]),
                                            // feed_feed
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Feed URL:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->class('required')
                                                        ->for('feed_feed'),
                                                    (new Input('feed_feed'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->feed),
                                                ]),
                                            // feed_desc
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Description:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_desc'),
                                                    (new Input('feed_desc'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->desc),
                                                ]),
                                            // feed_tags
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Tags:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_tags'),
                                                    (new Input('feed_tags'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->tags),
                                                ]),
                                            // feed_tweeter
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Tweeter or Identica ident:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_tweeter'),
                                                    (new Input('feed_tweeter'))
                                                        ->class('maximal')
                                                        ->size(60)
                                                        ->maxlength(255)
                                                        ->value($v->tweeter),
                                                ]),
                                        ]),
                                    (new Div())
                                        ->class('col30')
                                        ->items([
                                            (new Text('h4', __('Local settings'))),
                                            // feed_cat_id
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Category:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_cat_id'),
                                                    (new Select('feed_cat_id'))
                                                        ->class('maximal')
                                                        ->items(Combo::postCategories())
                                                        ->default((string) $v->cat_id),
                                                ]),
                                            // feed_status
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Status:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_status'),
                                                    (new Select('feed_status'))
                                                        ->class('maximal')
                                                        ->items(Combo::feedsStatus())
                                                        ->default((string) $v->status),
                                                ]),
                                            // feed_upd_int
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Update:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_upd_int'),
                                                    (new Select('feed_upd_int'))
                                                        ->class('maximal')
                                                        ->items(Combo::updateInterval())
                                                        ->default((string) $v->upd_int),
                                                ]),
                                            // feed_lang
                                            (new Para())
                                                ->items([
                                                    (new Label(__('Lang:'), Label::OUTSIDE_LABEL_BEFORE))
                                                        ->for('feed_lang'),
                                                    (new Select('feed_lang'))
                                                        ->class('maximal')
                                                        ->items(L10n::getISOcodes(true))
                                                        ->default((string) $v->lang),
                                                ]),
                                            // feed_get_tags
                                            (new Para())->items([
                                                (new Checkbox('feed_get_tags', $v->get_tags))
                                                    ->value(1),
                                                (new Label(__('Import tags from feed'), Label::OUTSIDE_LABEL_AFTER))
                                                    ->class('classic')
                                                    ->for('feed_get_tags'),
                                            ]),
                                        ]),
                                ]),
                            (new Para())
                                ->class('clear')
                                ->items([
                                    (new Submit(['save']))
                                        ->value(__('Save') . ' (s)')
                                        ->accesskey('s'),
                                    ... My::hiddenFields([
                                        'part'    => 'feed',
                                        'feed_id' => (string) $v->id,
                                        'action'  => 'savefeed',
                                    ]),
                                ]),
                        ]),
                ])
                ->render();
        }

        if ($v->id && $v->can_view_page && isset($post_filter) && isset($post_list) && isset($posts_actions_page) && !App::error()->flag()) {
            echo '<div class="multi-part" title="' . __('Entries') . '" id="entries">';

            # show posts filters
            $post_filter->display(
                ['admin.plugin.' . My::id(),'#entries'],
                My::manageUrl([
                    'part'    => 'feed',
                    'feed_id' => $v->id,
                ])
            );

            # fix pager url
            $args = $post_filter->values();
            unset($args['page']);
            $args['page'] = '%s';

            # show posts
            $post_list->display(
                $post_filter,
                My::manageUrl($args, '&') . '#entries',
                (new Form('form-entries'))
                    ->method('post')
                    ->action(My::manageUrl(['part' => 'feed']) . '#entries')
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
                                        (new Hidden('redir', My::manageUrl($post_filter->values()))),
                                        (new Label(__('Selected entries action:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('action'),
                                        (new Select('action'))
                                            ->items($posts_actions_page->getCombo() ?? []),
                                        (new Submit('feed-action'))
                                            ->value(__('ok')),
                                        ... My::hiddenFields($post_filter->values()),

                                    ]),
                            ]),
                    ])
                    ->render()
            );

            echo '</div>';
        }

        Page::closeModule();
    }
}
