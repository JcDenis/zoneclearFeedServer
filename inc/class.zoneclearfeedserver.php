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
 * @brief Feeds server - main methods
 * @since 2.6
 */
class zoneclearFeedServer
{
    public static $nethttp_timeout     = 5;
    public static $nethttp_agent       = 'zoneclearFeedServer - http://zoneclear.org';
    public static $nethttp_maxredirect = 2;

    public $con;
    private $blog;
    private $table;
    private $lock = null;
    private $user = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->con   = dcCore::app()->con;
        $this->blog  = dcCore::app()->con->escape(dcCore::app()->blog->id);
        $this->table = dcCore::app()->prefix . initZoneclearFeedServer::TABLE_NAME;
    }

    /**
     * Short openCursor.
     *
     * @return cursor cursor instance
     */
    public function openCursor()
    {
        return $this->con->openCursor($this->table);
    }

    /**
     * Update feed record.
     *
     * @param  integer $id  Feed id
     * @param  cursor  $cur cursor instance
     */
    public function updFeed($id, cursor $cur)
    {
        $this->con->writeLock($this->table);

        try {
            $id = (int) $id;

            if ($id < 1) {
                throw new Exception(__('No such ID'));
            }

            $cur->feed_upddt = date('Y-m-d H:i:s');

            $cur->update(sprintf(
                "WHERE feed_id = %s AND blog_id = '%s' ",
                $id,
                $this->blog
            ));
            $this->con->unlock();
            $this->trigger();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- zoneclearFeedServerAfterUpdFeed
        dcCore::app()->callBehavior('zoneclearFeedServerAfterUpdFeed', $cur, $id);
    }

    /**
     * Add feed record.
     *
     * @param cursor $cur cursor instance
     */
    public function addFeed(cursor $cur)
    {
        $this->con->writeLock($this->table);

        try {
            $cur->feed_id     = $this->getNextId();
            $cur->blog_id     = $this->blog;
            $cur->feed_creadt = date('Y-m-d H:i:s');
            $cur->feed_upddt  = date('Y-m-d H:i:s');

            //add getFeedCursor here

            $cur->insert();
            $this->con->unlock();
            $this->trigger();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- zoneclearFeedServerAfterAddFeed
        dcCore::app()->callBehavior('zoneclearFeedServerAfterAddFeed', $cur);

        return $cur->feed_id;
    }

    /**
     * Quick enable / disable feed.
     *
     * @param  integer $id     Feed Id
     * @param  boolean $enable Enable or disable feed
     * @param  integer  $time  Force update time
     */
    public function enableFeed($id, $enable = true, $time = null)
    {
        try {
            $id = (int) $id;

            if ($id < 1) {
                throw new Exception(__('No such ID'));
            }

            $cur = $this->openCursor();
            $this->con->writeLock($this->table);

            $cur->feed_upddt  = date('Y-m-d H:i:s');
            $cur->feed_status = (int) $enable;
            if (null !== $time) {
                $cur->feed_upd_last = (int) $time;
            }

            $cur->update(sprintf(
                "WHERE feed_id = %s AND blog_id = '%s' ",
                $id,
                $this->blog
            ));
            $this->con->unlock();
            $this->trigger();
        } catch (Exception $e) {
            $this->con->unlock();

            throw $e;
        }

        # --BEHAVIOR-- zoneclearFeedServerAfterEnableFeed
        dcCore::app()->callBehavior('zoneclearFeedServerAfterEnableFeed', $id, $enable, $time);
    }

    #
    /**
     * Delete record (this not deletes post).
     *
     * @param  integer $id Feed Id
     */
    public function delFeed($id)
    {
        $id = (int) $id;

        if ($id < 1) {
            throw new Exception(__('No such ID'));
        }

        # --BEHAVIOR-- zoneclearFeedServerBeforeDelFeed
        dcCore::app()->callBehavior('zoneclearFeedServerBeforeDelFeed', $id);

        $this->con->execute(sprintf(
            "DELETE FROM %s WHERE feed_id = %s AND blog_id = '%s' ",
            $this->table,
            $id,
            $this->blog
        ));
        $this->trigger();
    }

    /**
     * Get related posts.
     *
     * @param  array   $params     Query params
     * @param  boolean $count_only Return only result count
     * @return null|dcRecord              record instance
     */
    public function getPostsByFeed($params = [], $count_only = false)
    {
        if (!isset($params['feed_id'])) {
            return null;
        }

        $sql = new dcSelectStatement();
        $sql->join(
            (new dcJoinStatement())
                   ->type('LEFT')
                   ->from(dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' F')
                   ->on('P.post_id = F.post_id')
                   ->statement()
        );

        $params['sql'] = "AND P.blog_id = '" . $this->blog . "' " .
        "AND F.meta_type = 'zoneclearfeed_id' " .
        "AND F.meta_id = '" . $this->con->escape($params['feed_id']) . "' ";

        unset($params['feed_id']);

        return dcCore::app()->blog->getPosts($params, $count_only, $sql);
    }

    /**
     * Get feed record.
     *
     * @param  array   $params     Query params
     * @param  boolean $count_only Return only result count
     * @return dcRecord              record instance
     */
    public function getFeeds($params = [], $count_only = false)
    {
        if ($count_only) {
            $strReq = 'SELECT count(Z.feed_id) ';
        } else {
            $content_req = '';
            if (!empty($params['columns']) && is_array($params['columns'])) {
                $content_req .= implode(', ', $params['columns']) . ', ';
            }

            $strReq = 'SELECT Z.feed_id, Z.feed_creadt, Z.feed_upddt, Z.feed_type, ' .
            'Z.blog_id, Z.cat_id, ' .
            'Z.feed_upd_int, Z.feed_upd_last, Z.feed_status, ' .
            $content_req .
            'LOWER(Z.feed_name) as lowername, Z.feed_name, Z.feed_desc, ' .
            'Z.feed_url, Z.feed_feed, Z.feed_get_tags, ' .
            'Z.feed_tags, Z.feed_owner, Z.feed_tweeter, Z.feed_lang, ' .
            'Z.feed_nb_out, Z.feed_nb_in, ' .
            'C.cat_title, C.cat_url, C.cat_desc ';
        }

        $strReq .= 'FROM ' . $this->table . ' Z ' .
        'LEFT OUTER JOIN ' . dcCore::app()->prefix . dcCategories::CATEGORY_TABLE_NAME . ' C ON Z.cat_id = C.cat_id ';

        if (!empty($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE Z.blog_id = '" . $this->blog . "' ";

        if (isset($params['feed_type'])) {
            $strReq .= "AND Z.feed_type = '" . $this->con->escape($params['type']) . "' ";
        } else {
            $strReq .= "AND Z.feed_type = 'feed' ";
        }

        if (!empty($params['feed_id'])) {
            if (is_array($params['feed_id'])) {
                array_walk($params['feed_id'], function (&$v, $k) { if ($v !== null) { $v = (int) $v; }});
            } else {
                $params['feed_id'] = [(int) $params['feed_id']];
            }
            $strReq .= 'AND Z.feed_id ' . $this->con->in($params['feed_id']);
        }

        if (isset($params['feed_feed'])) {
            $strReq .= "AND Z.feed_feed = '" . $this->con->escape($params['feed_feed']) . "' ";
        }
        if (isset($params['feed_url'])) {
            $strReq .= "AND Z.feed_url = '" . $this->con->escape($params['feed_url']) . "' ";
        }
        if (isset($params['feed_status'])) {
            $strReq .= 'AND Z.feed_status = ' . ((int) $params['feed_status']) . ' ';
        }

        if (!empty($params['q'])) {
            $q = $this->con->escape(str_replace('*', '%', strtolower($params['q'])));
            $strReq .= "AND LOWER(Z.feed_name) LIKE '" . $q . "' ";
        }

        if (!empty($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . $this->con->escape($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY Z.feed_upddt DESC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $strReq .= $this->con->limit($params['limit']);
        }

        $rs = $this->con->select($strReq);

        return $rs;
    }

    /**
     * Get next table id.
     *
     * @return integer  Next id
     */
    private function getNextId()
    {
        return $this->con->select(
            'SELECT MAX(feed_id) FROM ' . $this->table
        )->f(0) + 1;
    }

    /**
     * Lock a file to see if an update is ongoing.
     *
     * @return boolean True if file is locked
     */
    public function lockUpdate()
    {
        try {
            # Need flock function
            if (!function_exists('flock')) {
                throw new Exception("Can't call php function named flock");
            }
            # Cache writable ?
            if (!is_writable(DC_TPL_CACHE)) {
                throw new Exception("Can't write in cache fodler");
            }
            # Set file path
            $f_md5       = md5($this->blog);
            $cached_file = sprintf(
                '%s/%s/%s/%s/%s.txt',
                DC_TPL_CACHE,
                'periodical',
                substr($f_md5, 0, 2),
                substr($f_md5, 2, 2),
                $f_md5
            );
            # Real path
            $cached_file = path::real($cached_file, false);
            # Make dir
            if (!is_dir(dirname($cached_file))) {
                files::makeDir(dirname($cached_file), true);
            }
            # Make file
            if (!file_exists($cached_file)) {
                !$fp = @fopen($cached_file, 'w');
                if ($fp === false) {
                    throw new Exception("Can't create file");
                }
                fwrite($fp, '1', strlen('1'));
                fclose($fp);
            }
            # Open file
            if (!($fp = @fopen($cached_file, 'r+'))) {
                throw new Exception("Can't open file");
            }
            # Lock file
            if (!flock($fp, LOCK_EX)) {
                throw new Exception("Can't lock file");
            }
            $this->lock = $fp;

            return true;
        } catch (Exception $e) {
            throw $e;
        }

        return false;
    }

    /**
     * Unlock file of update process.
     */
    public function unlockUpdate()
    {
        @fclose($this->lock);
        $this->lock = null;
    }

    /**
     * Check and add/update post related to record if needed.
     *
     * @param  integer  $id   Feed Id
     * @param  boolean $throw Throw exception or end silently
     * @return boolean        True if process succeed
     */
    public function checkFeedsUpdate($id = null, $throw = false)
    {
        $s = dcCore::app()->blog->settings->__get(basename(dirname('../' . __DIR__)));

        # Not configured
        if (!$s->zoneclearFeedServer_active || !$s->zoneclearFeedServer_user) {
            return false;
        }

        # Limit to one update at a time
        try {
            $this->lockUpdate();
        } catch (Exception $e) {
            if ($throw) {
                throw $e;
            }

            return false;
        }

        dt::setTZ(dcCore::app()->blog->settings->system->blog_timezone);
        $time = time();

        # All feeds or only one (from admin)
        $f = !$id ?
            $this->getFeeds(['feed_status' => 1, 'order' => 'feed_upd_last ASC']) :
            $this->getFeeds(['feed_id' => $id]);

        # No feed
        if ($f->isEmpty()) {
            return false;
        }

        # Set feeds user
        $this->enableUser(true);

        $updates  = false;
        $loop_mem = [];

        $limit = abs((int) $s->zoneclearFeedServer_update_limit);
        if ($limit < 1) {
            $limit = 10;
        }
        $i = 0;

        $cur_post = $this->con->openCursor(dcCore::app()->prefix . dcBlog::POST_TABLE_NAME);
        $cur_meta = $this->con->openCursor(dcCore::app()->prefix . dcMeta::META_TABLE_NAME);

        while ($f->fetch()) {
            # Check if feed need update
            if ($id || $i < $limit && $f->feed_status == 1
                                   && $time > $f->feed_upd_last + $f->feed_upd_int
            ) {
                $i++;
                $feed = self::readFeed($f->feed_feed);

                # Nothing to parse
                if (!$feed) {
                    # Keep active empty feed or disable it ?
                    if (!$s->zoneclearFeedServer_keep_empty_feed) {
                        $this->enableFeed($f->feed_id, false);
                    } else {
                        # Set update time of this feed
                        $this->enableFeed($f->feed_id, true, $time);
                    }
                    $i++;

                # Not updated since last visit
                } elseif (!$id
                    && '' != $feed->pubdate
                    && strtotime($feed->pubdate) < $f->feed_upd_last
                ) {
                    # Set update time of this feed
                    $this->enableFeed($f->feed_id, true, $time);
                    $i++;
                } else {
                    # Set update time of this feed
                    $this->enableFeed($f->feed_id, $f->feed_status, $time);

                    $this->con->begin();

                    foreach ($feed->items as $item) {
                        $item_TS = $item->TS ? $item->TS : $time;

                        // I found that mercurial atom feed did not repect standard
                        $item_link = @$item->link;
                        if (!$item_link) {
                            $item_link = @$item->guid;
                        }
                        # Unknow feed item link
                        if (!$item_link) {
                            continue;
                        }

                        $item_link              = $this->con->escape($item_link);
                        $is_new_published_entry = false;

                        # Not updated since last visit
                        if (!$id && $item_TS < $f->feed_upd_last) {
                            continue;
                        }

                        # Fix loop twin
                        if (in_array($item_link, $loop_mem)) {
                            continue;
                        }
                        $loop_mem[] = $item_link;

                        # Check if entry exists
                        $old_post = $this->con->select(
                            'SELECT P.post_id, P.post_status ' .
                            'FROM ' . dcCore::app()->prefix . dcBlog::POST_TABLE_NAME . ' P ' .
                            'INNER JOIN ' . dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' M ' .
                            'ON P.post_id = M.post_id ' .
                            "WHERE blog_id='" . $this->blog . "' " .
                            "AND meta_type = 'zoneclearfeed_url' " .
                            "AND meta_id = '" . $item_link . "' "
                        );

                        # Prepare entry cursor
                        $cur_post->clean();
                        $cur_post->post_dt = date('Y-m-d H:i:s', $item_TS);
                        if ($f->cat_id) {
                            $cur_post->cat_id = $f->cat_id;
                        }
                        $post_content           = $item->content ? $item->content : $item->description;
                        $cur_post->post_format  = 'xhtml';
                        $cur_post->post_content = html::absoluteURLs($post_content, $feed->link);
                        $cur_post->post_title   = $item->title ? $item->title : text::cutString(html::clean($cur_post->post_content), 60);
                        $creator                = $item->creator ? $item->creator : $f->feed_owner;

                        try {
                            # Create entry
                            if ($old_post->isEmpty()) {
                                # Post
                                $cur_post->user_id           = dcCore::app()->auth->userID();
                                $cur_post->post_format       = 'xhtml';
                                $cur_post->post_status       = (int) $s->zoneclearFeedServer_post_status_new;
                                $cur_post->post_open_comment = 0;
                                $cur_post->post_open_tb      = 0;

                                # --BEHAVIOR-- zoneclearFeedServerBeforePostCreate
                                dcCore::app()->callBehavior(
                                    'zoneclearFeedServerBeforePostCreate',
                                    $cur_post
                                );

                                $post_id = dcCore::app()->auth->sudo(
                                    [dcCore::app()->blog, 'addPost'],
                                    $cur_post
                                );

                                # --BEHAVIOR-- zoneclearFeedServerAfterPostCreate
                                dcCore::app()->callBehavior(
                                    'zoneclearFeedServerAfterPostCreate',
                                    $cur_post,
                                    $post_id
                                );

                                # Auto tweet new post
                                if ($cur_post->post_status == 1) {
                                    $is_new_published_entry = true;
                                }

                            # Update entry
                            } else {
                                $post_id = $old_post->post_id;

                                # --BEHAVIOR-- zoneclearFeedServerBeforePostUpdate
                                dcCore::app()->callBehavior(
                                    'zoneclearFeedServerBeforePostUpdate',
                                    $cur_post,
                                    $post_id
                                );

                                dcCore::app()->auth->sudo(
                                    [dcCore::app()->blog, 'updPost'],
                                    $post_id,
                                    $cur_post
                                );

                                # Quick delete old meta
                                $this->con->execute(
                                    'DELETE FROM ' . dcCore::app()->prefix . dcMeta::META_TABLE_NAME . ' ' .
                                    'WHERE post_id = ' . $post_id . ' ' .
                                    "AND meta_type LIKE 'zoneclearfeed_%' "
                                );

                                # Delete old tags
                                dcCore::app()->auth->sudo(
                                    [dcCore::app()->meta, 'delPostMeta'],
                                    $post_id,
                                    'tag'
                                );

                                # --BEHAVIOR-- zoneclearFeedServerAfterPostUpdate
                                dcCore::app()->callBehavior(
                                    'zoneclearFeedServerAfterPostUpdate',
                                    $cur_post,
                                    $post_id
                                );
                            }

                            # Quick add new meta
                            $meta          = new ArrayObject();
                            $meta->tweeter = $f->feed_tweeter;

                            $cur_meta->clean();
                            $cur_meta->post_id   = $post_id;
                            $cur_meta->meta_type = 'zoneclearfeed_url';
                            $cur_meta->meta_id   = $meta->url = $item_link;
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->post_id   = $post_id;
                            $cur_meta->meta_type = 'zoneclearfeed_author';
                            $cur_meta->meta_id   = $meta->author = $creator;
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->post_id   = $post_id;
                            $cur_meta->meta_type = 'zoneclearfeed_site';
                            $cur_meta->meta_id   = $meta->site = $f->feed_url;
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->post_id   = $post_id;
                            $cur_meta->meta_type = 'zoneclearfeed_sitename';
                            $cur_meta->meta_id   = $meta->sitename = $f->feed_name;
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->post_id   = $post_id;
                            $cur_meta->meta_type = 'zoneclearfeed_id';
                            $cur_meta->meta_id   = $meta->id = $f->feed_id;
                            $cur_meta->insert();

                            # Add new tags
                            $tags = dcCore::app()->meta->splitMetaValues($f->feed_tags);
                            if ($f->feed_get_tags) {
                                # Some feed subjects contains more than one tag
                                foreach ($item->subject as $subjects) {
                                    $tmp  = dcCore::app()->meta->splitMetaValues($subjects);
                                    $tags = array_merge($tags, $tmp);
                                }
                                $tags = array_unique($tags);
                            }
                            $formated_tags = [];
                            foreach ($tags as $tag) {
                                # Change tags case
                                switch ((int) $s->zoneclearFeedServer_tag_case) {
                                    case 3: $tag = strtoupper($tag);

                                        break;
                                    case 2: $tag = strtolower($tag);

                                        break;
                                    case 1: $tag = ucfirst(strtolower($tag));

                                        break;
                                    default: /* do nothing */                 break;
                                }
                                if (!in_array($tag, $formated_tags)) {
                                    $formated_tags[] = $tag;
                                    dcCore::app()->auth->sudo(
                                        [dcCore::app()->meta, 'delPostMeta'],
                                        $post_id,
                                        'tag',
                                        dcMeta::sanitizeMetaID($tag)
                                    );
                                    dcCore::app()->auth->sudo(
                                        [dcCore::app()->meta, 'setPostMeta'],
                                        $post_id,
                                        'tag',
                                        dcMeta::sanitizeMetaID($tag)
                                    );
                                }
                            }
                            $meta->tags = $formated_tags;

                            # --BEHAVIOR-- zoneclearFeedServerAfterFeedUpdate
                            dcCore::app()->callBehavior(
                                'zoneclearFeedServerAfterFeedUpdate',
                                $is_new_published_entry,
                                $cur_post,
                                $meta
                            );
                        } catch (Exception $e) {
                            $this->con->rollback();
                            $this->enableUser(false);
                            $this->unlockUpdate();

                            throw $e;
                        }
                        $updates = true;
                    }
                    $this->con->commit();
                }
            }
        }
        $this->enableUser(false);
        $this->unlockUpdate();

        return true;
    }

    /**
     * Set permission to update post table.
     *
     * @param  boolean $enable Enable or disable perm
     */
    public function enableUser($enable = false)
    {
        # Enable
        if ($enable) {
            // backup current user
            $this->user = dcCore::app()->auth->userID();
            // set zcfs posts user
            if (!dcCore::app()->auth->checkUser((string) dcCore::app()->blog->settings->__get(basename(dirname('../' . __DIR__)))->zoneclearFeedServer_user)) {
                throw new Exception('Unable to set user');
            }
        # Disable
        } else {
            dcCore::app()->auth = null;
            dcCore::app()->auth = new dcAuth();
            // restore current user
            dcCore::app()->auth->checkUser($this->user ?? '');
        }
    }

    /**
     * Read and parse external feeds.
     *
     * @param  string      $f Feed URL
     * @return feedParser|false    Parsed feed
     */
    public static function readFeed($f)
    {
        try {
            $feed_reader = new feedReader();
            $feed_reader->setCacheDir(DC_TPL_CACHE);
            $feed_reader->setTimeout(self::$nethttp_timeout);
            $feed_reader->setMaxRedirects(self::$nethttp_maxredirect);
            $feed_reader->setUserAgent(self::$nethttp_agent);

            return $feed_reader->parse($f);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Trigger.
     */
    private function trigger()
    {
        dcCore::app()->blog->triggerBlog();
    }

    /**
     * Check if an URL is well formed
     *
     * @param  string $url URL
     * @return Boolean     True if URL is allowed
     */
    public static function validateURL($url)
    {
        return false !== strpos($url, 'http://')
            || false !== strpos($url, 'https://');
    }

    /**
     * Get full URL.
     *
     * Know bugs: anchor is not well parsed.
     *
     * @param  string $root Root URL
     * @param  string $url  An URL
     * @return string       Parse URL
     */
    public static function absoluteURL($root, $url)
    {
        $host = preg_replace(
            '|^([a-z]{3,}://)(.*?)/(.*)$|',
            '$1$2',
            $root
        );

        $parse = parse_url($url);

        if (empty($parse['scheme'])) {
            if (strpos($url, '/') === 0) {
                $url = $host . $url;
            } elseif (strpos($url, '#') === 0) {
                $url = $root . $url;
            } elseif (preg_match('|/$|', $root)) {
                $url = $root . $url;
            } else {
                $url = dirname($root) . '/' . $url;
            }
        }

        return $url;
    }

    /**
     * Get list of feeds status.
     *
     * @return array List of names/values of feeds status
     */
    public static function getAllStatus()
    {
        return [
            __('Disabled') => '0',
            __('Enabled')  => '1',
        ];
    }

    /**
     * Get list of predefined interval.
     *
     * @return array List of Name/time of intervals
     */
    public static function getAllUpdateInterval()
    {
        return [
            __('Every hour')        => 3600,
            __('Every two hours')   => 7200,
            __('Two times per day') => 43200,
            __('Every day')         => 86400,
            __('Every two days')    => 172800,
            __('Every week')        => 604800,
        ];
    }

    /**
     * Get list of (super)admins of current blog.
     *
     * @return array List of UserCNs/UserIds
     */
    public function getAllBlogAdmins()
    {
        $admins = [];

        # Get super admins
        $rs = $this->con->select(
            'SELECT user_id, user_super, user_name, user_firstname, user_displayname ' .
            'FROM ' . $this->con->escapeSystem(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME) . ' ' .
            'WHERE user_super = 1 AND user_status = 1 '
        );

        if (!$rs->isEmpty()) {
            while ($rs->fetch()) {
                $user_cn = dcUtils::getUserCN(
                    $rs->user_id,
                    $rs->user_name,
                    $rs->user_firstname,
                    $rs->user_displayname
                );
                $admins[$user_cn . ' (super admin)'] = $rs->user_id;
            }
        }

        # Get admins
        $rs = $this->con->select(
            'SELECT U.user_id, U.user_super, U.user_name, U.user_firstname, U.user_displayname ' .
            'FROM ' . $this->con->escapeSystem(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME) . ' U ' .
            'LEFT JOIN ' . $this->con->escapeSystem(dcCore::app()->prefix . dcAuth::PERMISSIONS_TABLE_NAME) . ' P ' .
            'ON U.user_id=P.user_id ' .
            'WHERE U.user_status = 1 ' .
            "AND P.blog_id = '" . $this->blog . "' " .
            "AND P.permissions LIKE '%|admin|%' "
        );

        if (!$rs->isEmpty()) {
            while ($rs->fetch()) {
                $user_cn = dcUtils::getUserCN(
                    $rs->user_id,
                    $rs->user_name,
                    $rs->user_firstname,
                    $rs->user_displayname
                );
                $admins[$user_cn . ' (admin)'] = $rs->user_id;
            }
        }

        return $admins;
    }

    /**
     * Get list of urls where entries could be hacked.
     *
     * @return array        List of names/types of URLs
     */
    public static function getPublicUrlTypes()
    {
        $types = [];

        # --BEHAVIOR-- zoneclearFeedServerPublicUrlTypes
        dcCore::app()->callBehavior('zoneclearFeedServerPublicUrlTypes', $types);

        $types[__('Home page')]      = 'default';
        $types[__('Entries pages')]  = 'post';
        $types[__('Tags pages')]     = 'tag';
        $types[__('Archives pages')] = 'archive';
        $types[__('Category pages')] = 'category';
        $types[__('Entries feed')]   = 'feed';

        return $types;
    }

    /**
     * Take care about plugin tweakurls (thanks Mathieu M.).
     *
     * @param  cursor  $cur cursor instance
     * @param  integer $id  Post Id
     */
    public static function tweakurlsAfterPostCreate(cursor $cur, $id)
    {
        if (version_compare(dcCore::app()->plugins->moduleInfo('tweakurls', 'version'), '0.8', '>=')) {
            $cur->post_url = tweakUrls::tweakBlogURL($cur->post_url);
            dcCore::app()->auth->sudo([dcCore::app()->blog, 'updPost'], $id, $cur);
        }
    }
}
