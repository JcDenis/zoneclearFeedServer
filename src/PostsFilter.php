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
use adminGenericFilterV2;
use dcAdminCombos;
use dcAdminFilter;
use dcAdminFilters;
use dcCore;
use dcUtils;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * Backend feed posts list filters.
 */
class PostsFilter extends adminGenericFilterV2
{
    public function __construct()
    {
        // use user posts pref
        parent::__construct('posts');

        $filters = new ArrayObject([
            dcAdminFilters::getPageFilter(),
            $this->getPostUserFilter(),
            $this->getPostCategoriesFilter(),
            $this->getPostStatusFilter(),
            $this->getPostMonthFilter(),
        ]);

        # --BEHAVIOR-- zcfsPostFilter
        dcCore::app()->callBehavior('zcfsPostFilter', $filters);

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
            $users = dcCore::app()->blog?->getPostsUsers();
            if (is_null($users) || $users->isEmpty()) {
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
            $categories = dcCore::app()->blog?->getCategories();
            if (is_null($categories) || $categories->isEmpty()) {
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
            if (is_numeric($categories->f('level')) && is_string($categories->f('cat_title'))) {
                $combo[
                    str_repeat('&nbsp;', ((int) $categories->f('level') - 1) * 4) .
                    Html::escapeHTML($categories->f('cat_title')) . ' (' . $categories->f('nb_post') . ')'
                ] = $categories->f('cat_id');
            }
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
            $dates = dcCore::app()->blog?->getDates(['type' => 'month']);
            if (is_null($dates) || $dates->isEmpty()) {
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