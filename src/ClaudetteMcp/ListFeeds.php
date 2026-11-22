<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer\ClaudetteMcp;

use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\zoneclearFeedServer\FeedRow;
use Dotclear\Plugin\zoneclearFeedServer\ZoneclearFeedServer;

/**
 * @brief       zoneclearFeedServer tool for the plugin ClaudetteMcp: list feeds.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class ListFeeds extends FeedTool
{
    public function name(): string
    {
        return 'list_feeds';
    }

    public function title(): string
    {
        return 'List aggregated feeds';
    }

    public function description(): string
    {
        return 'List the feeds aggregated on the blog (plugin zoneclearFeedServer), by name, with their state: ' .
            '"enabled", "last_update" (the blog reads a feed at the given interval, from public pages) and "late" (an enabled feed not read for two intervals). ' .
            'A feed which can not be read may be disabled by the blog itself.';
    }

    public function properties(): array
    {
        return [
            'status' => [
                'type'        => 'string',
                'enum'        => ['all', 'enabled', 'disabled'],
                'description' => 'Feeds status, default: all.',
            ],
            'limit' => [
                'type'        => 'integer',
                'description' => 'Feeds per page, 1 to 100, default: 50.',
            ],
            'page' => [
                'type'        => 'integer',
                'description' => 'Page number, default: 1.',
            ],
        ];
    }

    public function call(array $args): array
    {
        $status = $this->str($args, 'status', 'all');
        $limit  = $this->int($args, 'limit', 50, 1, 100);
        $page   = $this->int($args, 'page', 1, 1);
        $params = ['order' => 'lowername ASC'];

        if ($status === 'enabled' || $status === 'disabled') {
            $params['feed_status'] = $status === 'enabled' ? 1 : 0;
        } elseif ($status !== 'all') {
            throw new McpToolException('Unknown status, use one of: all, enabled, disabled');
        }

        $z     = ZoneclearFeedServer::instance();
        $total = $z->getFeeds($params, true)->f(0);

        $params['limit'] = [($page - 1) * $limit, $limit];

        $rs    = $z->getFeeds($params);
        $feeds = [];
        while ($rs->fetch()) {
            $feeds[] = $this->describeFeed(new FeedRow($rs));
        }

        return [
            'total' => is_numeric($total) ? (int) $total : 0,
            'page'  => $page,
            'limit' => $limit,
            'feeds' => $feeds,
        ];
    }
}
