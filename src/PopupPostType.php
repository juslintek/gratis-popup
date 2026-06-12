<?php declare(strict_types=1);

namespace Gratis\Popup;

defined('ABSPATH') || exit;

/**
 * Registers the gratis_popup custom post type and its settings meta.
 *
 * The post content (block/visual editor) is the popup body; per-popup
 * behaviour (trigger, display rules, frequency cap, etc.) is stored as
 * registered post meta so it is available over REST and to the renderer.
 */
final class PopupPostType {

    /** Custom post type slug. */
    public const POST_TYPE = 'gratis_popup';

    /**
     * Meta keys with their sanitisation type, used both for registration and
     * by the meta box save handler.
     *
     * @return array<string,array{type:string,default:mixed}>
     */
    public static function metaSchema(): array {
        return [
            '_gp_trigger'        => ['type' => 'string', 'default' => 'time'],
            '_gp_delay'          => ['type' => 'integer', 'default' => 5],
            '_gp_scroll_percent' => ['type' => 'integer', 'default' => 50],
            '_gp_click_selector' => ['type' => 'string', 'default' => ''],
            '_gp_position'       => ['type' => 'string', 'default' => 'center'],
            '_gp_animation'      => ['type' => 'string', 'default' => 'fade'],
            '_gp_overlay'        => ['type' => 'integer', 'default' => 1],
            '_gp_frequency_days' => ['type' => 'integer', 'default' => 0],
            '_gp_target_type'    => ['type' => 'string', 'default' => 'all'],
            '_gp_target_ids'     => ['type' => 'string', 'default' => ''],
        ];
    }

    /**
     * Register the post type and its meta.
     */
    public static function register(): void {
        register_post_type(self::POST_TYPE, [
            'labels'       => [
                'name'          => __('Popups', 'gratis-popup'),
                'singular_name' => __('Popup', 'gratis-popup'),
                'add_new'       => __('Add Popup', 'gratis-popup'),
                'add_new_item'  => __('Add New Popup', 'gratis-popup'),
                'edit_item'     => __('Edit Popup', 'gratis-popup'),
                'menu_name'     => __('Popups', 'gratis-popup'),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-megaphone',
            'supports'     => ['title', 'editor', 'custom-fields'],
        ]);

        foreach (self::metaSchema() as $key => $spec) {
            register_post_meta(self::POST_TYPE, $key, [
                'type'              => $spec['type'],
                'single'            => true,
                'default'           => $spec['default'],
                'show_in_rest'      => true,
                'sanitize_callback' => 'integer' === $spec['type'] ? 'absint' : 'sanitize_text_field',
                'auth_callback'     => static function (): bool {
                    return current_user_can('edit_posts');
                },
            ]);
        }
    }
}
