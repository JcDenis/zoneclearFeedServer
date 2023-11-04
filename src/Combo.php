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

use Dotclear\App;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       zoneclearFeedServer backend combo helper.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Combo
{
    /**
     * @return  array<string, string>
     */
    public static function feedsSortby(): array
    {
        return [
            __('Date')        => 'feed_upddt',
            __('Name')        => 'lowername',
            __('Frequency')   => 'feed_upd_int',
            __('Update date') => 'feed_upd_last',
            __('Status')      => 'feed_status',
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function postsSortby(): array
    {
        return [
            __('Date')     => 'post_dt',
            __('Title')    => 'post_title',
            __('Category') => 'cat_title',
            __('Author')   => 'user_id',
            __('Status')   => 'post_status',
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function feedsStatus(): array
    {
        return [
            __('Disabled') => '0',
            __('Enabled')  => '1',
        ];
    }

    /**
     * @return  array<string, int>
     */
    public static function postsStatus(): array
    {
        return [
            __('Unpublished') => 0,
            __('Published')   => 1,
        ];
    }

    /**
     * @return  array<string, int>
     */
    public static function updateInterval(): array
    {
        return [
            __('Every hour')        => 3600,
            __('Every two hours')   => 7200,
            __('Two times per day') => 43200,
            __('Every day')         => 86400,
            __('Every two days')    => 172800,
            __('Every week')        => 604800,
        ];
    }

    /**
     * @return  array<string, int>
     */
    public static function tagCase(): array
    {
        return [
            __('Keep source case') => 0,
            __('First upper case') => 1,
            __('All lower case')   => 2,
            __('All upper case')   => 3,
        ];
    }

    /**
     * @return  array<string, int>
     */
    public static function pubUpdate(): array
    {
        return [
            __('Disable')        => 0,
            __('Before display') => 1,
            __('After display')  => 2,
            __('Through Ajax')   => 3,
        ];
    }

    /**
     * @return  array<string, string>
     */
    public static function postCategories(): array
    {
        $combo = ['-' => ''];

        try {
            $categories = App::blog()->getCategories(['post_type' => 'post']);

            while ($categories->fetch()) {
                $level     = is_numeric($categories->f('level')) ? (int) $categories->f('level') : 1;
                $cat_title = is_string($categories->f('cat_title')) ? $categories->f('cat_title') : '';
                $cat_id    = is_numeric($categories->f('cat_id')) ? (string) $categories->f('cat_id') : '';

                $combo[
                    str_repeat('&nbsp;&nbsp;', $level - 1) .
                    '&bull; ' . Html::escapeHTML($cat_title)
                ] = $cat_id;
            }
        } catch (Exception $e) {
        }

        return $combo;
    }
}
