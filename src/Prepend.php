<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Helper\Process\TraitProcess;

/**
 * @brief       zoneclearFeedServer prepend class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Prepend
{
    use TraitProcess;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::PREPEND));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        // public url for page of description of the flux
        App::url()->register(
            'zoneclearFeedsPage',
            'zcfeeds',
            '^zcfeeds(.*?)$',
            UrlHandler::zoneclearFeedsPage(...)
        );

        return true;
    }
}
