<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer\ClaudetteMcp;

use Dotclear\App;
use Dotclear\Plugin\ClaudetteMcp\McpToolException;
use Dotclear\Plugin\zoneclearFeedServer\FeedRow;
use Dotclear\Plugin\zoneclearFeedServer\ZoneclearFeedServer;
use Exception;

/**
 * @brief       zoneclearFeedServer tool for the plugin ClaudetteMcp: add or update a feed.
 * @ingroup     zoneclearFeedServer
 *
 * Same checks as the feed form of the admin. No deletion: a feed is disabled instead.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class SetFeed extends FeedTool
{
    public function name(): string
    {
        return 'set_feed';
    }

    public function title(): string
    {
        return 'Add or update an aggregated feed';
    }

    public function description(): string
    {
        return 'Add a feed to aggregate on the blog (plugin zoneclearFeedServer), or update one (with its ID from list_feeds). ' .
            'On update, only given arguments are changed. Feeds can not be deleted: set enabled to false to stop reading one. ' .
            'The entries of an enabled feed are added to the blog by the blog itself, at the given interval. ' .
            'Check with list_feeds that the feed does not already exist.';
    }

    public function properties(): array
    {
        return [
            'id' => [
                'type'        => 'integer',
                'description' => 'The ID of the feed to update (see list_feeds), omit to add a new feed.',
            ],
            'name' => [
                'type'        => 'string',
                'description' => 'The feed name, required for a new feed.',
            ],
            'owner' => [
                'type'        => 'string',
                'description' => 'The owner of the feed (person or site), required for a new feed.',
            ],
            'site_url' => [
                'type'        => 'string',
                'description' => 'The address of the site, http or https, required for a new feed.',
            ],
            'feed_url' => [
                'type'        => 'string',
                'description' => 'The address of the RSS or Atom feed, http or https, required for a new feed.',
            ],
            'description' => [
                'type'        => 'string',
                'description' => 'The feed description.',
            ],
            'lang' => [
                'type'        => 'string',
                'description' => 'The language code of the feed (ex: fr).',
            ],
            'category_id' => [
                'type'        => 'integer',
                'description' => 'The category of the entries of the feed (see list_categories), 0 for none.',
            ],
            'tags' => [
                'type'        => 'string',
                'description' => 'Tags added to the entries of the feed, comma separated.',
            ],
            'get_tags' => [
                'type'        => 'boolean',
                'description' => 'Also add the tags given by the feed to its entries. Default for a new feed: true.',
            ],
            'interval' => [
                'type'        => 'string',
                'enum'        => array_keys(self::INTERVALS),
                'description' => 'How often the feed is read. Default for a new feed: day.',
            ],
            'enabled' => [
                'type'        => 'boolean',
                'description' => 'Read the feed or not. Default for a new feed: false, enable it once checked.',
            ],
        ];
    }

    public function readOnly(): bool
    {
        return false;
    }

    public function call(array $args): array
    {
        $z  = ZoneclearFeedServer::instance();
        $id = $this->int($args, 'id');

        $current = null;
        if ($id !== 0) {
            $rs = $z->getFeeds(['feed_id' => $id]);
            if ($rs->isEmpty()) {
                throw new McpToolException('Unknown feed, see list_feeds');
            }
            $current = $this->describeFeed(new FeedRow($rs));
        }

        $value = function (string $name, string $default = '') use ($args, $current): string {
            if ($this->has($args, $name)) {
                return $this->str($args, $name);
            }

            return is_string($v = $current[$name] ?? null) ? $v : $default;
        };
        $bool = function (string $name, bool $default) use ($args, $current): bool {
            if ($this->has($args, $name)) {
                return $this->bool($args, $name, $default);
            }

            return is_bool($v = $current[$name] ?? null) ? $v : $default;
        };

        $name     = $value('name');
        $owner    = $value('owner');
        $url      = $value('site_url');
        $feed     = $value('feed_url');
        $interval = $value('interval', 'day');
        $cat_id   = $this->has($args, 'category_id') ? $this->int($args, 'category_id') : (is_int($c = $current['category_id'] ?? null) ? $c : 0);

        // Same checks as the admin form
        if ($name === '') {
            throw new McpToolException('A feed needs a name');
        }
        if ($owner === '') {
            throw new McpToolException('A feed needs an owner');
        }
        if (!preg_match('#^https?://#i', $url)) {
            throw new McpToolException('A feed needs a site address in http or https');
        }
        if (!preg_match('#^https?://#i', $feed)) {
            throw new McpToolException('A feed needs a feed address in http or https');
        }
        if (!isset(self::INTERVALS[$interval])) {
            throw new McpToolException('Unknown interval, use one of: ' . implode(', ', array_keys(self::INTERVALS)));
        }
        if ($cat_id > 0 && App::blog()->getCategory($cat_id)->isEmpty()) {
            throw new McpToolException('Unknown category, see list_categories');
        }
        $same = ['feed_feed' => $feed];
        if ($id !== 0) {
            $same['sql'] = 'AND feed_id <> ' . $id . ' ';
        }
        if ($z->getFeeds($same, true)->f(0)) {
            throw new McpToolException('A feed with the same feed address already exists, see list_feeds');
        }

        $cur = $z->openCursor();
        $cur->setField('feed_name', $name);
        $cur->setField('feed_desc', $value('description'));
        $cur->setField('feed_owner', $owner);
        $cur->setField('feed_url', $url);
        $cur->setField('feed_feed', $feed);
        $cur->setField('feed_lang', $value('lang'));
        $cur->setField('feed_tags', $value('tags'));
        $cur->setField('feed_get_tags', (int) $bool('get_tags', true));
        $cur->setField('cat_id', $cat_id > 0 ? $cat_id : null);
        $cur->setField('feed_status', (int) $bool('enabled', false));
        $cur->setField('feed_upd_int', self::INTERVALS[$interval]);
        if ($id === 0) {
            $cur->setField('feed_tweeter', '');
        }

        try {
            if ($id === 0) {
                $id = $z->addFeed($cur);
            } else {
                $z->updateFeed($id, $cur);
            }
        } catch (Exception $e) {
            throw new McpToolException($e->getMessage());
        }

        $rs = $z->getFeeds(['feed_id' => $id]);
        if ($rs->isEmpty()) {
            throw new McpToolException('Feed not saved');
        }

        return $this->describeFeed(new FeedRow($rs));
    }
}
