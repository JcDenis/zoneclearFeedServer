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

use dcCore;
use dcMeta;
use Dotclear\Database\Statement\{
    DeleteStatement,
    SelectStatement
};
use Dotclear\Plugin\Uninstaller\{
    ActionDescriptor,
    CleanerDescriptor,
    CleanerParent,
    CleanersStack,
    ValueDescriptor
};

/**
 * Plugin Uninstaller Cleaner object.
 *
 * This add special action for feed posts metadata.
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
        $sql->from(dcCore::app()->prefix . dcMeta::META_TABLE_NAME)
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
            $sql->from(dcCore::app()->prefix . dcMeta::META_TABLE_NAME)
                ->where($sql->like('meta_type', My::META_PREFIX . '%'))
                ->delete();

            return true;
        }

        return false;
    }
}
