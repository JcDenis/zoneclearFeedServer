<?php

declare(strict_types=1);

namespace Dotclear\Plugin\zoneclearFeedServer;

/**
 * @brief       zoneclearFeedServer settings.
 * @ingroup     zoneclearFeedServer
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Settings
{
    /** @var    Settings    Self instance */
    private static $instance;

    public readonly bool $active;

    public readonly bool $pub_active;

    public readonly bool $post_status_new;

    public readonly int $bhv_pub_upd;

    public readonly int $update_limit;

    public readonly bool $keep_empty_feed;

    public readonly int $tag_case;

    /** @var    array<int,string> */
    public readonly array $post_full_tpl;

    /** @var    array<int,string> */
    public readonly array $post_title_redir;

    public readonly string $user;

    /**
     * Constructor set up plugin settings.
     */
    protected function __construct()
    {
        $s = My::settings();

        $update_limit = is_numeric($s->get('update_limit')) ? (int) $s->get('update_limit') : 1;

        $this->active           = !empty($s->get('active'));
        $this->pub_active       = !empty($s->get('pub_active'));
        $this->post_status_new  = !empty($s->get('post_status_new'));
        $this->bhv_pub_upd      = is_numeric($s->get('bhv_pub_upd')) ? (int) $s->get('bhv_pub_upd') : 1;
        $this->update_limit     = $update_limit < 1 ? 10 : $update_limit;
        $this->keep_empty_feed  = !empty($s->get('keep_empty_feed'));
        $this->tag_case         = is_numeric($s->get('tag_case')) ? (int) $s->get('tag_case') : 0;
        $this->post_full_tpl    = is_array($s->get('post_full_tpl')) ? $s->get('post_full_tpl') : [];
        $this->post_title_redir = is_array($s->get('post_title_redir')) ? $s->get('post_title_redir') : [];
        $this->user             = is_string($s->get('user')) ? $s->get('user') : '';
    }

    public static function instance(): Settings
    {
        if (!(self::$instance instanceof Settings)) {
            self::$instance = new Settings();
        }

        return self::$instance;
    }

    public function isset(string $key): bool
    {
        return property_exists($this, $key);
    }

    public function get(string $key): mixed
    {
        return $this->{$key} ?? null;
    }

    /**
     * Overwrite a plugin settings (in db).
     *
     * @param   string  $key    The setting ID
     * @param   mixed   $value  The setting value
     *
     * @return  bool True on success
     */
    public function set(string $key, mixed $value): bool
    {
        $s = My::settings();

        if (!is_null($s) && property_exists($this, $key) && settype($value, gettype($this->{$key})) === true) {
            $s->put($key, $value, gettype($this->{$key}));

            return true;
        }

        return false;
    }

    /**
     * List defined settings keys.
     *
     * @return  array<string,mixed>     The settings keys
     */
    public function dump(): array
    {
        return get_object_vars($this);
    }
}
