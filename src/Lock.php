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

use Dotclear\Helper\File\{
    Files,
    Path
};

class Lock
{
    /**
     * Locked files resource stack.
     *
     * @var    array<string,resource>
     */
    protected static $lock_stack = [];

    /**
     * Locked files status stack.
     *
     * @var    array<string,bool>
     */
    protected static $lock_disposable = [];

    /**
     * Last lock attempt error
     *
     * @var    string
     */
    protected static $lock_error = '';

    /**
     * Lock file.
     *
     * @param   string  $file           The file path
     * @param   bool    $disposable     File only use to lock
     *
     * @return null|string    Clean file path on success, empty string on error, null if already locked
     */
    public static function lock(string $file, bool $disposable = false): ?string
    {
        # Real path
        $file = Path::real($file, false);
        if (false === $file) {
            self::$lock_error = __("Can't get file path");

            return '';
        }

        # not a dir
        if (is_dir($file)) {
            self::$lock_error = __("Can't lock a directory");

            return '';
        }

        # already marked as locked
        if (isset(self::$lock_stack[$file]) || $disposable && file_exists($file)) {
            return null;
        }

        # Need flock function
        if (!function_exists('flock')) {
            self::$lock_error = __("Can't call php function named flock");

            return '';
        }

        # Make dir
        if (!is_dir(dirname($file))) {
            Files::makeDir(dirname($file), true);
        }

        # Open new file
        if (!file_exists($file)) {
            $resource = @fopen($file, 'w');
            if ($resource === false) {
                self::$lock_error = __("Can't create file");

                return '';
            }
            fwrite($resource, '1', strlen('1'));
        //fclose($resource);
        } else {
            # Open existsing file
            $resource = @fopen($file, 'r+');
            if ($resource === false) {
                self::$lock_error = __("Can't open file");

                return '';
            }
        }

        # Lock file
        if (!flock($resource, LOCK_EX | LOCK_NB)) {
            self::$lock_error = __("Can't lock file");

            return '';
        }

        self::$lock_stack[$file]      = $resource;
        self::$lock_disposable[$file] = $disposable;

        return $file;
    }

    /**
     * Unlock file.
     *
     * @param   string  $file           The file to unlock
     */
    public static function unlock(string $file): void
    {
        if (isset(self::$lock_stack[$file])) {
            fclose(self::$lock_stack[$file]);
            if (!empty(self::$lock_disposable[$file]) && file_exists($file)) {
                @unlink($file);
            }
            unset(
                self::$lock_stack[$file],
                self::$lock_disposable[$file]
            );
        }
    }

    /**
     * Get last error from lock method.
     *
     * @return  string  The last lock error
     */
    public static function getlastLockError(): string
    {
        return self::$lock_error;
    }
}
