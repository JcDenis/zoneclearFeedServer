<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Combos;
use Dotclear\Core\Backend\Filter\{
    Filter,
    Filters,
    FiltersLibrary
};
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       zoneclearFeedServer backend feeds posts list filter.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class PostsFilter extends Filters
{
    public function __construct()
    {
        // use user posts pref
        parent::__construct('posts');

        $filters = new ArrayObject([
            FiltersLibrary::getPageFilter(),
            $this->getPostUserFilter(),
            $this->getPostCategoriesFilter(),
            $this->getPostStatusFilter(),
            $this->getPostMonthFilter(),
        ]);

        # --BEHAVIOR-- zcfsPostFilter
        App::behavior()->callBehavior('zcfsPostFilter', $filters);

        $filters = $filters->getArrayCopy();

        $this->add($filters);
    }

    /**
     * Posts users select
     *
     * @return null|Filter
     */
    public function getPostUserFilter()
    {
        $users = null;

        try {
            $users = App::blog()->getPostsUsers();
            if ($users->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        $combo = Combos::getUsersCombo($users);
        App::lexical()->lexicalKeySort($combo);

        return (new Filter('user_id'))
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
     *
     * @return null|Filter
     */
    public function getPostCategoriesFilter()
    {
        $categories = null;

        try {
            $categories = App::blog()->getCategories();
            if ($categories->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

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

        return (new Filter('cat_id'))
            ->param()
            ->title(__('Category:'))
            ->options($combo)
            ->prime(true);
    }

    /**
     * Posts status select
     */
    public function getPostStatusFilter(): Filter
    {
        return (new Filter('status'))
            ->param('post_status')
            ->title(__('Status:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getPostStatusesCombo()
            ));
    }

    /**
     * Posts by month select
     *
     * @return null|Filter
     */
    public function getPostMonthFilter()
    {
        $dates = null;

        try {
            $dates = App::blog()->getDates(['type' => 'month']);
            if ($dates->isEmpty()) {
                return null;
            }
        } catch (Exception $e) {
            App::error()->add($e->getMessage());

            return null;
        }

        return (new Filter('month'))
            ->param('post_month', function ($f) { return substr($f[0], 4, 2); })
            ->param('post_year', function ($f) { return substr($f[0], 0, 4); })
            ->title(__('Month:'))
            ->options(array_merge(
                ['-' => ''],
                Combos::getDatesCombo($dates)
            ));
    }
}
