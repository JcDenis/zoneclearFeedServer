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
declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\Database\MetaRecord;

/**
 * Feed meta record row type hinting.
 */
class FeedRow
{
    public readonly int $id;
    public readonly int $creadt;
    public readonly int $upddt;
    public readonly string $type;
    public readonly ?string $blog_id;
    public readonly ?int $cat_id;
    public readonly int $upd_int;
    public readonly int $upd_last;
    public readonly int $status;
    public readonly string $name;
    public readonly string $desc;
    public readonly string $url;
    public readonly string $feed;
    public readonly string $tags;
    public readonly bool $get_tags;
    public readonly string $owner;
    public readonly string $tweeter;
    public readonly string $lang;
    public readonly int $nb_out;
    public readonly int $nb_in;

    /**
     * Constructor sets properties.
     */
    public function __construct(
        public readonly MetaRecord $rs
    ) {
        $this->id       = is_numeric($this->rs->f('feed_id')) ? (int) $this->rs->f('feed_id') : 0;
        $this->creadt   = is_numeric($this->rs->f('feed_creadt')) ? (int) $this->rs->f('feed_creadt') : 0;
        $this->upddt    = is_numeric($this->rs->f('feed_upddt')) ? (int) $this->rs->f('feed_upddt') : 0;
        $this->type     = is_string($this->rs->f('feed_type')) ? $this->rs->f('feed_type') : '';
        $this->blog_id  = is_string($this->rs->f('blog_id')) ? $this->rs->f('blog_id') : null;
        $this->cat_id   = is_numeric($this->rs->f('cat_id')) ? (int) $this->rs->f('cat_id') : null;
        $this->upd_int  = is_numeric($this->rs->f('feed_upd_int')) ? (int) $this->rs->f('feed_upd_int') : 0;
        $this->upd_last = is_numeric($this->rs->f('feed_upd_last')) ? (int) $this->rs->f('feed_upd_last') : 0;
        $this->status   = is_numeric($this->rs->f('feed_status')) ? (int) $this->rs->f('feed_status') : 0;
        $this->name     = is_string($this->rs->f('feed_name')) ? $this->rs->f('feed_name') : '';
        $this->desc     = is_string($this->rs->f('feed_desc')) ? $this->rs->f('feed_desc') : '';
        $this->url      = is_string($this->rs->f('feed_url')) ? $this->rs->f('feed_url') : '';
        $this->feed     = is_string($this->rs->f('feed_feed')) ? $this->rs->f('feed_feed') : '';
        $this->tags     = is_string($this->rs->f('feed_tags')) ? $this->rs->f('feed_tags') : '';
        $this->get_tags = !empty($this->rs->f('feed_get_tags'));
        $this->owner    = is_string($this->rs->f('feed_owner')) ? $this->rs->f('feed_owner') : '';
        $this->tweeter  = is_string($this->rs->f('feed_tweeter')) ? $this->rs->f('feed_tweeter') : '';
        $this->lang     = is_string($this->rs->f('feed_lang')) ? $this->rs->f('feed_lang') : '';
        $this->nb_out   = is_numeric($this->rs->f('feed_nb_out')) ? (int) $this->rs->f('feed_nb_out') : 0;
        $this->nb_in    = is_numeric($this->rs->f('feed_nb_in')) ? (int) $this->rs->f('feed_nb_in') : 0;
    }
}
