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
    public function feedsDisplay($page, $nb_per_page, $enclose_block = '', $filter = false)
    {
        if ($this->rs->isEmpty()) {
            if ($filter) {
                echo '<p><strong>' . __('No feeds matches the filter') . '</strong></p>';
            } else {
                echo '<p><strong>' . __('No feeds') . '</strong></p>';
            }
        } else {
            $pager   = new dcPager($page, $this->rs_count, $nb_per_page, 10);
            $entries = [];
            if (isset($_REQUEST['feeds'])) {
                foreach ($_REQUEST['feeds'] as $v) {
                    $entries[(integer) $v] = true;
                }
            }
            $html_block = 
                '<div class="table-outer">' .
                '<table>' . 
                '<caption>' . ($filter ? 
                    sprintf(__('List of %s feeds matching the filter.'), $this->rs_count) :
                    sprintf(__('List of entries (%s)'), $this->rs_count)
                ) . '</caption>';

            $cols = [
                'title'   => '<th colspan="2" class="first">' . __('Name') . '</th>',
                'desc'    => '<th scope="col">' . __('Feed') . '</th>',
                'period'  => '<th scope="col">' . __('Frequency') . '</th>',
                'update'  => '<th scope="col">' . __('Last update') . '</th>',
                'entries' => '<th scope="col">' . __('Entries') . '</th>',
                'status'  => '<th scope="col">' . __('Status') . '</th>'
            ];
            $cols = new ArrayObject($cols);

            $this->core->callBehavior('adminZcfsFeedsListHeader', $this->core, $this->rs, $cols);

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
        $combo_status = zoneclearFeedServer::getAllStatus();
        $combo_upd_int = zoneclearFeedServer::getAllUpdateInterval();
        $status = $this->rs->feed_status ? 
            '<img src="images/check-on.png" alt="enable" />' :
            '<img src="images/check-off.png" alt="disable" />';

        $entries_count = $this->rs->zc->getPostsByFeed(['feed_id' => $this->rs->feed_id], true)->f(0);
        $shunk_feed = $this->rs->feed_feed;
        if (strlen($shunk_feed) > 83) {
            $shunk_feed = substr($shunk_feed,0,50).'...'.substr($shunk_feed,-20);
        }

        $url = 'plugin.php?p=zoneclearFeedServer&amp;part=feed&amp;feed_id=' . $this->rs->feed_id;

        $cols = [
            'check' => '<td class="nowrap minimal">' . 
                form::checkbox(['feeds[]'], $this->rs->feed_id, ['checked' => $checked]) . 
                '</td>',
            'title' => '<td class="nowrap" scope="row">' .
                '<a href="' . $url . '#feed" title="' . __('Edit') . '">' . html::escapeHTML($this->rs->feed_name) . '</a>' .
                '</td>',
            'desc'   => '<td class="nowrap maximal">' .
                '<a href="' . $this->rs->feed_feed . '" title="' . html::escapeHTML($this->rs->feed_desc) . '">' . html::escapeHTML($shunk_feed) . '</a>'. 
                '</td>',
            'period'       => '<td class="nowrap minimal count">' . 
                array_search($this->rs->feed_upd_int,$combo_upd_int) . 
                '</td>',
            'update'       => '<td class="nowrap minimal count">' . 
                ($this->rs->feed_upd_last < 1 ? 
                    __('never') : 
                    dt::str(__('%Y-%m-%d %H:%M'), $this->rs->feed_upd_last, $this->rs->zc->core->auth->getInfo('user_tz'))
                ) . '</td>',
            'entries'   => '<td class="nowrap minimal count">' . 
                ($entries_count ? 
                    '<a href="' . $url . '#entries" title="' . __('View entries') . '">' . $entries_count . '</a>' :
                    $entries_count
                ) . '</td>',
            'status'     => '<td class="nowrap minimal status">' . $status . '</td>'
        ];

        $cols = new ArrayObject($cols);
        $this->core->callBehavior('adminZcfsFeedsListValue', $this->core, $this->rs, $cols);

        $this->userColumns('zcfs_feeds', $cols);

        return 
            '<tr class="line ' . ($this->rs->feed_status ? '' : 'offline ') . '" id="p' . $this->rs->feed_id . '">' .
            implode(iterator_to_array($cols)) .
            '</tr>';
    }
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - Posts list methods
 * @since 2.6
 * @see  adminGenericList for more info
 */
class zcfsEntriesList extends adminGenericList
{
    public function display($page, $nb_per_page, $url, $enclose='')
    {
        if ($this->rs->isEmpty()) {

            return '<p><strong>'.__('No entry').'</strong></p>';
        }

        $pager = new dcPager($page, $this->rs_count, $nb_per_page, 10);
        $pager->base_url    = $url;
        $pager->html_prev    = $this->html_prev;
        $pager->html_next    = $this->html_next;
        $pager->var_page    = 'page';

        $html_block =
        '<div class="table-outer">'.
        '<table class="clear"><tr>'.
        '<th colspan="2">'.__('Title').'</th>'.
        '<th>'.__('Date').'</th>'.
        '<th>'.__('Category').'</th>'.
        '<th>'.__('Author').'</th>'.
        '<th>'.__('Comments').'</th>'.
        '<th>'.__('Trackbacks').'</th>'.
        '<th>'.__('Status').'</th>'.
        '</tr>%s</table></div>';

        $res = '';
        while ($this->rs->fetch()) {
            $res .= $this->postLine();
        }

        return 
            $pager->getLinks().
            sprintf($enclose, sprintf($html_block, $res)).
            $pager->getLinks();
    }

    private function postLine()
    {
        $cat_link = $this->core->auth->check('categories', $this->core->blog->id) ?
            '<a href="category.php?id=%s" title="'.__('Edit category').'">%s</a>' 
            : '%2$s';

        $cat_title = $this->rs->cat_title ? 
            sprintf($cat_link,$this->rs->cat_id, html::escapeHTML($this->rs->cat_title)) 
            : __('None');

        $img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        switch ($this->rs->post_status) {
            case 1:
                $img_status = sprintf($img, __('published'), 'check-on.png');
                break;
            case 0:
                $img_status = sprintf($img, __('unpublished'), 'check-off.png');
                break;
            case -1:
                $img_status = sprintf($img, __('scheduled'), 'scheduled.png');
                break;
            case -2:
                $img_status = sprintf($img, __('pending'), 'check-wrn.png');
                break;
        }

        return 
        '<tr class="line'.($this->rs->post_status != 1 ? ' offline' : '').'"'.
        ' id="p'.$this->rs->post_id.'">'.
        '<td class="nowrap">'.
        form::checkbox(array('entries[]'), $this->rs->post_id, '', '', '', !$this->rs->isEditable()).'</td>'.
        '<td class="maximal"><a href="'.$this->core->getPostAdminURL($this->rs->post_type, $this->rs->post_id).
        '" title="'.__('Edit entry').'">'.
        html::escapeHTML($this->rs->post_title).'</a></td>'.
        '<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt).'</td>'.
        '<td class="nowrap">'.$cat_title.'</td>'.
        '<td class="nowrap">'.$this->rs->user_id.'</td>'.
        '<td class="nowrap">'.$this->rs->nb_comment.'</td>'.
        '<td class="nowrap">'.$this->rs->nb_trackback.'</td>'.
        '<td class="nowrap status">'.$img_status.'</td>'.
        '</tr>';
    }
}