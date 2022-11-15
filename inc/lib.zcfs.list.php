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
                'title'   => '<th colspan="2" class="first">' . __('Name') . '</th>',
                'desc'    => '<th scope="col">' . __('Feed') . '</th>',
                'period'  => '<th scope="col">' . __('Frequency') . '</th>',
                'update'  => '<th scope="col">' . __('Last update') . '</th>',
                'entries' => '<th scope="col">' . __('Entries') . '</th>',
                'status'  => '<th scope="col">' . __('Status') . '</th>',
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

        $url = 'plugin.php?p=zoneclearFeedServer&amp;part=feed&amp;feed_id=' . $this->rs->feed_id;

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

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - Posts list methods
 * @since 2.6
 * @see  adminGenericList for more info
 */
class zcfsEntriesList extends adminGenericList
{
    public function display($page, $nb_per_page, $base_url, $enclose_block = '', $filter = false)
    {
        if ($this->rs->isEmpty()) {
            echo '<p><strong>' . (
                $filter ?
                    __('No entries matches the filter') :
                    __('No entries')
            ) . '</strong></p>';
        } else {
            $pager           = new dcPager($page, $this->rs_count, $nb_per_page, 10);
            $pager->base_url = $base_url;

            $entries = [];
            if (isset($_REQUEST['feeds'])) {
                foreach ($_REQUEST['feeds'] as $v) {
                    $entries[(int) $v] = true;
                }
            }

            $html_block = '<div class="table-outer clear">' .
            '<table>' .
                '<caption>' . (
                    $filter ?
                    sprintf(__('List of %s entries matching the filter.'), $this->rs_count) :
                    sprintf(__('List of entries (%s)'), $this->rs_count)
                ) . '</caption>';

            $cols = [
                'title'    => '<th scope="col" colspan="2" class="first">' . __('Title') . '</th>',
                'date'     => '<th scope="col">' . __('Date') . '</th>',
                'author'   => '<th scope="col">' . __('Author') . '</th>',
                'category' => '<th scope="col">' . __('Category') . '</th>',
                'status'   => '<th scope="col">' . __('Status') . '</th>',
            ];

            $cols = new ArrayObject($cols);
            dcCore::app()->callBehavior('adminZcfsPostListHeader', $this->rs, $cols);

            $this->userColumns('zcfs_entries', $cols);

            $html_block .= '<tr>' . implode(iterator_to_array($cols)) . '</tr>%s</table></div>';
            if ($enclose_block) {
                $html_block = sprintf($enclose_block, $html_block);
            }

            echo $pager->getLinks();

            $blocks = explode('%s', $html_block);

            echo $blocks[0];

            while ($this->rs->fetch()) {
                echo $this->postLine();
            }

            echo $blocks[1];

            echo $pager->getLinks();
        }
    }

