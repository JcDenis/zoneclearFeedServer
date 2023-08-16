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

use ArrayObject;
use dcCore;
use Dotclear\Core\Backend\Listing\{
    Listing,
    Pager
};
use Dotclear\Helper\Date;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Link,
    Para,
    Text
};
use Dotclear\Helper\Html\Html;

/**
 * Backend feed posts lists.
 */
class PostsList extends Listing
{
    public function display(PostsFilter $filter, string $base_url, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo
            (new Text(
                'p',
                $filter->show() ?
                    __('No entries matches the filter') :
                    __('No entries')
            ))
                ->class('info')
                ->render();

            return;
        }

        $page            = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
        $nbpp            = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 10;
        $count           = (int) $this->rs_count;
        $pager           = new Pager($page, $count, $nbpp, 10);
        $pager->base_url = $base_url;

        $cols = new ArrayObject([
            'title' => (new Text('th', __('Title')))
                ->class('first')
                ->extra('colspan="2"'),
            'date' => (new Text('th', __('Date')))
                ->extra('scope="col"'),
            'author' => (new Text('th', __('Author')))
                ->extra('scope="col"'),
            'category' => (new Text('th', __('Category')))
                ->extra('scope="col"'),
            'status' => (new Text('th', __('Status')))
                ->extra('scope="col"'),
        ]);

        $this->userColumns(My::id() . 'posts', $cols);

        $lines = [];
        while ($this->rs->fetch()) {
            $lines[] = $this->line(isset($_POST['entries']) && in_array($this->rs->post_id, $_POST['entries']));
        }

        echo
        $pager->getLinks() .
        sprintf(
            $enclose_block,
            (new Div())
                ->class('table-outer')
                ->items([
                    (new Para(null, 'table'))
                        ->items([
                            (new Text(
                                'caption',
                                $filter->show() ?
                                sprintf(__('List of %s entries matching the filter.'), $this->rs_count) :
                                sprintf(__('List of entries. (%s)'), $this->rs_count)
                            )),
                            (new Para(null, 'tr'))
                                ->items(iterator_to_array($cols)),
                            (new Para(null, 'tbody'))
                                ->items($lines),
                        ]),
                ])
                ->render()
        ) .
        $pager->getLinks();
    }

    private function line(bool $checked): Para
    {
        $cat_title = (new Text('', __('None')));
        if ($this->rs->cat_title
            && dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcCore::app()->auth::PERMISSION_CATEGORIES]), dcCore::app()->blog?->id)
        ) {
            $cat_title = (new Link())
                ->href('category.php?id=' . $this->rs->cat_id)
                ->title(Html::escapeHTML(__('Edit category')))
                ->text(Html::escapeHTML($this->rs->cat_title));
        }

        switch ($this->rs->post_status) {
            case 1:
                $img_title = __('Published');
                $img_src   = 'check-on.png';
                $sts_class = ' sts-online';

                break;
            case -1:
                $img_title = __('Scheduled');
                $img_src   = 'scheduled.png';
                $sts_class = ' sts-scheduled';

                break;
            case -2:
                $img_title = __('Pending');
                $img_src   = 'check-wrn.png';
                $sts_class = ' sts-pending';

                break;
            default:
                $img_title = __('Unpublished');
                $img_src   = 'check-off.png';
                $sts_class = ' sts-offline';

                break;
        }

        $cols = new ArrayObject([
            'check' => (new Para(null, 'td'))
                ->class('nowrap minimal')
                ->items([
                    (new Checkbox(['entries[]'], $checked))
                        ->value($this->rs->post_id)
                        ->disabled(!$this->rs->isEditable()),
                ]),
            'title' => (new Para(null, 'td'))
                ->class('maximal')
                ->items([
                    (new Link())
                        ->href(dcCore::app()->getPostAdminURL($this->rs->post_type, $this->rs->post_id))
                        ->title(Html::escapeHTML($this->rs->getURL()))
                        ->text(Html::escapeHTML(trim(Html::clean($this->rs->post_title)))),
                ]),
            'date' => (new Text('td', Date::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt)))
                ->class('nowrap count'),
            'author' => (new Text('td', Html::escapeHTML($this->rs->user_id)))
                ->class('nowrap'),
            'category' => (new Para(null, 'td'))
                ->class('nowrap')
                ->items([$cat_title]),
            'status' => (new Para(null, 'td'))
                ->class('nowrap status')
                ->items([
                    (new Text('img', ''))
                        ->title($img_title)
                        ->extra('src="images/' . $img_src . '"'),
                ]),
        ]);

        $this->userColumns(My::id() . 'posts', $cols);

        return (new Para('p' . $this->rs->post_id, 'tr'))
            ->class('line' . ($this->rs->post_status != 1 ? ' offline ' : '') . $sts_class)
            ->items(iterator_to_array($cols));
    }
}
