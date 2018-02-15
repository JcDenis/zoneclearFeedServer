<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
#
# This file is part of zoneclearFeedServer, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2015 Jean-Christian Denis and contributors
# contact@jcdenis.fr http://jcd.lv
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) {
    return null;
}

# Namespace for settings
$core->blog->settings->addNamespace('zoneclearFeedServer');
$s = $core->blog->settings->zoneclearFeedServer;

# Widgets
require_once dirname(__FILE__).'/_widgets.php';

$core->addBehavior(
	'coreBlogGetPosts',
	array('zcfsPublicBehaviors', 'coreBlogGetPosts')
);

if (!$s->zoneclearFeedServer_active) {

	return null;
}
if (1 == $s->zoneclearFeedServer_bhv_pub_upd) {
	$core->addBehavior(
		'publicBeforeDocument',
		array('zcfsPublicBehaviors', 'publicDocument')
	);
}
elseif (2 == $s->zoneclearFeedServer_bhv_pub_upd) {
	$core->addBehavior(
		'publicAfterDocument',
		array('zcfsPublicBehaviors', 'publicAfterDocument')
	);
}
elseif (3 == $s->zoneclearFeedServer_bhv_pub_upd) {
	$core->addBehavior(
		'publicHeadContent',
		array('zcfsPublicBehaviors', 'publicHeadContent')
	);
}

