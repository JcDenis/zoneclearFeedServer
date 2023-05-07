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
use dcNsProcess;

/**
 * Module prepend.
 */
class Prepend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        // public url for page of description of the flux
        dcCore::app()->url->register(
            'zoneclearFeedsPage',
            'zcfeeds',
            '^zcfeeds(.*?)$',
            [UrlHandler::class, 'zoneclearFeedsPage']
        );

        // report zoneclearFeedServer activities
        if (defined('ACTIVITY_REPORT') && ACTIVITY_REPORT == 3) {
            ActivityReportActions::init();
        }

        return true;
    }
}
