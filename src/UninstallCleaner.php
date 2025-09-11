<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

use Dotclear\App;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Plugin\Uninstaller\ActionDescriptor;
use Dotclear\Plugin\Uninstaller\CleanerDescriptor;
use Dotclear\Plugin\Uninstaller\CleanerParent;
use Dotclear\Plugin\Uninstaller\CleanersStack;
use Dotclear\Plugin\Uninstaller\ValueDescriptor;

/**
 * @brief       zoneclearFeedServer uninstall cleaner.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class UninstallCleaner extends CleanerParent
{
    public static function init(CleanersStack $cleaners): void
    {
        $cleaners->set(new UninstallCleaner());
    }

    public function __construct()
    {
        parent::__construct(new CleanerDescriptor(
            id:   My::id() . 'DeletePostsMeta',
            name: __('Feed Server'),
            desc: __('Feed server posts feed metadata'),
            actions: [
                new ActionDescriptor(
                    id:      'delete_all',
                    select:  __('delete selected posts feed metadata'),
                    query:   __('delete "%s" posts feed metadata'),
                    success: __('"%s" posts feed metadata deleted'),
                    error:   __('Failed to delete "%s" posts feed metadata')
                ),
            ]
        ));
    }

    public function distributed(): array
    {
        return [];
    }

    public function values(): array
    {
        $sql = new SelectStatement();
        $sql->from(App::db()->con()->prefix() . App::meta()::META_TABLE_NAME)
            ->columns([
                $sql->as($sql->count('*'), 'counter'),
            ])
            ->where($sql->like('meta_type', My::META_PREFIX . '%'));

        $rs = $sql->select();
        if (is_null($rs) || $rs->isEmpty() || !is_numeric($rs->f('counter'))) {
            return [];
        }

        $res = [];
        while ($rs->fetch()) {
            $res[] = new ValueDescriptor(
                ns:    My::id(),
                count: (int) $rs->f('counter')
            );
        }

        return $res;
    }

    public function execute(string $action, string $ns): bool
    {
        if ($action == 'delete_all') {
            $sql = new DeleteStatement();
            $sql->from(App::db()->con()->prefix() . App::meta()::META_TABLE_NAME)
                ->where($sql->like('meta_type', My::META_PREFIX . '%'))
                ->delete();

            return true;
        }

        return false;
    }
}
