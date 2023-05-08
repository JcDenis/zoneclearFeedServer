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

use dcCore;

/**
 * This module definitions.
 */
class My
{
    /** @var    string  This module database table name */
    public const TABLE_NAME = 'zc_feed';

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

    /** @var    string  This module required php version */
    public const PHP_MIN = '8.1';

    /**
     * This module id.
     */
    public static function id(): string
    {
        return basename(dirname(__DIR__));
    }

    /**
     * This module name.
     */
    public static function name(): string
    {
        $name = dcCore::app()->plugins->moduleInfo(self::id(), 'name');

        return __(is_string($name) ? $name : self::id());
    }

    /**
     * This module path.
     */
    public static function path(): string
    {
        return dirname(__DIR__);
    }

    /**
     * Check this module PHP version compliant.
     */
    public static function phpCompliant(): bool
    {
        return version_compare(phpversion(), self::PHP_MIN, '>=');
    }
}
