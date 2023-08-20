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

use dcCore;
use Dotclear\Core\Backend\{
    Notices,
    ModulesList,
    Page
};
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
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
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // no action
        if (empty($_POST['save'])) {
            return true;
        }

        try {
            // read settings
            $s = ZoneclearFeedServer::instance()->settings;

            // write settings
            foreach ($s->dump() as $key => $value) {
                if (is_bool($value)) {
                    $s->set($key, !empty($_POST[My::id() . $key]));
                } else {
                    $s->set($key, $_POST[My::id() . $key] ?: $value);
                }
            }

            Notices::addSuccessNotice(
                __('Configuration has been successfully updated.')
            );
            dcCore::app()->admin->url->redirect('admin.plugins', [
                'module' => My::id(),
                'conf'   => '1',
                'redir'  => !(dcCore::app()->admin->__get('list') instanceof ModulesList) ? '' : dcCore::app()->admin->__get('list')->getRedir(),
            ]);
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
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
                    (new Checkbox([My::id() . 'post_title_redir[]', My::id() . 'post_title_redir_' . $v], in_array($v, $s->post_title_redir)))
                        ->value($v),
                    (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for(My::id() . 'post_title_redir_' . $v),
                ]);
        }

        $contents = [];
        foreach ($z->getPublicUrlTypes() as $k => $v) {
            $contents[] = (new Para(null, 'li'))
                ->items([
                    (new Checkbox([My::id() . 'post_full_tpl[]', My::id() . 'post_full_tpl_' . $v], in_array($v, $s->post_full_tpl)))
                        ->value($v),
                    (new Label(__($k), Label::OUTSIDE_LABEL_AFTER))
                        ->class('classic')
                        ->for(My::id() . 'post_full_tpl_' . $v),
                ]);
        }

        echo
        (new Div())
            ->items([
                (new Div())
                    ->items($msg),
                (new Para())
                    ->items([
                        (new Checkbox(My::id() . 'active', $s->active))
                            ->value(1),
                        (new Label(__('Enable plugin'), Label::OUTSIDE_LABEL_AFTER))
                            ->class('classic')
                            ->for(My::id() . 'active'),
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
                                            ->for(My::id() . 'post_status_new'),
                                        (new Select(My::id() . 'post_status_new'))
                                            ->items(Combo::postsStatus())
                                            ->default((string) $s->post_status_new),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('Owner of entries created by the feed server:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'user'),
                                        (new Select(My::id() . 'user'))
                                            ->items($z->getAllBlogAdmins())
                                            ->default($s->user),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('How to transform imported tags:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'tag_case'),
                                        (new Select(My::id() . 'tag_case'))
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
                                            ->for(My::id() . 'bhv_pub_upd'),
                                        (new Select(My::id() . 'bhv_pub_upd'))
                                            ->items(Combo::pubUpdate())
                                            ->default((string) $s->bhv_pub_upd),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Label(__('Number of feeds to update at one time:'), Label::OUTSIDE_LABEL_BEFORE))
                                            ->for(My::id() . 'update_limit'),
                                        (new Number(My::id() . 'update_limit'))
                                            ->min(0)
                                            ->max(20)
                                            ->value($s->update_limit),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'keep_empty_feed', $s->keep_empty_feed))
                                            ->value(1),
                                        (new Label(__('Keep active empty feeds'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for(My::id() . 'keep_empty_feed'),
                                    ]),
                                (new Para())
                                    ->items([
                                        (new Checkbox(My::id() . 'pub_active', $s->pub_active))
                                            ->value(1),
                                        (new Label(__('Enable public page'), Label::OUTSIDE_LABEL_AFTER))
                                            ->class('classic')
                                            ->for(My::id() . 'pub_active'),
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
                (new Div())->class('clear')->items(
                    $s->active ?
                        [(new Para())
                            ->items([
                                (new Link())
                                    ->href(My::manageUrl())
                                    ->text(__('Configure feeds')),
                            ])] :
                        [],
                ),
                (new Div())->class('clear'),
            ])
            ->render();

        Page::helpBlock('zoneclearFeedServer');
    }
}
