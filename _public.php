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

# Namespace for settings
dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
$s = dcCore::app()->blog->settings->__get(basename(__DIR__));

# Widgets
require_once __DIR__ . '/_widgets.php';

dcCore::app()->addBehavior('coreBlogGetPosts', ['zcfsPublicBehaviors', 'coreBlogGetPosts']);

if (!$s->active) {
    return null;
}
if (1 == $s->bhv_pub_upd) {
    dcCore::app()->addBehavior('publicBeforeDocumentV2', ['zcfsPublicBehaviors', 'publicDocumentV2']);
} elseif (2 == $s->bhv_pub_upd) {
    dcCore::app()->addBehavior('publicAfterDocumentV2', ['zcfsPublicBehaviors', 'publicAfterDocumentV2']);
} elseif (3 == $s->bhv_pub_upd) {
    dcCore::app()->addBehavior('publicHeadContent', ['zcfsPublicBehaviors', 'publicHeadContent']);
}

# Take care about tweakurls (thanks Mathieu M.)
if (version_compare(dcCore::app()->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {
    dcCore::app()->addbehavior('zoneclearFeedServerAfterPostCreate', ['zoneclearFeedServer', 'tweakurlsAfterPostCreate']);
}

# Register tempalte blocks
$tpl_blocks = [
    'Feeds',
    'FeedsFooter',
    'FeedsHeader',
    'FeedIf',
];
foreach ($tpl_blocks as $v) {
    dcCore::app()->tpl->addBlock('zc' . $v, ['zcfsTemplate', $v]);
}

# Register tempalte values
$tpl_values = [
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
foreach ($tpl_values as $v) {
    dcCore::app()->tpl->addValue('zc' . $v, ['zcfsTemplate', $v]);
}

dcCore::app()->addBehavior('publicBreadcrumb', function ($context, $separator) {
    if ($context == 'zoneclearFeedsPage') {
        return __('List of feeds');
    }
});
