<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Database\{
    Cursor,
    MetaRecord
};
use Dotclear\Database\Statement\{
    DeleteStatement,
    JoinStatement,
    SelectStatement
};
use Dotclear\Helper\Date;
use Dotclear\Helper\File\{
    Files,
    Path
};
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Feed\{
    Parser,
    Reader
};
use Dotclear\Helper\Text;
use Exception;

/**
 * @brief       zoneclearFeedServer main class.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ZoneclearFeedServer
{
    /**
     * Net HTTP timeout.
     *
     * @var     int     NET_HTTP_TIMEOUT
     */
    public const NET_HTTP_TIMEOUT = 5;

    /**
     * Net HTTP feed reader agent.
     *
     * @var     string  NET_HTTP_AGENT
     */
    public const NET_HTTP_AGENT = 'zoneclearFeedServer - http://zoneclear.org';

    /**
     * Net HTTP feed reader max redirect.
     *
     * @var     int NET_HTTP_MAX_REDIRECT
     */
    public const NET_HTTP_MAX_REDIRECT = 2;

    /**
     * Self instance.
     *
     * @var     ZoneclearFeedServer     $instance
     */
    private static $instance;

    /**
     * Settings.
     *
     * @var     Settings    $settings
     */
    public readonly Settings $settings;

    /**
     * File lock for update.
     *
     * @var     null|string     $lock
     */
    private static $lock = null;

    /**
     * Affiliate user ID.
     *
     * @var     null|string     $user
     */
    private $user = null;

    /**
     * Constructor load settings.
     */
    protected function __construct()
    {
        $this->settings = Settings::instance();
    }

    /**
     * Get class instance.
     *
     * @return  ZoneclearFeedServer     Self instacne
     */
    public static function instance(): ZoneclearFeedServer
    {
        if (!(self::$instance instanceof ZoneclearFeedServer)) {
            self::$instance = new ZoneclearFeedServer();
        }

        return self::$instance;
    }

    /**
     * Open database table cursor.
     *
     * @return  Cursor  The cursor
     */
    public function openCursor(): Cursor
    {
        return App::con()->openCursor(App::con()->prefix() . My::TABLE_NAME);
    }

    /**
     * Update feed record.
     *
     * @param   int     $id     The feed ID
     * @param   Cursor  $cur    The cursor instance
     */
    public function updateFeed(int $id, Cursor $cur): void
    {
        App::con()->writeLock(App::con()->prefix() . My::TABLE_NAME);

        try {
            if ($id < 1) {
                throw new Exception(__('No such ID'));
            }

            $this->getFeedCursor($cur);

            $cur->update(sprintf(
                "WHERE feed_id = %s AND blog_id = '%s' ",
                $id,
                App::con()->escapeStr(App::blog()->id())
            ));
            App::con()->unlock();
            $this->trigger();
        } catch (Exception $e) {
            App::con()->unlock();

            throw $e;
        }

        # --BEHAVIOR-- zoneclearFeedServerAfterUpdateFeed -- Cursor, int
        App::behavior()->callBehavior('zoneclearFeedServerAfterUpdateFeed', $cur, $id);
    }

    /**
     * Add feed record.
     *
     * @param   Cursor  $cur    The cursor
     *
     * @return  int     The new feed ID
     */
    public function addFeed(Cursor $cur): int
    {
        App::con()->writeLock(App::con()->prefix() . My::TABLE_NAME);

        try {
            $cur->setField('feed_id', $this->getNextId());
            $cur->setField('blog_id', App::con()->escapeStr(App::blog()->id()));
            $cur->setField('feed_creadt', date('Y-m-d H:i:s'));

            $this->getFeedCursor($cur);

            $cur->insert();
            App::con()->unlock();
            $this->trigger();
        } catch (Exception $e) {
            App::con()->unlock();

            throw $e;
        }

        $id = is_numeric($cur->getField('feed_id')) ? (int) $cur->getField('feed_id') : 0;

        # --BEHAVIOR-- zoneclearFeedServerAfterAddFeed -- Cursor, int
        App::behavior()->callBehavior('zoneclearFeedServerAfterAddFeed', $cur, $id);

        return $id;
    }

    protected function getFeedCursor(Cursor $cur): void
    {
        if ($cur->isField('feed_get_tags')) {
            $cur->setField('feed_get_tags', is_numeric($cur->getField('feed_get_tags')) ? (int) $cur->getField('feed_get_tags') : 0);
        }
        $cur->setField('feed_upddt', date('Y-m-d H:i:s'));
    }

    /**
     * Quick enable / disable feed.
     *
     * @param   int     $id         The feed ID
     * @param   bool    $enable     Enable feed
     * @param   int     $time       Force update time
     */
    public function enableFeed(int $id, bool $enable = true, int $time = 0): void
    {
        try {
            if ($id < 1) {
                throw new Exception(__('No such ID'));
            }

            $cur = $this->openCursor();
            App::con()->writeLock(App::con()->prefix() . My::TABLE_NAME);

            $cur->setField('feed_upddt', date('Y-m-d H:i:s'));
            $cur->setField('feed_status', (int) $enable);
            if (0 < $time) {
                $cur->setField('feed_upd_last', $time);
            }

            $cur->update(sprintf(
                "WHERE feed_id = %s AND blog_id = '%s' ",
                $id,
                App::con()->escapeStr(App::blog()->id())
            ));
            App::con()->unlock();
            $this->trigger();
        } catch (Exception $e) {
            App::con()->unlock();

            throw $e;
        }

        # --BEHAVIOR-- zoneclearFeedServerAfterEnableFeed -- int, bool, int
        App::behavior()->callBehavior('zoneclearFeedServerAfterEnableFeed', $id, $enable, $time);
    }

    #
    /**
     * Delete record (this not deletes post).
     *
     * @param   int     $id     The feed ID
     */
    public function deleteFeed(int $id): void
    {
        if ($id < 1) {
            throw new Exception(__('No such ID'));
        }

        # --BEHAVIOR-- zoneclearFeedServerBeforeDeleteFeed -- int
        App::behavior()->callBehavior('zoneclearFeedServerBeforeDeleteFeed', $id);

        $sql = new DeleteStatement();
        $sql->from(App::con()->prefix() . My::TABLE_NAME)
            ->where('feed_id ' . $sql->in($id))
            ->and('blog_id = ' . $sql->quote(App::blog()->id()))
            ->delete();

        $this->trigger();
    }

    /**
     * Delete all post(s) meta.
     *
     * This deletes all post(s) related meta,
     * the post from the planet become an ordinary post.
     *
     * @param   null|int    $id     The post ID (or null for all!)
     */
    public static function deletePostsMeta(?int $id): void
    {
        $sql = new DeleteStatement();
        $sql->from(App::con()->prefix() . App::meta()::META_TABLE_NAME)
            ->where('meta_type ' . $sql->in([
                My::META_PREFIX . 'url',
                My::META_PREFIX . 'author',
                My::META_PREFIX . 'site',
                My::META_PREFIX . 'sitename',
                My::META_PREFIX . 'id',
            ]));

        if (!is_null($id)) {
            $sql->and('post_id = ' . $id);
        }

        $sql->delete();
    }

    /**
     * Get related posts.
     *
     * @param   array<string, mixed>    $params         The query params
     * @param   bool                    $count_only     Return only result count
     *
     * @return  MetaRecord  The record instance
     */
    public function getPostsByFeed(array $params = [], bool $count_only = false): MetaRecord
    {
        if (!isset($params['feed_id']) || !is_numeric($params['feed_id'])) {
            return MetaRecord::newFromArray([]);
        }

        $sql = new SelectStatement();
        $sql->join(
            (new JoinStatement())
                   ->left()
                   ->from(App::con()->prefix() . App::meta()::META_TABLE_NAME . ' F')
                   ->on('P.post_id = F.post_id')
                   ->statement()
        );

        $params['sql'] = "AND P.blog_id = '" . App::con()->escapeStr(App::blog()->id()) . "' " .
        "AND F.meta_type = '" . My::META_PREFIX . "id' " .
        "AND F.meta_id = '" . App::con()->escapeStr((string) $params['feed_id']) . "' ";

        unset($params['feed_id']);

        return App::blog()->getPosts($params, $count_only, $sql);
    }

    /**
     * Get feed record.
     *
     * @param   array<string, mixed>    $params         The query params
     * @param   bool                    $count_only     Return only result count
     *
     * @return  MetaRecord  The record instance
     */
    public function getFeeds(array $params = [], bool $count_only = false): MetaRecord
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

        $strReq .= 'FROM ' . App::con()->prefix() . My::TABLE_NAME . ' Z ' .
        'LEFT OUTER JOIN ' . App::con()->prefix() . App::categories()::CATEGORY_TABLE_NAME . ' C ON Z.cat_id = C.cat_id ';

        if (!empty($params['from']) && is_string($params['from'])) {
            $strReq .= $params['from'] . ' ';
        }

        $strReq .= "WHERE Z.blog_id = '" . App::con()->escapeStr(App::blog()->id()) . "' ";

        if (isset($params['feed_type']) && is_string($params['feed_type'])) {
            $strReq .= "AND Z.feed_type = '" . App::con()->escapeStr((string) $params['feed_type']) . "' ";
        } else {
            $strReq .= "AND Z.feed_type = 'feed' ";
        }

        if (!empty($params['feed_id'])) {
            if (is_array($params['feed_id'])) {
                array_walk($params['feed_id'], function (&$v, $k) { if ($v !== null) { $v = (int) $v; }});
            } elseif (is_numeric($params['feed_id'])) {
                $params['feed_id'] = [(int) $params['feed_id']];
            }
            $strReq .= 'AND Z.feed_id ' . App::con()->in($params['feed_id']);
        }

        if (isset($params['feed_feed']) && is_string($params['feed_feed'])) {
            $strReq .= "AND Z.feed_feed = '" . App::con()->escapeStr((string) $params['feed_feed']) . "' ";
        }
        if (isset($params['feed_url']) && is_string($params['feed_url'])) {
            $strReq .= "AND Z.feed_url = '" . App::con()->escapeStr((string) $params['feed_url']) . "' ";
        }
        if (isset($params['feed_status'])) {
            $strReq .= 'AND Z.feed_status = ' . ((int) $params['feed_status']) . ' ';
        }

        if (!empty($params['q']) && is_string($params['q'])) {
            $q = App::con()->escapeStr((string) str_replace('*', '%', strtolower($params['q'])));
            $strReq .= "AND LOWER(Z.feed_name) LIKE '" . $q . "' ";
        }

        if (!empty($params['sql']) && is_string($params['sql'])) {
            $strReq .= $params['sql'] . ' ';
        }

        if (!$count_only) {
            if (!empty($params['order']) && is_string($params['order'])) {
                $strReq .= 'ORDER BY ' . App::con()->escapeStr((string) $params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY Z.feed_upddt DESC ';
            }
        }

        if (!$count_only && isset($params['limit'])) {
            if (is_numeric($params['limit'])) {
                $params['limit'] = (int) $params['limit'];
            }
            if (is_int($params['limit']) || is_array($params['limit'])) {
                $strReq .= App::con()->limit($params['limit']);
            }
        }

        return new MetaRecord(App::con()->select($strReq));
    }

    /**
     * Get next table id.
     *
     * @return  int     THe next ID
     */
    private function getNextId(): int
    {
        $sql = new SelectStatement();
        $rs  = $sql
            ->column($sql->max('feed_id'))
            ->from(App::con()->prefix() . My::TABLE_NAME)
            ->select();

        return (int) $rs?->f(0) + 1;
    }

    /**
     * Lock a file to see if an update is ongoing.
     *
     * @return  bool    True if file is locked
     */
    public function lockUpdate(): bool
    {
        try {
            # Cache writable ?
            if (!is_writable(App::config()->cacheRoot())) {
                throw new Exception("Can't write in cache fodler");
            }
            # Set file path
            $f_md5 = md5(App::blog()->id());
            $file  = sprintf(
                '%s/%s/%s/%s/%s.txt',
                App::config()->cacheRoot(),
                My::id(),
                substr($f_md5, 0, 2),
                substr($f_md5, 2, 2),
                $f_md5
            );

            $file = Files::lock($file);
            if (is_null($file) || empty($file)) {
                return false;
            }

            self::$lock = $file;

            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Unlock file of update process.
     */
    public function unlockUpdate(): void
    {
        if (!is_null(self::$lock)) {
            Files::unlock(self::$lock);
            self::$lock = null;
        }
    }

    /**
     * Check and add/update post related to record if needed.
     *
     * @param   int     $id     The feed ID
     * @param   bool    $throw  Throw exception or end silently
     *
     * @return  bool    True on success
     */
    public function checkFeedsUpdate(int $id = 0, bool $throw = false): bool
    {
        $s = $this->settings;

        # Not configured
        if (!$s->active || !$s->user) {
            return false;
        }

        # Limit to one update at a time
        if (!$this->lockUpdate()) {
            return false;
        }

        $tz = App::blog()->settings()->get('system')->get('blog_timezone');
        Date::setTZ(is_string($tz) ? $tz : 'UTC');
        $time = time();

        # All feeds or only one (from admin)
        $f = !$id ?
            $this->getFeeds(['feed_status' => 1, 'order' => 'feed_upd_last ASC']) :
            $this->getFeeds(['feed_id' => $id]);

        # No feed
        if ($f->isEmpty()) {
            return false;
        }

        $enabled  = false;
        $updates  = false;
        $loop_mem = [];

        $i = 0;

        $cur_post = App::blog()->openPostCursor();
        $cur_meta = App::meta()->openMetaCursor();

        while ($f->fetch()) {
            $row = new FeedRow($f);
            # Check if feed need update
            if ($id
             || $i < $s->update_limit && $row->status == 1 && ($time > $row->upd_last + $row->upd_int)
            ) {
                if (!$enabled) {
                    # Set feeds user
                    $this->enableUser(true);
                    $enabled = true;
                }
                $i++;
                $feed = self::readFeed($row->feed);

                # Nothing to parse
                if (!$feed) {
                    # Keep active empty feed or disable it ?
                    if (!$s->keep_empty_feed) {
                        $this->enableFeed($row->id, false);
                    } else {
                        # Set update time of this feed
                        $this->enableFeed($row->id, true, $time);
                    }
                    $i++;

                    # Not updated since last visit
                } elseif (!$id
                    && '' != $feed->pubdate
                    && strtotime($feed->pubdate) < $row->upd_last
                ) {
                    # Set update time of this feed
                    $this->enableFeed($row->id, true, $time);
                    $i++;
                } else {
                    # Set update time of this feed
                    $this->enableFeed($row->id, (bool) $row->status, $time);

                    App::con()->begin();

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

                        $item_link              = App::con()->escapeStr((string) $item_link);
                        $is_new_published_entry = false;

                        # Not updated since last visit
                        if (!$id && $item_TS < $row->upd_last) {
                            continue;
                        }

                        # Fix loop twin
                        if (in_array($item_link, $loop_mem)) {
                            continue;
                        }
                        $loop_mem[] = $item_link;

                        # Check if entry exists
                        $sql      = new SelectStatement();
                        $old_post = $sql
                            ->columns([
                                'P.post_id',
                                'P.post_status',
                            ])
                            ->from($sql->as(App::con()->prefix() . App::blog()::POST_TABLE_NAME, 'P'))
                            ->join(
                                (new JoinStatement())
                                    ->inner()
                                    ->from($sql->as(App::con()->prefix() . App::meta()::META_TABLE_NAME, 'M'))
                                    ->on('P.post_id = M.post_id')
                                    ->statement()
                            )
                            ->where('blog_id = ' . $sql->quote(App::blog()->id()))
                            ->and("meta_type = '" . My::META_PREFIX . "url'")
                            ->and('meta_id = ' . $sql->quote($item_link))
                            ->select();

                        if (is_null($old_post)) {
                            $old_post = MetaRecord::newFromArray([]);
                        }

                        # Prepare entry Cursor
                        $cur_post->clean();
                        $cur_post->setField('post_dt', date('Y-m-d H:i:s', $item_TS));
                        if ($row->cat_id) {
                            $cur_post->setField('cat_id', $row->cat_id);
                        }
                        $post_content = $item->content ? $item->content : $item->description;
                        $cur_post->setField('post_format', 'xhtml');
                        $cur_post->setField('post_content', Html::absoluteURLs($post_content, $feed->link));
                        $cur_post->setField('post_title', $item->title ? $item->title : Text::cutString(Html::clean(is_string($cur_post->getField('post_content')) ? $cur_post->getField('post_content') : ''), 60));
                        $creator = $item->creator ? $item->creator : $row->owner;

                        try {
                            # Create entry
                            if ($old_post->isEmpty()) {
                                # Post
                                $cur_post->setField('user_id', App::auth()->userID());
                                $cur_post->setField('post_format', 'xhtml');
                                $cur_post->setField('post_status', $s->post_status_new ? App::blog()::POST_PUBLISHED : App::blog()::POST_UNPUBLISHED);
                                $cur_post->setField('post_open_comment', 0);
                                $cur_post->setField('post_open_tb', 0);

                                $post_id = App::auth()->sudo(
                                    App::blog()->addPost(...),
                                    $cur_post
                                );

                                # Auto tweet new post
                                if (!empty($cur_post->getField('post_status'))) {
                                    $is_new_published_entry = true;
                                }

                                # Update entry
                            } else {
                                $post_id = is_numeric($old_post->f('post_id')) ? (int) $old_post->f('post_id') : 0;

                                App::auth()->sudo(
                                    App::blog()->updPost(...),
                                    $post_id,
                                    $cur_post
                                );

                                # Quick delete old meta
                                $sql = new DeleteStatement();
                                $sql->from(App::con()->prefix() . App::meta()::META_TABLE_NAME)
                                    ->where('post_id = ' . $post_id)
                                    ->and($sql->like('meta_type', My::META_PREFIX . '%'))
                                    ->delete();

                                # Delete old tags
                                App::auth()->sudo(
                                    App::meta()->delPostMeta(...),
                                    $post_id,
                                    'tag'
                                );
                            }

                            # Quick add new meta

                            $cur_meta->clean();
                            $cur_meta->setField('post_id', $post_id);
                            $cur_meta->setField('meta_type', My::META_PREFIX . 'url');
                            $cur_meta->setField('meta_id', $item_link);
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->setField('post_id', $post_id);
                            $cur_meta->setField('meta_type', My::META_PREFIX . 'author');
                            $cur_meta->setField('meta_id', $creator);
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->setField('post_id', $post_id);
                            $cur_meta->setField('meta_type', My::META_PREFIX . 'site');
                            $cur_meta->setField('meta_id', $row->url);
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->setField('post_id', $post_id);
                            $cur_meta->setField('meta_type', My::META_PREFIX . 'sitename');
                            $cur_meta->setField('meta_id', $row->name);
                            $cur_meta->insert();

                            $cur_meta->clean();
                            $cur_meta->setField('post_id', $post_id);
                            $cur_meta->setField('meta_type', My::META_PREFIX . 'id');
                            $cur_meta->setField('meta_id', $row->id);
                            $cur_meta->insert();

                            # Add new tags
                            $tags = App::meta()->splitMetaValues($row->tags);
                            if ($row->get_tags) {
                                # Some feed subjects contains more than one tag
                                foreach ($item->subject as $subjects) {
                                    $tmp  = App::meta()->splitMetaValues($subjects);
                                    $tags = array_merge($tags, $tmp);
                                }
                                $tags = array_unique($tags);
                            }
                            $formated_tags = [];
                            foreach ($tags as $tag) {
                                # Change tags case
                                switch ((int) $s->tag_case) {
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
                                    App::auth()->sudo(
                                        App::meta()->delPostMeta(...),
                                        $post_id,
                                        'tag',
                                        App::meta()::sanitizeMetaID($tag)
                                    );
                                    App::auth()->sudo(
                                        App::meta()->setPostMeta(...),
                                        $post_id,
                                        'tag',
                                        App::meta()::sanitizeMetaID($tag)
                                    );
                                }
                            }
                        } catch (Exception $e) {
                            App::con()->rollback();
                            $this->enableUser(false);
                            $this->unlockUpdate();

                            throw $e;
                        }
                    }
                    App::con()->commit();
                }

                # --BEHAVIOR-- zoneclearFeedServerAfterCheckFeedUpdate -- FeedRow
                App::behavior()->callBehavior('zoneclearFeedServerAfterCheckFeedUpdate', $row);
            }
        }
        if ($enabled) {
            $this->enableUser(false);
        }
        $this->unlockUpdate();

        return true;
    }

    /**
     * Set permission to update post table.
     *
     * @param  boolean $enable Enable or disable perm
     */
    public function enableUser(bool $enable = false): void
    {
        # Enable
        if ($enable) {
            // backup current user
            if (!App::auth()->userID()) {
                throw new Exception('Unable to backup user');
            }
            $this->user = App::auth()->userID();
            // set zcfs posts user
            if (!App::auth()->checkUser($this->settings->user)) {
                throw new Exception('Unable to set user');
            }
            # Disable
        } else {
            // restore current user
            App::auth()->checkUser($this->user ?? '');
        }
    }

    /**
     * Read and parse external feeds.
     *
     * @param   string  $url    The feed URL
     *
     * @return  Parser|false    The parsed feed
     */
    public static function readFeed(string $url)
    {
        try {
            $feed_reader = new Reader();
            $feed_reader->setCacheDir(App::config()->cacheRoot());
            $feed_reader->setTimeout(self::NET_HTTP_TIMEOUT);
            $feed_reader->setMaxRedirects(self::NET_HTTP_MAX_REDIRECT);
            $feed_reader->setUserAgent(self::NET_HTTP_AGENT);

            return $feed_reader->parse($url);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Trigger blog.
     */
    private function trigger(): void
    {
        App::blog()->triggerBlog();
    }

    /**
     * Check if an URL is well formed.
     *
     * @param   string  $url    The URL
     *
     * @return  bool    True if URL is allowed
     */
    public static function validateURL(string $url): bool
    {
        return false !== strpos($url, 'http://')
            || false !== strpos($url, 'https://');
    }

    /**
     * Get full URL.
     *
     * Know bugs: anchor is not well parsed.
     *
     * @param   string  $root   The root URL
     * @param   string  $url    A URL
     *
     * @return  string  The parse URL
     */
    public static function absoluteURL(string $root, string $url): string
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
     * Get list of (super)admins of current blog.
     *
     * @return  array<string,string>    List of UserCNs/UserIds
     */
    public function getAllBlogAdmins(): array
    {
        $admins = [];

        # Get super admins
        $sql = new SelectStatement();
        $rs  = $sql
            ->from(App::con()->prefix() . App::auth()::USER_TABLE_NAME)
            ->columns([
                'user_id',
                'user_super',
                'user_name',
                'user_firstname',
                'user_displayname',
            ])
            ->where('user_super = 1')
            ->and('user_status = 1')
            ->select();

        if (!is_null($rs) && !$rs->isEmpty()) {
            while ($rs->fetch()) {
                $user_cn = App::users()->getUserCN(
                    $rs->f('user_id'),
                    $rs->f('user_name'),
                    $rs->f('user_firstname'),
                    $rs->f('user_displayname')
                );
                $admins[$user_cn . ' (super admin)'] = $rs->f('user_id');
            }
        }

        # Get admins
        $sql = new SelectStatement();
        $rs  = $sql
            ->columns([
                'U.user_id',
                'U.user_super',
                'U.user_name',
                'U.user_firstname',
                'U.user_displayname',
            ])
            ->from($sql->as(App::con()->prefix() . App::auth()::USER_TABLE_NAME, 'U'))
            ->join(
                (new JoinStatement())
                    ->left()
                    ->from($sql->as(App::con()->prefix() . App::auth()::PERMISSIONS_TABLE_NAME, 'P'))
                    ->on('U.user_id = P.user_id')
                    ->statement()
            )
            ->where('U.user_status = 1')
            ->and('P.blog_id = ' . $sql->quote(App::blog()->id()))
            ->and($sql->like('P.permissions', '%|admin|%'))
            ->select();

        if (!is_null($rs) && !$rs->isEmpty()) {
            while ($rs->fetch()) {
                $user_cn = App::users()->getUserCN(
                    $rs->f('user_id'),
                    $rs->f('user_name'),
                    $rs->f('user_firstname'),
                    $rs->f('user_displayname')
                );
                $admins[$user_cn . ' (admin)'] = $rs->f('user_id');
            }
        }

        return $admins;
    }

    /**
     * Get list of urls where entries could be hacked.
     *
     * @return  array<string,string>    List of names/types of URLs
     */
    public static function getPublicUrlTypes(): array
    {
        $types = new ArrayObject([
            __('Home page')      => 'default',
            __('Entries pages')  => 'post',
            __('Tags pages')     => 'tag',
            __('Archives pages') => 'archive',
            __('Category pages') => 'category',
            __('Entries feed')   => 'feed',
        ]);

        # --BEHAVIOR-- zoneclearFeedServerPublicUrlTypes -- ArrayObject
        App::behavior()->callBehavior('zoneclearFeedServerPublicUrlTypes', $types);

        return $types->getArrayCopy();
    }
}
