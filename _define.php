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

if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'Feeds server',
    'Mix your blog with a feeds planet',
    'Jean-Christian Denis, BG, Pierre Van Glabeke',
    '2015.07.19',
    [
        'requires'    => [['core', '2.19']],
        'permissions' => 'admin',
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/zoneclearFeedServer',
        'details'     => 'https://plugins.dotaddict.org/dc2/details/pacKman',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/zoneclearFeedServer/master/dcstore.xml'
    ]
);