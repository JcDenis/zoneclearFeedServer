<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\Module\MyPlugin;

/**
 * @brief       zoneclearFeedServer My helper.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    /**
     * This module database table name.
     *
     * @var     string  TABLE_NAME
     */
    public const TABLE_NAME = 'zc_feed';

    /**
     * This module meta prefix.
     *
     * @var     string  META_PREFIX
     */
    public const META_PREFIX = 'zoneclearfeed_';

    /**
     * This module template blocks.
     *
     * @var     array<int,string>   TPL_BLOCKS
     */
    public const TPL_BLOCKS = [
        'Feeds',
        'FeedsFooter',
        'FeedsHeader',
        'FeedIf',
    ];

    /**
     * This module template values.
     *
     * @var     array<int,string>   TPL_VALUES
     */
    public const TPL_VALUES = [
        'FeedsCount',
        'FeedsEntriesCount',
        'FeedEntriesCount',
        'FeedCategory',
        'FeedCategoryID',
        'FeedCategoryURL',
        'FeedCategoryShortURL',
        'FeedID',
        'FeedIfFirst',
        'FeedIfOdd',
        'FeedLang',
        'FeedName',
        'FeedOwner',
        'FeedDesc',
        'FeedSiteURL',
        'FeedFeedURL',
    ];

    // Use default permissions
}
