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
    '2023.11.04',
    [
        'requires'    => [['core', '2.28']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2025-02-24T23:31:12+00:00',
    ]
);
