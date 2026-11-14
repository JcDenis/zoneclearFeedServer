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
\Dotclear\App::backend()->resources()->set('help', 'zoneclearFeedServer-config', __DIR__ . '/help/config.html');
\Dotclear\App::backend()->resources()->set('help', 'zoneclearFeedServer-feeds', __DIR__ . '/help/feeds.html');
\Dotclear\App::backend()->resources()->set('help', 'zoneclearFeedServer-feed', __DIR__ . '/help/feed.html');