    private function postLine()
    {
        $cat_link = dcCore::app()->auth->check('categories', dcCore::app()->blog->id) ?
            '<a href="category.php?id=%s" title="' . __('Edit category') . '">%s</a>'
            : '%2$s';

        $cat_title = $this->rs->cat_title ?
            sprintf($cat_link, $this->rs->cat_id, html::escapeHTML($this->rs->cat_title))
            : __('None');

        $img        = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
        $img_status = '';
        $sts_class  = '';
        switch ($this->rs->post_status) {
            case 1:
                $img_status = sprintf($img, __('Published'), 'check-on.png');
                $sts_class  = 'sts-online';

                break;
            case 0:
                $img_status = sprintf($img, __('Unpublished'), 'check-off.png');
                $sts_class  = 'sts-offline';

                break;
            case -1:
                $img_status = sprintf($img, __('Scheduled'), 'scheduled.png');
                $sts_class  = 'sts-scheduled';

                break;
            case -2:
                $img_status = sprintf($img, __('Pending'), 'check-wrn.png');
                $sts_class  = 'sts-pending';

                break;
        }

        $res = '<tr class="line ' . ($this->rs->post_status != 1 ? 'offline ' : '') . $sts_class . '"' .
        ' id="p' . $this->rs->post_id . '">';

        $cols = [
            'check' => '<td class="nowrap minimal">' .
                form::checkbox(['entries[]'], $this->rs->post_id, '', '', '', !$this->rs->isEditable()) . '</td>',
            'title' => '<td scope="row" class="maximal"><a href="' .
                dcCore::app()->getPostAdminURL($this->rs->post_type, $this->rs->post_id) . '" ' .
                'title="' . html::escapeHTML($this->rs->getURL()) . '">' .
                html::escapeHTML(trim(html::clean($this->rs->post_title))) . '</a></td>',
            'date'     => '<td class="nowrap count">' . dt::dt2str(__('%Y-%m-%d %H:%M'), $this->rs->post_dt) . '</td>',
            'author'   => '<td class="nowrap">' . html::escapeHTML($this->rs->user_id) . '</td>',
            'category' => '<td class="nowrap">' . $cat_title . '</td>',
            'status'   => '<td class="nowrap status">' . $img_status . '</td>',
        ];

        $cols = new ArrayObject($cols);
        dcCore::app()->callBehavior('adminZcfsPostListValue', $this->rs, $cols);

        $this->userColumns('zcfs_entries', $cols);

        $res .= implode(iterator_to_array($cols));
        $res .= '</tr>';

        return $res;
    }
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Feeds server - Posts list filters methods
 * @since 2.20
 * @see  adminGenericFilter for more info
 */
class adminZcfsPostFilter extends adminGenericFilter
{
    public function __construct()
    {
        parent::__construct(dcCore::app(), 'zcfs_entries');

        $filters = new arrayObject([
            dcAdminFilters::getPageFilter(),
            $this->getPostUserFilter(),
            $this->getPostCategoriesFilter(),
            $this->getPostStatusFilter(),
            $this->getPostMonthFilter(),
        ]);

        # --BEHAVIOR-- adminPostFilter
        dcCore::app()->callBehavior('adminZcfsPostFilter', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Posts users select
     */
    public function getPostUserFilter(): ?dcAdminFilter
    {
        $users = null;

        try {
            $users = dcCore::app()->blog->getPostsUsers();
            if ($users->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        $combo = dcAdminCombos::getUsersCombo($users);
        dcUtils::lexicalKeySort($combo);

        return (new dcAdminFilter('user_id'))
            ->param()
            ->title(__('Author:'))
            ->options(array_merge(
                ['-' => ''],
                $combo
            ))
            ->prime(true);
    }

    /**
     * Posts categories select
     */
    public function getPostCategoriesFilter(): ?dcAdminFilter
    {
        $categories = null;

        try {
            $categories = dcCore::app()->blog->getCategories();
            if ($categories->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        $combo = [
            '-'            => '',
            __('(No cat)') => 'NULL',
        ];
        while ($categories->fetch()) {
            $combo[
                str_repeat('&nbsp;', ($categories->level - 1) * 4) .
                html::escapeHTML($categories->cat_title) . ' (' . $categories->nb_post . ')'
            ] = $categories->cat_id;
        }

        return (new dcAdminFilter('cat_id'))
            ->param()
            ->title(__('Category:'))
            ->options($combo)
            ->prime(true);
    }

    /**
     * Posts status select
     */
    public function getPostStatusFilter(): dcAdminFilter
    {
        return (new dcAdminFilter('status'))
            ->param('post_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getPostStatusesCombo()
            ));
    }

    /**
     * Posts by month select
     */
    public function getPostMonthFilter(): ?dcAdminFilter
    {
        $dates = null;

        try {
            $dates = dcCore::app()->blog->getDates(['type' => 'month']);
            if ($dates->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return null;
        }

        return (new dcAdminFilter('month'))
            ->param('post_month', function ($f) { return substr($f[0], 4, 2); })
            ->param('post_year', function ($f) { return substr($f[0], 0, 4); })
            ->title(__('Month:'))
            ->options(array_merge(
                ['-' => ''],
                dcAdminCombos::getDatesCombo($dates)
            ));
    }
}
