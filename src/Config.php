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

use adminModulesList;
use dcCore;
use dcPage;
use dcNsProcess;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Input,
    Label,
    Link,
    Number,
    Para,
    Select,
    Text
};
use Exception;

/**
 * Backend module configuration.
 */
class Config extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init == defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth?->isSuperAdmin();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // no action
        if (empty($_POST['save'])) {
            return true;
        }

        // read settings
        $s = ZoneclearFeedServer::instance()->settings;

        // write settings
        try {
            foreach ($s->dump() as $key => $value) {
                $s->set($key, $_POST[$key] ?? $value);
            }

            dcPage::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            dcCore::app()->adminurl?->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
                'redir'  => !(dcCore::app()->admin->__get('list') instanceof adminModulesList) ? '' : dcCore::app()->admin->__get('list')->getRedir(),
            ]);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        // nullsafe
        if (is_null(dcCore::app()->blog)) {
            return;
        }

        $z = ZoneclearFeedServer::instance();
        $s = $z->settings;

        $msg = [];
        if (!is_writable(DC_TPL_CACHE)) {
            $msg[] = (new Para())
                ->class('error')
                ->text(__('Dotclear cache is not writable or not well configured!'));
        }
        if ($s->pub_active) {
            $msg[] = (new Para())
                ->items([
                    (new Link())
                        ->class('onblog_link outgoing')
                        ->text(__('View the public list of feeds') . ' <img alt="" src="images/outgoing-link.svg">')
                        ->href(dcCore::app()->blog->url . dcCore::app()->url->getBase('zoneclearFeedsPage')),
                ]);
        }

        $titles = [];
        foreach ($z->getPublicUrlTypes() as $k => $v) {
            $titles[] = (new Para(null, 'li'))
                ->items([
                    (new Checkbox(['post_title_redir[]', 'post_title_redir_' . $v], in_array($v, $s->post_title_redir)))
                        ->value($v),
                    (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for('post_title_redir_' . $v),
                ]);
        }

        $contents = [];
        foreach ($z->getPublicUrlTypes() as $k => $v) {
            $contents[] = (new Para(null, 'li'))
                ->items([
                    (new Checkbox(['post_full_tpl_[]', 'post_full_tpl_' . $v], in_array($v, $s->post_full_tpl)))
                        ->value($v),
                    (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for('post_full_tpl_' . $v),
                ]);
        }

        echo
        (new Div())
            ->items([
                (new Div())
                    ->items($msg),
                (new Para())
                    ->items([
                        (new Checkbox('active', $s->active))
                            ->value(1),
                        (new Label(__('Enable plugin'), Label::OUTSIDE_LABEL_AFTER))
                            ->class('classic')
                            ->for('active'),
                    ]),
                (new Div())
                    ->class('clear two-cols')
                    ->items([
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Label(__('Status of new posts:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('post_status_new'),
                                        (new Select('post_status_new'))
                                            ->items(Combo::postsStatus())
                                            ->default((string) $s->post_status_new),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('Owner of entries created by the feed server:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('user'),
                                        (new Select('user'))
                                            ->items($z->getAllBlogAdmins())
                                            ->default($s->user),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('How to transform imported tags:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('tag_case'),
                                        (new Select('tag_case'))
                                            ->items(Combo::tagCase())
                                            ->default((string) $s->tag_case),
                                    ]),
                            ]),
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Para())
                                    ->items([
                                        (new Label(__('Update feeds on public side:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('bhv_pub_upd'),
                                        (new Select('bhv_pub_upd'))
                                            ->items(Combo::pubUpdate())
                                            ->default((string) $s->bhv_pub_upd),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('Number of feeds to update at one time:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for('update_limit'),
                                        (new Number('update_limit'))
                                            ->min(0)
                                            ->max(20)
                                            ->value($s->update_limit),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('keep_empty_feed', $s->keep_empty_feed))
                                            ->value(1),
                                        (new Label(__('Keep active empty feeds'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for('keep_empty_feed'),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox('pub_active', $s->pub_active))
                                            ->value(1),
                                        (new Label(__('Enable public page'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for('pub_active'),
                                    ]),
                            ]),
                    ]),
                (new Div())
                    ->class('two-cols')
                    ->items([
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Text('p', __('Redirect to original post on:'))),
                                (new Para(null, 'ul'))
                                    ->items($titles),
                            ]),
                        (new Div())
                            ->class('fieldset col')
                            ->items([
                                (new Text('p', __('Show full content on:'))),
                                (new Para(null, 'ul'))
                                    ->items($contents),
                            ]),
                    ]),
            ])
            ->render();

        dcPage::helpBlock('zoneclearFeedServer');
    }
}
