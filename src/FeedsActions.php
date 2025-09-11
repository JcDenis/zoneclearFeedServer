<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use ArrayObject;
use Dotclear\App;
use Dotclear\Core\Backend\Action\Actions;
use Dotclear\Core\Backend\Page;
use Dotclear\Database\MetaRecord;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Html;
use Exception;

/**
 * @brief       zoneclearFeedServer feeds list actions.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class FeedsActions extends Actions
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
        App::behavior()->callBehavior('zoneclearFeedServerAddFeedsActions', $this);
    }

    public function beginPage(string $breadcrumb = '', string $head = ''): void
    {
        Page::openModule(
            My::name(),
            Page::jsLoad('js/_posts_actions.js') .
            $head
        );
        echo
        $breadcrumb .
        (new Para())->items([
            (new Link())
                ->class('back')
                ->href($this->getRedirection(true))
                ->text(__('Back to feeds list')),
        ])->render();
    }

    public function endPage(): void
    {
        Page::closeModule();
    }

    public function error(Exception $e): void
    {
        App::error()->add($e->getMessage());
        $this->beginPage(
            Page::breadcrumb([
                Html::escapeHTML(App::blog()->name()) => '',
                $this->getCallerTitle()               => $this->getRedirection(true),
                __('Feeds actions')                   => '',
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
                $row                              = new FeedRow($feeds);
                $this->entries[(string) $row->id] = $row->name;
            }
            $this->rs = $feeds;
        } else {
            $this->rs = MetaRecord::newFromArray([]);
        }
    }
}
