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

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - public methods.
 * @since 2.6
 */
class zcfsPublicBehaviors
{
    /**
     * Remember others post extension.
     *
     * @param  dcRecord $rs record instance
     */
    public static function coreBlogGetPosts(dcRecord $rs)
    {
        $GLOBALS['beforeZcFeedRsExt'] = $rs->extensions();
        $rs->extend('zcfsRsExtPosts');
    }

    /**
     * Update feeds after contents.
     */
    public static function publicAfterDocumentV2()
    {
        # Limit feeds update to home page et feed page
        # Like publishScheduledEntries
        if (!in_array(dcCore::app()->url->type, ['default', 'feed'])) {
            return null;
        }

        self::publicDocument();
    }

    /**
     * Generic behavior for before and after public content.
     */
    public static function publicDocumentV2()
    {
        $zc = new zoneclearFeedServer();
        $zc->checkFeedsUpdate();

        return null;
    }

    /**
     * Update feeds by an Ajax request (background).
     */
    public static function publicHeadContent()
    {
        # Limit update to home page
        if (dcCore::app()->url->type != 'default') {
            return null;
        }

        $blog_url = html::escapeJS(
            dcCore::app()->blog->url .
            dcCore::app()->url->getBase('zoneclearFeedsPage') .
            '/zcfsupd'
        );
        $blog_id = html::escapeJS(dcCore::app()->blog->id);

        echo
        "\n<!-- JS for zoneclearFeedServer --> \n" .
        dcUtils::jsLoad(dcCore::app()->blog->url . dcCore::app()->url->getBase('zoneclearFeedsPage') . '/zcfsupd.js') .
        "<script type=\"text/javascript\"> \n//<![CDATA[\n" .
        ' $(function(){if(!document.getElementById){return;} ' .
        " $('body').zoneclearFeedServer({blog_url:'" .
            $blog_url . "',blog_id:'" . $blog_id . "'}); " .
        " })\n" .
        "//]]>\n</script>\n";
    }
}
