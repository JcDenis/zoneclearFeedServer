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
        $cat_link = dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([dcAuth::PERMISSION_CATEGORIES]), dcCore::app()->blog->id) ?
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
