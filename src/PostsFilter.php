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
 * @brief Feeds server - Posts list filters methods
 * @since 2.20
 * @see  adminGenericFilter for more info
 */
class zcfsPostFilter extends adminGenericFilter
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
