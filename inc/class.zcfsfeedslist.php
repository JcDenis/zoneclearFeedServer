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
 * @brief Feeds server - feeds list methods
 * @since 2.6
 * @see  adminGenericList for more info
 */
class zcfsFeedsList extends adminGenericList
{
    private $zc = null;

    public function feedsDisplay($page, $nb_per_page, $enclose_block = '', $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No feeds matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No feeds') . '</strong></p>';
            }
        } else {
            $this->zc = new zoneclearFeedServer();
            $pager    = new dcPager($page, $this->rs_count, $nb_per_page, 10);
            $entries  = [];
            if (isset($_REQUEST['feeds'])) {
                foreach ($_REQUEST['feeds'] as $v) {
                    $entries[(int) $v] = true;
                }
            }
            $html_block = '<div class="table-outer">' .
                '<table>' .
                '<caption>' . (
                    $filter ?
                    sprintf(__('List of %s feeds matching the filter.'), $this->rs_count) :
                    sprintf(__('List of feeds (%s)'), $this->rs_count)
                ) . '</caption>';

            $cols = [
                'title'   => '<th colspan="2" class="first nowrap">' . __('Name') . '</th>',
                'desc'    => '<th class="nowrap" scope="col">' . __('Feed') . '</th>',
                'period'  => '<th class="nowrap" scope="col">' . __('Frequency') . '</th>',
                'update'  => '<th class="nowrap" scope="col">' . __('Last update') . '</th>',
                'entries' => '<th class="nowrap" scope="col">' . __('Entries') . '</th>',
                'status'  => '<th class="nowrap" scope="col">' . __('Status') . '</th>',
            ];
            $cols = new ArrayObject($cols);

            dcCore::app()->callBehavior('adminZcfsFeedsListHeader', $this->rs, $cols);

            $this->userColumns('zcfs_feeds', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table>%s</div>';
            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->feedsLine(isset($entries[$this->rs->feed_id]));
            }

            echo $blocks[1];
            echo $blocks[2];
            echo $pager->getLinks();
        }
    }

    private function feedsLine($checked)
    {
        $combo_status  = zoneclearFeedServer::getAllStatus();
        $combo_upd_int = zoneclearFeedServer::getAllUpdateInterval();
        $status        = $this->rs->feed_status ?
            '<img src="images/check-on.png" alt="enable" />' :
            '<img src="images/check-off.png" alt="disable" />';

        $entries_count = $this->zc->getPostsByFeed(['feed_id' => $this->rs->feed_id], true)->f(0);
        $shunk_feed    = $this->rs->feed_feed;
        if (strlen($shunk_feed) > 83) {
            $shunk_feed = substr($shunk_feed, 0, 50) . '...' . substr($shunk_feed, -20);
        }

        $url = dcCore::app()->adminurl->get('admin.plugin.' . basename(dirname('../' . __DIR__)), ['part' => 'feed', 'feed_id' => $this->rs->feed_id]);

        $cols = [
            'check' => '<td class="nowrap minimal">' .
                form::checkbox(['feeds[]'], $this->rs->feed_id, ['checked' => $checked]) .
                '</td>',
            'title' => '<td class="nowrap" scope="row">' .
                '<a href="' . $url . '#feed" title="' . __('Edit') . '">' . html::escapeHTML($this->rs->feed_name) . '</a>' .
                '</td>',
            'desc' => '<td class="nowrap maximal">' .
                '<a href="' . $this->rs->feed_feed . '" title="' . html::escapeHTML($this->rs->feed_desc) . '">' . html::escapeHTML($shunk_feed) . '</a>' .
                '</td>',
            'period' => '<td class="nowrap minimal count">' .
                array_search($this->rs->feed_upd_int, $combo_upd_int) .
                '</td>',
            'update' => '<td class="nowrap minimal count">' .
                (
                    $this->rs->feed_upd_last < 1 ?
                    __('never') :
                    dt::str(__('%Y-%m-%d %H:%M'), (int) $this->rs->feed_upd_last, dcCore::app()->auth->getInfo('user_tz'))
                ) . '</td>',
            'entries' => '<td class="nowrap minimal count">' .
                (
                    $entries_count ?
                    '<a href="' . $url . '#entries" title="' . __('View entries') . '">' . $entries_count . '</a>' :
                    $entries_count
                ) . '</td>',
            'status' => '<td class="nowrap minimal status">' . $status . '</td>',
        ];

        $cols = new ArrayObject($cols);
        dcCore::app()->callBehavior('adminZcfsFeedsListValue', $this->rs, $cols);

        $this->userColumns('zcfs_feeds', $cols);

        return
            '<tr class="line ' . ($this->rs->feed_status ? '' : 'offline ') . '" id="p' . $this->rs->feed_id . '">' .
            implode(iterator_to_array($cols)) .
            '</tr>';
    }
}
