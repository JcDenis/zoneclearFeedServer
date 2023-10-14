<?php
/**
 * @file
 * @brief       The plugin zoneclearFeedServer definition
 * @ingroup     zoneclearFeedServer
 *
 * @defgroup    zoneclearFeedServer Plugin zoneclearFeedServer.
 *
 * Mix your blog with a feeds planet.
 *
 * @author      Jean-Christian Denis (author)
 * @author      Pierre Van Glabeke
 * @author      BG
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

$this->registerModule(
    'Feeds server',
    'Mix your blog with a feeds planet',
    'Jean-Christian Denis, BG, Pierre Van Glabeke',
    '2023.10.14',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/issues',
        'details'     => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/src/branch/master/README.md',
        'repository'  => 'https://git.dotclear.watch/JcDenis/' . basename(__DIR__) . '/raw/branch/master/dcstore.xml',
    ]
);
