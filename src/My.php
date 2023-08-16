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

use Dotclear\Module\MyPlugin;

/**
 * This module definitions.
 */
class My extends MyPlugin
{
    /** @var    string  This module database table name */
    public const TABLE_NAME = \initZoneclearFeedServer::TABLE_NAME;

    /** @var    string  This module meta prefix */
    public const META_PREFIX = 'zoneclearfeed_';

    /** @var    array<int,string>   This module template blocks */
    public const TPL_BLOCKS = [
        'Feeds',
        'FeedsFooter',
        'FeedsHeader',
        'FeedIf',
    ];

    /** @var    array<int,string>   This module template values */
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

    public static function checkCustomContext(int $context): ?bool
    {
        return $context == My::BACKEND ? defined('DC_CONTEXT_ADMIN') : null;
    }
}