# Take care about tweakurls (thanks Mathieu M.)
if (version_compare($core->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {
	$core->addbehavior(
		'zoneclearFeedServerAfterPostCreate',
		array('zoneclearFeedServer', 'tweakurlsAfterPostCreate')
	);
}

# Register tempalte blocks
$tpl_blocks = array(
	'Feeds',
	'FeedsFooter',
	'FeedsHeader',
	'FeedIf'
);
foreach($tpl_blocks as $v) {
	$core->tpl->addBlock('zc'.$v, array('zcfsTemplate', $v));
}

# Register tempalte values
$tpl_values = array(
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
	'FeedFeedURL'
);
foreach($tpl_values as $v) {
	$core->tpl->addValue('zc'.$v, array('zcfsTemplate', $v));
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
	 * @param  record $rs record instance
	 */
	public static function coreBlogGetPosts(record $rs)
	{
		$GLOBALS['beforeZcFeedRsExt'] = $rs->extensions();
		$rs->extend('zcfsRsExtPosts');
	}

	/**
	 * Update feeds after contents.
	 * 
	 * @param  dcCore $core dcCore instance
	 * @return string       Document
	 */
	public static function publicAfterDocument(dcCore $core)
	{
		# Limit feeds update to home page et feed page
		# Like publishScheduledEntries
		if (!in_array($core->url->type, array('default', 'feed'))) {

			return null;
		}

		self::publicDocument($core);
	}

	/**
	 * Generic behavior for before and after public content.
	 * 
	 * @param  dcCore $core dcCore instance
	 * @return null       NULL
	 */
	public static function publicDocument(dcCore $core)
	{
		$zc = new zoneclearFeedServer($core);
		$zc->checkFeedsUpdate();

		return null;
	}

	/**
	 * Update feeds by an Ajax request (background).
	 * 
	 * @param  dcCore  $core dcCore instance
	 * @param  context $_ctx context instance
	 * @return string        HTML content
	 */
	public static function publicHeadContent(dcCore $core, context $_ctx)
	{
		# Limit update to home page
		if ($core->url->type != 'default') {

			return null;
		}

		$blog_url = html::escapeJS(
			$core->blog->url.
			$core->url->getBase('zoneclearFeedsPage').
			'/zcfsupd'
		);
		$blog_id = html::escapeJS($core->blog->id);

		echo
		"\n<!-- JS for zoneclearFeedServer --> \n".
		"<script type=\"text/javascript\" src=\"".
			$core->blog->url.
			$core->url->getBase('zoneclearFeedsPage').
			'/zcfsupd.js">'.
		"</script> \n".
		"<script type=\"text/javascript\"> \n".
		"//<![CDATA[\n".
		" \$(function(){if(!document.getElementById){return;} ".
		" $('body').zoneclearFeedServer({blog_url:'".
			$blog_url."',blog_id:'".$blog_id."'}); ".
		" })\n".
		"//]]>\n".
		"</script>\n";
	}
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - rs methods.
 * @since 2.6
 */
class zcfsRsExtPosts extends rsExtPost
{
	/**
	 * Get feed meta.
	 * 
	 * @param  record $rs   record instance
	 * @param  string $info Feed info key
	 * @return string       Feed info value
	 */
	public static function zcFeed($rs, $info)
	{
		$p = array(
			'post_id'		=> $rs->post_id,
			'meta_type'	=> 'zoneclearfeed_'.$info,
			'limit'		=> 1
		);
		$meta = $rs->core->meta->getMetadata($p);

		return $meta->isEmpty() ? null : $meta->meta_id;
	}

	/**
	 * Call other rs extension.
	 * 
	 * @param  string $type Type of extension
	 * @param  array  $args Arguments
	 * @return mixed        record extension ressource
	 */
	public static function zcFeedBrother($type, $args)
	{
		if (!empty($GLOBALS['beforeZcFeedRsExt'][$type])) {
			$func = $GLOBALS['beforeZcFeedRsExt'][$type];
		}
		elseif (is_callable('rsExtPostPublic', $type)) {
			$func = array('rsExtPostPublic', $type);
		}
		else {
			$func = array('rsExtPost', $type);
		}

		return call_user_func_array($func, $args);
	}

	/**
	 * Get author link from post to feed.
	 * 
	 * @param  record $rs record instance
	 * @return string     Author link
	 */
	public static function getAuthorLink($rs)
	{
		$author = $rs->zcFeed('author');
		$site = $rs->zcFeed('site');
		$sitename = $rs->zcFeed('sitename');

		return ($author && $sitename) ?
			$author.' (<a href="'.$site.'">'.$sitename.'</a>)' :
			self::zcFeedBrother('getAuthorLink', array(&$rs));
	}

	/**
	 * Get author CN from post to feed.
	 * 
	 * @param  record $rs record instance
	 * @return string     Author CN
	 */
	public static function getAuthorCN($rs)
	{
		$author = $rs->zcFeed('author');
		return $author ? 
			$author : 
			self::zcFeedBrother('getAuthorCN', array(&$rs));
	}

	/**
	 * Get post link from post to feed.
	 * 
	 * @param  record $rs record instance
	 * @return string     Post link
	 */
	public static function getURL($rs)
	{
		$url = $rs->zcFeed('url');
		$types = @unserialize($rs->core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_post_title_redir);
		$full = is_array($types) && in_array($rs->core->url->type, $types);

		return $url && $full ? 
			zoneclearFeedServer::absoluteURL($rs->zcFeed('site'), $url) : 
			self::zcFeedBrother('getURL', array(&$rs));
	}

	/**
	 * Get post content from post to feed.
	 * 
	 * @param  record $rs record instance
	 * @return string     Post content
	 */
	public static function getContent($rs, $absolute_urls=false)
	{
		$url = $rs->zcFeed('url');
		$sitename = $rs->zcFeed('sitename');
		$content = self::zcFeedBrother('getContent', array(&$rs,$absolute_urls));

		if ($url && $sitename && $rs->post_type == 'post') {
			$types = @unserialize($rs->core->blog->settings->zoneclearFeedServer->zoneclearFeedServer_post_full_tpl);

			if (is_array($types) && in_array($rs->core->url->type, $types)) {

				return $content .
				'<p class="zoneclear-original"><em>'.
				sprintf(__('Original post on <a href="%s">%s</a>'), $url, $sitename).
				'</em></p>';
			}
			else {
				$content = context::remove_html($content);
				$content = context::cut_string($content,350);	
				$content = html::escapeHTML($content);

				return
				'<p>'.$content.'... '.
				'<em><a href="'.self::getURL($rs).'" title="'.
				__('Read more details about this feed').
				'">'.__('Continue reading').'</a></em></p>';
			}
		}
		else {

			return $content;
		}
	}
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - url handler methods.
 * @since 2.6
 */
class zcfsUrlHandler extends dcUrlHandlers
{
	/**
	 * Feeds source page and update methods.
	 * 
	 * @param  array $args Page arguments
	 * @return mixed
	 */
	public static function zcFeedsPage($args)
	{
		global $core, $_ctx;
		$s = $core->blog->settings->zoneclearFeedServer;

		# Not active
		if (!$s->zoneclearFeedServer_active) {
			self::p404();

			return null;
		}

		# Update feeds (from ajax or other post resquest)
		if ($args == '/zcfsupd' 
		 && 3 == $s->zoneclearFeedServer_bhv_pub_upd
		) {
			$msg = '';
			if (!empty($_POST['blogId']) 
			 && html::escapeJS($core->blog->id) == $_POST['blogId']
			) {
				try {
					$zc = new zoneclearFeedServer($core);
					if ($zc->checkFeedsUpdate()) {
						$msg = '<status>ok</status><message>'.
						'Feeds updated successfully</message>';
					}
				}
				catch (Exception $e) {}
			}
			if (empty($msg)) {
				$msg = '<status>failed</status><message>'.
				'Failed to update feeds</message>';
			}

			header('Content-Type: application/xml; charset=UTF-8');
			echo  
			'<?xml version="1.0" encoding="utf-8"?>'."\n".
			'<response><rsp>'."\n".
			$msg."\n".
			'</rsp></response>';

			exit(1);
		}
		# Server js
		elseif ($args == '/zcfsupd.js' 
		 && 3 == $s->zoneclearFeedServer_bhv_pub_upd
		) {
			$core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates');
			self::serveDocument(
				'zcfsupd.js',
				'text/javascript',
				false,
				false
			);
		}
		# Server feeds description page
		elseif (in_array($args, array('', '/')) 
		 && $s->zoneclearFeedServer_pub_active
		) {
			$tplset = $core->themes->moduleInfo($core->blog->settings->system->theme,'tplset');
        if (!empty($tplset) && is_dir(dirname(__FILE__).'/default-templates/'.$tplset)) {
            $core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates/'.$tplset);
        } else {
            $core->tpl->setPath($core->tpl->getPath(), dirname(__FILE__).'/default-templates/'.DC_DEFAULT_TPLSET);
        }
			self::serveDocument('zcfeeds.html');
		}
		# Unknow
		else {
			self::p404();
		}

		return null;
	}
}

/**
 * @ingroup DC_PLUGIN_ZONECLEARFEEDSERVER
 * @brief Mix your blog with a feeds planet - template methods.
 * @since 2.6
 */
class zcfsTemplate
{
	public static function Feeds($a, $c)
	{
		$lastn = -1;
		$p = '';
		if (isset($a['lastn'])) {
			$lastn = abs((integer) $a['lastn'])+0;
			$p .= "\$zcfs_params['limit'] = ".$lastn.";\n";
		}
		if (isset($a['cat_id'])) {
			$p .= "@\$zcfs_params['sql'] .= 'AND Z.cat_id = ".addslashes($a['cat_id'])." ';\n";
		}
		if (isset($a['no_category'])) {
			$p .= "@\$zcfs_params['sql'] .= 'AND Z.cat_id IS NULL ';\n";
		}
		if (!empty($a['site_url'])) {
			$p .= "\$zcfs_params['feed_url'] = '".addslashes($a['site_url'])."';\n";
		}
		if (isset($a['feed_status'])) {
			$p .= "\$zcfs_params['feed_status'] = ".((integer) $a['feed_status']).";\n";
		}
		else {
			$p .= "\$zcfs_params['feed_status'] = 1;\n";
		}
		if (!empty($a['feed_url'])) {
			$p .= "\$zcfs_params['feed_feed'] = '".addslashes($a['feed_url'])."';\n";
		}
		if (isset($a['feed_owner'])) {
			$p .= "@\$zcfs_params['sql'] .= \"AND Z.feed_owner = '".addslashes($a['author'])."' \";\n";
		}

		$sortby = 'feed_creadt';
		$order = 'desc';
		if (isset($a['sortby'])) {
			switch ($a['sortby']) {
				case 'name':		$sortby = 'lowername';	break;
				case 'owner' :		$sortby = 'feed_owner';	break;
				case 'date' :		$sortby = 'feed_dt';	break;
				case 'update' :	$sortby = 'feed_upddt';	break;
				case 'id' :		$sortby = 'feed_id';	break;
			}
		}
		if (isset($a['order']) 
		 && preg_match('/^(desc|asc)$/i', $a['order'])
		) {
			$order = $a['order'];
		}
		$p .= "\$zcfs_params['order'] = '".$sortby." ".$order."';\n";

		return  
		'<?php '.$p.
		'$_ctx->feeds_params = $zcfs_params;'."\n".
		'$zcfs = new zoneclearFeedServer($core);'."\n".
		'$_ctx->feeds = $zcfs->getFeeds($zcfs_params); unset($zcfs_params,$zcfs);'."\n".
		"?>\n".
		'<?php while ($_ctx->feeds->fetch()) : ?>'.$c.'<?php endwhile; '.
		'$_ctx->feeds = null; $_ctx->feeds_params = null; ?>';
	}

	public static function FeedIf($a,$c)
	{
		$if = array();

		$operator = isset($a['operator']) ? self::getOperator($a['operator']) : '&&';

		if (isset($a['type'])) {
			$type = trim($a['type']);
			$type = !empty($type) ? $type : 'feed';
			$if[] = '$_ctx->feeds->feed_type == "'.addslashes($type).'"';
		}
		if (isset($a['site_url'])) {
			$url = trim($a['feed_url']);
			if (substr($url, 0, 1) == '!') {
				$url = substr($url, 1);
				$if[] = '$_ctx->feeds->feed_url != "'.addslashes($url).'"';
			}
			else {
				$if[] = '$_ctx->feeds->feed_url == "'.addslashes($url).'"';
			}
		}
		if (isset($a['feed_url'])) {
			$url = trim($a['feed_feed']);
			if (substr($url, 0, 1) == '!') {
				$url = substr($url, 1);
				$if[] = '$_ctx->feeds->feed_feed != "'.addslashes($url).'"';
			}
			else {
				$if[] = '$_ctx->feeds->feed_feed == "'.addslashes($url).'"';
			}
		}
		if (isset($a['category'])) {
			$category = addslashes(trim($a['category']));
			if (substr($category, 0, 1) == '!') {
				$category = substr($category, 1);
				$if[] = '($_ctx->feeds->cat_url != "'.$category.'")';
			}
			else {
				$if[] = '($_ctx->feeds->cat_url == "'.$category.'")';
			}
		}
		if (isset($a['first'])) {
			$sign = (boolean) $a['first'] ? '=' : '!';
			$if[] = '$_ctx->feeds->index() '.$sign.'= 0';
		}
		if (isset($a['odd'])) {
			$sign = (boolean) $a['odd'] ? '=' : '!';
			$if[] = '($_ctx->feeds->index()+1)%2 '.$sign.'= 1';
		}
		if (isset($a['has_category'])) {
			$sign = (boolean) $a['has_category'] ? '' : '!';
			$if[] = $sign.'$_ctx->feeds->cat_id';
		}
		if (isset($a['has_description'])) {
			$sign = (boolean) $a['has_description'] ? '' : '!';
			$if[] = $sign.'$_ctx->feeds->feed_desc';
		}

		return empty($if) ?
			$c : 
			'<?php if('.implode(' '.$operator.' ',$if).') : ?>'.$c.'<?php endif; ?>';
	}

	public static function FeedIfFirst($a)
	{
		$ret = isset($a['return']) ? $a['return'] : 'first';
		$ret = html::escapeHTML($ret);

		return
		'<?php if ($_ctx->feeds->index() == 0) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}

	public static function FeedIfOdd($a)
	{
		$ret = isset($a['return']) ? $a['return'] : 'odd';
		$ret = html::escapeHTML($ret);

		return
		'<?php if (($_ctx->feeds->index()+1)%2 == 1) { '.
		"echo '".addslashes($ret)."'; } ?>";
	}

	public static function FeedDesc($a)
	{
		return self::getValue($a, '$_ctx->feeds->feed_desc');
	}

	public static function FeedOwner($a)
	{
		return self::getValue($a, '$_ctx->feeds->feed_owner');
	}

	public static function FeedCategory($a)
	{
		return self::getValue($a, '$_ctx->feeds->cat_title');
	}

	public static function FeedCategoryID($a)
	{
		return self::getValue($a, '$_ctx->feeds->cat_id');
	}

	public static function FeedCategoryURL($a)
	{
		return self::getValue($a, '$core->blog->url.$core->url->getBase(\'category\').\'/\'.html::sanitizeURL($_ctx->feeds->cat_url)');
	}

	public static function FeedCategoryShortURL($a)
	{
		return self::getValue($a, '$_ctx->feeds->cat_url');
	}

	public static function FeedID($a)
	{
		return self::getValue($a, '$_ctx->feeds->feed_id');
	}

	public static function FeedLang($a)
	{
		$f = $GLOBALS['core']->tpl->getFilters($a);

		return empty($a['full']) ?
			'<?php echo '.sprintf($f,'$_ctx->feeds->feed_lang').'; ?>'
			:
			'<?php $langs = l10n::getISOcodes(); if (isset($langs[$_ctx->feeds->feed_lang])) { echo '.sprintf($f, '$langs[$_ctx->feeds->feed_lang]').'; } else { echo '.sprintf($f, '$_ctx->feeds->feed_lang').'; } unset($langs); ?>';
	}

	public static function FeedName($a)
	{
		return self::getValue($a, '$_ctx->feeds->feed_name');
	}

	public static function FeedSiteURL($a)
	{
		return self::getValue($a, '$_ctx->feeds->feed_url');
	}

	public static function FeedFeedURL($a)
	{
		return self::getValue($a, '$_ctx->feeds->feed_feed');
	}

	public static function FeedsHeader($a, $c)
	{
		return "<?php if (\$_ctx->feeds->isStart()) : ?>".$c."<?php endif; ?>";
	}

	public static function FeedsFooter($a, $c)
	{
		return "<?php if (\$_ctx->feeds->isEnd()) : ?>".$c."<?php endif; ?>";
	}

	public static function FeedsCount($a)
	{
		$none = 'no sources';
		$one = 'one source';
		$more = '%d sources';

		if (isset($a['none'])) {
			$none = addslashes($a['none']);
		}
		if (isset($a['one'])) {
			$one = addslashes($a['one']);
		}
		if (isset($a['more'])) {
			$more = addslashes($a['more']);
		}

		return
		"<?php \$fcount = \$_ctx->feeds->count(); \n".
		"if (\$fcount == 0) {\n".
		"  printf(__('".$none."'),\$fcount);\n".
		"} elseif (\$fcount == 1) {\n".
		"  printf(__('".$one."'),\$fcount);\n".
		"} else {\n".
		"  printf(__('".$more."'),\$fcount);\n".
		"} unset(\$fcount); ?>";
	}

	public static function FeedsEntriesCount($a)
	{
		$none = __('no entries');
		$one = __('one entry');
		$more = __('%d entries');

		if (isset($a['none'])) {
			$none = addslashes($a['none']);
		}
		if (isset($a['one'])) {
			$one = addslashes($a['one']);
		}
		if (isset($a['more'])) {
			$more = addslashes($a['more']);
		}

		return
		"<?php \$fcount = 0; \$allfeeds = \$_ctx->feeds->zc->getFeeds(); \n".
		"if (!\$allfeeds->isEmpty()) { \n".
		" while (\$allfeeds->fetch()) { ".
		"  \$fcount += (integer) \$_ctx->feeds->zc->getPostsByFeed(array('feed_id'=>\$allfeeds->feed_id),true)->f(0); ".
		" } \n".
		"} \n".
		"if (\$fcount == 0) {\n".
		"  printf(__('".$none."'),\$fcount);\n".
		"} elseif (\$fcount == 1) {\n".
		"  printf(__('".$one."'),\$fcount);\n".
		"} else {\n".
		"  printf(__('".$more."'),\$fcount);\n".
		"} unset(\$allfeeds,\$fcount); ?>";
	}

	public static function FeedEntriesCount($a)
	{
		$none = 'no entries';
		$one = 'one entry';
		$more = '%d entries';

		if (isset($a['none'])) {
			$none = addslashes($a['none']);
		}
		if (isset($a['one'])) {
			$one = addslashes($a['one']);
		}
		if (isset($a['more'])) {
			$more = addslashes($a['more']);
		}

		return
		"<?php \$fcount = \$_ctx->feeds->zc->getPostsByFeed(array('feed_id'=>\$_ctx->feeds->feed_id),true)->f(0); \n".
		"if (\$fcount == 0) {\n".
		"  printf(__('".$none."'),\$fcount);\n".
		"} elseif (\$fcount == 1) {\n".
		"  printf(__('".$one."'),\$fcount);\n".
		"} else {\n".
		"  printf(__('".$more."'),\$fcount);\n".
		"} unset(\$fcount); ?>";
	}

	protected static function getValue($a,$v)
	{
		return '<?php echo '.sprintf($GLOBALS['core']->tpl->getFilters($a), $v).'; ?>';
	}

	protected static function getOperator($op)
	{
		switch (strtolower($op))
		{
			case 'or':
			case '||':
				return '||';
			case 'and':
			case '&&':
			default:
				return '&&';
		}
	}
}

$core->addBehavior('publicBreadcrumb',array('extZcfeeds','publicBreadcrumb'));

class extZcfeeds
{
    public static function publicBreadcrumb($context,$separator)
    {
        if ($context == 'zoneclearFeedsPage') {
            return __('List of feeds');
        }
    }
}