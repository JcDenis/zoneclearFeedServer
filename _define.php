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

if (!isset($this) || !is_object($this) || !method_exists($this, 'registerModule') || !isset($this->id) || !is_string($this->id)) {
    return;
}

$this->registerModule(
    'Feeds server',
    'Mix your blog with a feeds planet',
    'Jean-Christian Denis, BG, Pierre Van Glabeke',
    '2026.08.13',
    [
        'requires'    => [['core', '2.39']],
        'permissions' => 'My',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/' . $this->id . '/issues',
        'details'     => 'https://github.com/JcDenis/' . $this->id . '/',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/' . $this->id . '/master/dcstore.xml',
        'date'        => '2026-08-13T21:49:46+00:00',
    ]
);
