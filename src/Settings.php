<?php declare(strict_types=1);

namespace Gratis\Popup;

defined('ABSPATH') || exit;

/**
 * Global popup behaviour settings, stored as a single option array and exposed
 * via the WordPress Settings API on a submenu under the Popups CPT.
 */
final class Settings {

    /** Option name holding all global settings. */
    public const OPTION = 'gratis_popup_settings';

    /** Settings group / page slug. */
    private const GROUP = 'gratis_popup_settings_group';
    private const PAGE  = 'gratis-popup-settings';

    /**
     * Default values for every global setting.
     *
     * @return array<string,mixed>
     */
    public static function defaults(): array {
        return [
            'enabled'           => 1,
            'show_logged_in'    => 0,
            'default_frequency' => 0,
        ];
    }

    /**
     * Fetch the merged settings array (stored values over defaults).
     *
     * @return array<string,mixed>
     */
    public static function all(): array {
        $stored = get_option(self::OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }
        return array_merge(self::defaults(), $stored);
    }

    /**
     * Boolean accessor for a single setting.
     */
    public static function get_bool(string $key, bool $fallback = false): bool {
        $all = self::all();
        return isset($all[$key]) ? (bool) $all[$key] : $fallback;
    }

    /**
     * Integer accessor for a single setting.
     */
    public static function get_int(string $key, int $fallback = 0): int {
        $all = self::all();
        return isset($all[$key]) ? (int) $all[$key] : $fallback;
    }

    /**
     * Register the settings submenu page.
     */
    public static function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . PopupPostType::POST_TYPE,
            __('Popup Settings', 'gratis-popup'),
            __('Settings', 'gratis-popup'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /**
     * Register the option and its sanitisation callback.
     */
    public static function register_settings(): void {
        register_setting(
            self::GROUP,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [__CLASS__, 'sanitize'],
                'default'           => self::defaults(),
            ]
        );
    }

    /**
     * Sanitise the incoming settings array.
     *
     * @param mixed $input Raw option input.
     * @return array<string,mixed>
     */
    public static function sanitize($input): array {
        $input = is_array($input) ? $input : [];
        return [
            'enabled'           => empty($input['enabled']) ? 0 : 1,
            'show_logged_in'    => empty($input['show_logged_in']) ? 0 : 1,
            'default_frequency' => isset($input['default_frequency']) ? absint($input['default_frequency']) : 0,
        ];
    }

    /**
     * Render the settings screen.
     */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-popup'));
        }

        $settings = self::all();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Popup Settings', 'gratis-popup') . '</h1>';
        echo '<p>' . esc_html__('Global behaviour for all Gratis popups. Per-popup triggers and display rules are configured on each popup.', 'gratis-popup') . '</p>';

        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);

        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Enable popups', 'gratis-popup') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPTION) . '[enabled]" value="1"' . checked((int) $settings['enabled'], 1, false) . '> ';
        echo esc_html__('Master switch — display popups on the front end.', 'gratis-popup') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row">' . esc_html__('Logged-in users', 'gratis-popup') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPTION) . '[show_logged_in]" value="1"' . checked((int) $settings['show_logged_in'], 1, false) . '> ';
        echo esc_html__('Also show popups to logged-in users.', 'gratis-popup') . '</label>';
        echo '</td></tr>';

        echo '<tr><th scope="row"><label for="gp_default_frequency">' . esc_html__('Default frequency cap (days)', 'gratis-popup') . '</label></th><td>';
        echo '<input type="number" min="0" id="gp_default_frequency" name="' . esc_attr(self::OPTION) . '[default_frequency]" value="' . esc_attr((string) (int) $settings['default_frequency']) . '" class="small-text"> ';
        echo '<p class="description">' . esc_html__('Applied to popups that do not set their own frequency cap. 0 = once per session.', 'gratis-popup') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';

        submit_button();
        echo '</form>';
        echo '</div>';
    }
}
