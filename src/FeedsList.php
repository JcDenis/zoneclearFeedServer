<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Filter\Filters;
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
 * @brief       zoneclearFeedServer backend feeds list.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FeedsList extends Listing
{
    public function display(Filters $filter, string $enclose_block = ''): void
    {
        if ($this->rs->isEmpty()) {
            echo
            (new Text(
                'p',
                $filter->show() ?
                    __('No feeds matches the filter') :
                    __('No feeds')
            ))
                ->class('info')
                ->render();

            return;
        }

        $page  = is_numeric($filter->value('page')) ? (int) $filter->value('page') : 1;
        $nbpp  = is_numeric($filter->value('nb')) ? (int) $filter->value('nb') : 10;
        $count = (int) $this->rs_count;
        $pager = new Pager($page, $count, $nbpp, 10);

        $cols = new ArrayObject([
            'title' => (new Text('th', __('Name')))
                ->class('first')
                ->extra('colspan="2"'),
            'desc' => (new Text('th', __('Feed')))
                ->extra('scope="col"'),
            'period' => (new Text('th', __('Frequency')))
                ->extra('scope="col"'),
            'update' => (new Text('th', __('Last update')))
                ->extra('scope="col"')->class('nowrap'),
            'entries' => (new Text('th', __('Entries')))
                ->extra('scope="col"'),
            'status' => (new Text('th', __('Status')))
                ->extra('scope="col"'),
        ]);

        $this->userColumns(My::id() . 'feeds', $cols);

        $lines = [];
        while ($this->rs->fetch()) {
            $lines[] = $this->line(isset($_POST['feeds']) && in_array($this->rs->post_id, $_POST['feeds']));
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
                                sprintf(__('List of %s feeds matching the filter.'), $this->rs_count) :
                                sprintf(__('List of feeds. (%s)'), $this->rs_count)
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
        $row       = new FeedRow($this->rs);
        $img_title = $row->status ? __('enabled') : __('disabled');
        $img_src   = $row->status ? 'check-on.png' : 'check-off.png';

        $entries_count = ZoneclearFeedServer::instance()->getPostsByFeed(['feed_id' => $row->id], true)->f(0);
        if (!is_numeric($entries_count)) {
            $entries_count = 0;
        }

        $shunk_feed = $row->feed;
        if (strlen($shunk_feed) > 83) {
            $shunk_feed = substr($shunk_feed, 0, 50) . '...' . substr($shunk_feed, -20);
        }

        $url = My::manageUrl(['part' => 'feed', 'feed_id' => $row->id]);
        if (!is_string($url)) {
            $url = '';
        }
        $tz = App::auth()->getInfo('user_tz');
        if (!is_string($tz)) {
            $tz = 'UTC';
        }

        $cols = new ArrayObject([
            'check' => (new Para(null, 'td'))
                ->class('nowrap minimal')
                ->items([
                    (new Checkbox(['feeds[]'], $checked))
                        ->value($row->id),
                ]),
            'title' => (new Para(null, 'td'))
                ->class('nowrap')
                ->items([
                    (new Link())
                        ->title(__('Edit'))
                        ->text(Html::escapeHTML($row->name))
                        ->href($url . '#feed'),
                ]),
            'desc' => (new Para(null, 'td'))
                ->class('nowrap maximal')
                ->items([
                    (new Link())
                        ->title(Html::escapeHTML($row->desc))
                        ->text(Html::escapeHTML($shunk_feed))
                        ->href($row->feed),
                ])
                ->class('nowrap minimal'),
            'period' => (new Text('td', (string) array_search($row->upd_int, Combo::updateInterval())))
                ->class('nowrap minimal'),
            'update' => (new Text(
                'td',
                $row->upd_last < 1 ?
                    __('never') :
                    Date::str(__('%Y-%m-%d %H:%M'), $row->upd_last, $tz)
            ))
                ->class('nowrap minimal'),
            'entries' => (new Para(null, 'td'))
                ->class('nowrap minimal count')
                ->items([
                    (new Link())
                        ->title(Html::escapeHTML(__('View entries')))
                        ->text(Html::escapeHTML((string) $entries_count))
                        ->href($url . '#entries'),
                ]),
            'status' => (new Para(null, 'td'))
                ->class('nowrap minimal status')
                ->items([
                    (new Text('img', ''))
                        ->title($img_title)
                        ->extra('src="images/' . $img_src . '"'),
                ]),
        ]);

        $this->userColumns(My::id() . 'feeds', $cols);

        return (new Para('p' . $row->id, 'tr'))
            ->class('line' . ($row->status != 1 ? ' offline ' : ''))
            ->items(iterator_to_array($cols));
    }
}
