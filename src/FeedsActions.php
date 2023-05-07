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

use ArrayObject;
use dcActions;
use dcCore;
use dcPage;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * Backend feeds list actions handler.
 */
class FeedsActions extends dcActions
{
    public ZoneclearFeedServer $zcfs;

    /**
     * @param   string                  $uri
     * @param   array<string,mixed>     $redirect_args
     */
    public function __construct(string $uri, array $redirect_args = [])
    {
        $this->zcfs = ZoneclearFeedServer::instance();

        parent::__construct($uri, $redirect_args);

        $this->redirect_fields = [
            'sortby', 'order', 'page', 'nb',
        ];
        $this->field_entries = 'feeds';
        $this->caller_title  = __('Feeds');
        $this->loadDefaults();
    }

    protected function loadDefaults(): void
    {
        FeedsDefaultActions::addDefaultFeedsActions($this);

        # --BEHAVIOR-- zoneclearFeedServerAddFeedsActions - FeedsActions
        dcCore::app()->callBehavior('zoneclearFeedServerAddFeedsActions', $this);
    }

    public function beginPage(string $breadcrumb = '', string $head = ''): void
    {
        echo
        '<html><head><title>' . __('Feeds server') . '</title>' .
        dcPage::jsLoad('js/_posts_actions.js') .
        $head .
        '</script></head><body>' .
        $breadcrumb .
        '<p><a class="back" href="' . $this->getRedirection(true) . '">' .
        __('Back to feeds list') . '</a></p>';
    }

    public function endPage(): void
    {
        echo '</body></html>';
    }

    public function error(Exception $e): void
    {
        dcCore::app()->error->add($e->getMessage());
        $this->beginPage(
            dcPage::breadcrumb([
                Html::escapeHTML((string) dcCore::app()->blog?->name) => '',
                $this->getCallerTitle()                               => $this->getRedirection(true),
                __('Feeds actions')                                   => '',
            ])
        );
        $this->endPage();
    }

    protected function fetchEntries(ArrayObject $from): void
    {
        if (!empty($from['feeds']) && is_array($from['feeds'])) {
            $params = [
                'feed_id' => $from['feeds'],
            ];

            $feeds = ZoneclearFeedServer::instance()->getFeeds($params);
            while ($feeds->fetch()) {
                $row                     = new FeedRow($feeds);
                $this->entries[$row->id] = $row->name;
            }
            $this->rs = $feeds;
        } else {
            $this->rs = MetaRecord::newFromArray([]);
        }
    }
}
