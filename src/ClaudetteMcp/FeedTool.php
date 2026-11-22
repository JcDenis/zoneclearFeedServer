<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer\ClaudetteMcp;

use Dotclear\App;
use Dotclear\Plugin\ClaudetteMcp\McpTool;
use Dotclear\Plugin\zoneclearFeedServer\FeedRow;
use Dotclear\Plugin\zoneclearFeedServer\ZoneclearFeedServer;

/**
 * @brief       zoneclearFeedServer tools helper for the plugin ClaudetteMcp.
 * @ingroup     zoneclearFeedServer
 *
 * ClaudetteMcp connects an AI assistant to the blog: these tools let the assistant manage the feeds.
 * These classes are only loaded by ClaudetteMcp, which is not required by zoneclearFeedServer.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class FeedTool extends McpTool
{
    /**
     * Update intervals of the admin form, by name.
     *
     * @var     array<string, int>  INTERVALS
     */
    protected const INTERVALS = [
        'hour'       => 3600,
        'two_hours'  => 7200,
        'half_day'   => 43200,
        'day'        => 86400,
        'two_days'   => 172800,
        'week'       => 604800,
    ];

    /**
     * Family of tools for the keys of ClaudetteMcp (string, not constant: older ClaudetteMcp versions have none).
     */
    public function family(): string
    {
        return 'extras';
    }

    /**
     * Like in backend: the plugin must be configured on the blog (active, with a user for its entries),
     * its page is for blog administrators.
     */
    public function available(): bool
    {
        $s = ZoneclearFeedServer::instance()->settings;

        return $s->active && $s->user !== '' && $this->can(App::auth()::PERMISSION_ADMIN);
    }

    /**
     * Describe a feed.
     *
     * @return  array<string, mixed>
     */
    protected function describeFeed(FeedRow $row): array
    {
        $interval = array_search($row->upd_int, self::INTERVALS, true);

        return [
            'id'          => $row->id,
            'name'        => $row->name,
            'owner'       => $row->owner,
            'site_url'    => $row->url,
            'feed_url'    => $row->feed,
            'description' => $row->desc,
            'lang'        => $row->lang,
            'category_id' => $row->cat_id ?? 0,
            'tags'        => $row->tags,
            'get_tags'    => $row->get_tags,
            'enabled'     => $row->status === 1,
            'interval'    => $interval === false ? (string) $row->upd_int : $interval,
            'last_update' => $row->upd_last > 0 ? date('Y-m-d H:i', $row->upd_last) : '',
            // An enabled feed not read for two intervals: the blog may not update it
            'late'        => $row->status === 1 && $row->upd_last > 0 && time() > $row->upd_last + 2 * max($row->upd_int, 3600),
        ];
    }
}
