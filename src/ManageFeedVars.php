<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Helper\Html\Html;

/**
 * @brief       zoneclearFeedServer backend vars definition.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ManageFeedVars
{
    /** @var    ManageFeedVars  $container  Self instance  */
    private static $container;

    public readonly int $id;
    public readonly string $name;
    public readonly string $desc;
    public readonly string $owner;
    public readonly string $tweeter;
    public readonly string $url;
    public readonly string $feed;
    public readonly string $lang;
    public readonly string $tags;
    public readonly bool $get_tags;
    public readonly ?int $cat_id;
    public readonly int $status;
    public readonly int $upd_int;

    public readonly bool $can_view_page;
    public readonly string $next_link;
    public readonly string $prev_link;
    public readonly string $next_headlink;
    public readonly string $prev_headlink;

    /**
     * Constructor sets properties.
     */
    protected function __construct()
    {
        $z = ZoneclearFeedServer::instance();

        $feed_headlink = '<link rel="%s" title="%s" href="' . App::backend()->getPageURL() . '&amp;part=feed&amp;feed_id=%s" />';
        $feed_link     = '<a href="' . App::backend()->getPageURL() . '&amp;part=feed&amp;feed_id=%s" title="%s">%s</a>';
        $lang          = App::auth()->getInfo('user_lang');

        // default values
        $feed_id       = 0;
        $feed_name     = '';
        $feed_desc     = '';
        $feed_owner    = '';
        $feed_tweeter  = '';
        $feed_url      = '';
        $feed_feed     = '';
        $feed_lang     = is_string($lang) ? $lang : 'en';
        $feed_tags     = '';
        $feed_get_tags = false;
        $feed_cat_id   = null;
        $feed_status   = 0;
        $feed_upd_int  = 86400;

        $can_view_page = true;
        $next_link     = '';
        $prev_link     = '';
        $next_headlink = '';
        $prev_headlink = '';

        // database values
        if (!empty($_REQUEST['feed_id'])) {
            $feed = $z->getFeeds(['feed_id' => $_REQUEST['feed_id']]);

            if ($feed->isEmpty()) {
                App::error()->add(__('This feed does not exist.'));
                $can_view_page = false;
            } else {
                $row           = new FeedRow($feed);
                $feed_id       = $row->id;
                $feed_name     = $row->name;
                $feed_desc     = $row->desc;
                $feed_owner    = $row->owner;
                $feed_tweeter  = $row->tweeter;
                $feed_url      = $row->url;
                $feed_feed     = $row->feed;
                $feed_lang     = $row->lang;
                $feed_tags     = $row->tags;
                $feed_get_tags = $row->get_tags;
                $feed_cat_id   = $row->cat_id;
                $feed_status   = $row->status;
                $feed_upd_int  = $row->upd_int;

                $next_params = [
                    'sql'   => 'AND feed_id < ' . $feed_id . ' ',
                    'limit' => 1,
                ];
                $next_rs = $z->getFeeds($next_params);

                if (!$next_rs->isEmpty()) {
                    $next_row  = new FeedRow($next_rs);
                    $next_link = sprintf(
                        $feed_link,
                        $next_row->id,
                        Html::escapeHTML($next_row->name),
                        __('next feed') . '&nbsp;&#187;'
                    );
                    $next_headlink = sprintf(
                        $feed_headlink,
                        'next',
                        Html::escapeHTML($next_row->name),
                        $next_row->id
                    );
                }

                $prev_params = [
                    'sql'   => 'AND feed_id > ' . $feed_id . ' ',
                    'limit' => 1,
                ];
                $prev_rs = $z->getFeeds($prev_params);

                if (!$prev_rs->isEmpty()) {
                    $prev_row  = new FeedRow($prev_rs);
                    $prev_link = sprintf(
                        $feed_link,
                        $prev_row->id,
                        Html::escapeHTML($prev_row->name),
                        '&#171;&nbsp;' . __('previous feed')
                    );
                    $prev_headlink = sprintf(
                        $feed_headlink,
                        'previous',
                        Html::escapeHTML($prev_row->name),
                        $prev_row->id
                    );
                }
            }
        }

        // form values
        if (!empty($_POST)) {
            $feed_name     = !empty($_POST['feed_name'])    && is_string($_POST['feed_name']) ? $_POST['feed_name'] : $feed_name;
            $feed_desc     = !empty($_POST['feed_desc'])    && is_string($_POST['feed_desc']) ? $_POST['feed_desc'] : $feed_desc;
            $feed_owner    = !empty($_POST['feed_owner'])   && is_string($_POST['feed_owner']) ? $_POST['feed_owner'] : $feed_owner;
            $feed_tweeter  = !empty($_POST['feed_tweeter']) && is_string($_POST['feed_tweeter']) ? $_POST['feed_tweeter'] : $feed_tweeter;
            $feed_url      = !empty($_POST['feed_url'])     && is_string($_POST['feed_url']) ? $_POST['feed_url'] : $feed_url;
            $feed_feed     = !empty($_POST['feed_feed'])    && is_string($_POST['feed_feed']) ? $_POST['feed_feed'] : $feed_feed;
            $feed_lang     = !empty($_POST['feed_lang'])    && is_string($_POST['feed_lang']) ? $_POST['feed_lang'] : $feed_lang;
            $feed_tags     = !empty($_POST['feed_tags'])    && is_string($_POST['feed_tags']) ? $_POST['feed_tags'] : $feed_tags;
            $feed_get_tags = !empty($_POST['feed_get_tags']);
            $feed_cat_id   = !empty($_POST['feed_cat_id'])  && is_numeric($_POST['feed_cat_id']) ? (int) $_POST['feed_cat_id'] : $feed_cat_id;
            $feed_upd_int  = !empty($_POST['feed_upd_int']) && is_numeric($_POST['feed_upd_int']) ? (int) $_POST['feed_upd_int'] : $feed_upd_int;
            $feed_status   = empty($_POST['feed_status']) ? $feed_status : 1;
        }

        // class values
        $this->id       = $feed_id;
        $this->name     = $feed_name;
        $this->desc     = $feed_desc;
        $this->owner    = $feed_owner;
        $this->tweeter  = $feed_tweeter;
        $this->url      = $feed_url;
        $this->feed     = $feed_feed;
        $this->lang     = $feed_lang;
        $this->tags     = $feed_tags;
        $this->get_tags = (bool) $feed_get_tags;
        $this->cat_id   = $feed_cat_id;
        $this->status   = $feed_status;
        $this->upd_int  = $feed_upd_int;

        $this->can_view_page = $can_view_page;
        $this->next_link     = $next_link;
        $this->prev_link     = $prev_link;
        $this->next_headlink = $next_headlink;
        $this->prev_headlink = $prev_headlink;
    }

    /**
     * Get self instance.
     *
     * @return  ManageFeedVars  Self instance
     */
    public static function instance(): ManageFeedVars
    {
        if (!(self::$container instanceof self)) {
            self::$container = new self();
        }

        return self::$container;
    }

    /**
     * Create or update feed.
     *
     * @return  int     The feed ID
     */
    public function save()
    {
        $z  = ZoneclearFeedServer::instance();
        $id = $this->id;

        // prepare cursor
        $cur = $z->openCursor();
        $cur->setField('feed_name', $this->name);
        $cur->setField('feed_desc', $this->desc);
        $cur->setField('feed_owner', $this->owner);
        $cur->setField('feed_tweeter', $this->tweeter);
        $cur->setField('feed_url', $this->url);
        $cur->setField('feed_feed', $this->feed);
        $cur->setField('feed_lang', $this->lang);
        $cur->setField('feed_tags', $this->tags);
        $cur->setField('feed_get_tags', (int) $this->get_tags);
        $cur->setField('cat_id', $this->cat_id);
        $cur->setField('feed_status', $this->status);
        $cur->setField('feed_upd_int', $this->upd_int);

        # --BEHAVIOR-- adminBeforeZoneclearFeedServerFeedSave - Cursor, int
        App::behavior()->callBehavior('adminBeforeZoneclearFeedServerFeedSave', $cur, $id);

        if (!$id) {
            // create feed
            $id = $z->addFeed($cur);
        } else {
            // update feed
            $z->updateFeed($id, $cur);
        }

        # --BEHAVIOR-- adminAfterZoneclearFeedServerFeedSave - Cursor - int
        App::behavior()->callBehavior('adminAfterZoneclearFeedServerFeedSave', $cur, $id);

        return $id;
    }
}
