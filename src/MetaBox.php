<?php declare(strict_types=1);

namespace Gratis\Popup;

defined('ABSPATH') || exit;

/**
 * Per-popup settings meta box: trigger configuration, positioning/animation,
 * display rules (page targeting) and the cookie frequency cap.
 */
final class MetaBox {

    private const NONCE_ACTION = 'gratis_popup_save_meta';
    private const NONCE_FIELD  = 'gratis_popup_meta_nonce';

    /**
     * Register the meta box on the popup edit screen.
     */
    public static function register(): void {
        add_meta_box(
            'gratis-popup-settings',
            __('Popup Settings', 'gratis-popup'),
            [__CLASS__, 'render'],
            PopupPostType::POST_TYPE,
            'normal',
            'high'
        );
    }

    /**
     * Read a meta value for the given post, falling back to the schema default.
     */
    private static function meta(int $post_id, string $key): mixed {
        $schema = PopupPostType::metaSchema();
        $stored = get_post_meta($post_id, $key, true);
        if ('' === $stored || null === $stored || false === $stored) {
            return $schema[$key]['default'] ?? '';
        }
        return $stored;
    }

    /**
     * Render the meta box markup.
     *
     * @param \WP_Post $post Current popup post.
     */
    public static function render(\WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $trigger    = (string) self::meta($post->ID, '_gp_trigger');
        $delay      = (int) self::meta($post->ID, '_gp_delay');
        $scroll     = (int) self::meta($post->ID, '_gp_scroll_percent');
        $selector   = (string) self::meta($post->ID, '_gp_click_selector');
        $position   = (string) self::meta($post->ID, '_gp_position');
        $animation  = (string) self::meta($post->ID, '_gp_animation');
        $overlay    = (int) self::meta($post->ID, '_gp_overlay');
        $frequency  = (int) self::meta($post->ID, '_gp_frequency_days');
        $targetType = (string) self::meta($post->ID, '_gp_target_type');
        $targetIds  = (string) self::meta($post->ID, '_gp_target_ids');

        $triggers = [
            'time'   => __('Time delay', 'gratis-popup'),
            'scroll' => __('Scroll percentage', 'gratis-popup'),
            'exit'   => __('Exit intent', 'gratis-popup'),
            'click'  => __('Element click', 'gratis-popup'),
        ];
        $positions = [
            'center'       => __('Center', 'gratis-popup'),
            'top'          => __('Top bar', 'gratis-popup'),
            'bottom-right' => __('Bottom right (slide-in)', 'gratis-popup'),
            'bottom-left'  => __('Bottom left (slide-in)', 'gratis-popup'),
        ];
        $animations = [
            'fade'  => __('Fade', 'gratis-popup'),
            'slide' => __('Slide', 'gratis-popup'),
            'scale' => __('Scale', 'gratis-popup'),
            'none'  => __('None', 'gratis-popup'),
        ];
        $targets = [
            'all'      => __('Entire site', 'gratis-popup'),
            'front'    => __('Front page only', 'gratis-popup'),
            'posts'    => __('All single posts', 'gratis-popup'),
            'pages'    => __('All pages', 'gratis-popup'),
            'specific' => __('Specific post/page IDs', 'gratis-popup'),
        ];

        echo '<table class="form-table" role="presentation"><tbody>';

        // Trigger.
        echo '<tr><th scope="row"><label for="gp_trigger">' . esc_html__('Trigger', 'gratis-popup') . '</label></th><td>';
        echo '<select name="gp_trigger" id="gp_trigger">';
        foreach ($triggers as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($trigger, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        // Time delay.
        echo '<tr><th scope="row"><label for="gp_delay">' . esc_html__('Delay (seconds)', 'gratis-popup') . '</label></th><td>';
        echo '<input type="number" min="0" name="gp_delay" id="gp_delay" value="' . esc_attr((string) $delay) . '" class="small-text"> ';
        echo '<span class="description">' . esc_html__('Used with the “Time delay” trigger.', 'gratis-popup') . '</span>';
        echo '</td></tr>';

        // Scroll percent.
        echo '<tr><th scope="row"><label for="gp_scroll_percent">' . esc_html__('Scroll depth (%)', 'gratis-popup') . '</label></th><td>';
        echo '<input type="number" min="0" max="100" name="gp_scroll_percent" id="gp_scroll_percent" value="' . esc_attr((string) $scroll) . '" class="small-text"> ';
        echo '<span class="description">' . esc_html__('Used with the “Scroll percentage” trigger.', 'gratis-popup') . '</span>';
        echo '</td></tr>';

        // Click selector.
        echo '<tr><th scope="row"><label for="gp_click_selector">' . esc_html__('Click selector', 'gratis-popup') . '</label></th><td>';
        echo '<input type="text" name="gp_click_selector" id="gp_click_selector" value="' . esc_attr($selector) . '" class="regular-text" placeholder=".open-popup, #signup-btn"> ';
        echo '<p class="description">' . esc_html__('CSS selector that opens the popup. Used with the “Element click” trigger.', 'gratis-popup') . '</p>';
        echo '</td></tr>';

        // Position.
        echo '<tr><th scope="row"><label for="gp_position">' . esc_html__('Position', 'gratis-popup') . '</label></th><td>';
        echo '<select name="gp_position" id="gp_position">';
        foreach ($positions as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($position, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        // Animation.
        echo '<tr><th scope="row"><label for="gp_animation">' . esc_html__('Animation', 'gratis-popup') . '</label></th><td>';
        echo '<select name="gp_animation" id="gp_animation">';
        foreach ($animations as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($animation, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        // Overlay.
        echo '<tr><th scope="row">' . esc_html__('Overlay', 'gratis-popup') . '</th><td>';
        echo '<label><input type="checkbox" name="gp_overlay" value="1"' . checked($overlay, 1, false) . '> ';
        echo esc_html__('Dim the page behind the popup (modal behaviour).', 'gratis-popup') . '</label>';
        echo '</td></tr>';

        // Frequency cap.
        echo '<tr><th scope="row"><label for="gp_frequency_days">' . esc_html__('Frequency cap (days)', 'gratis-popup') . '</label></th><td>';
        echo '<input type="number" min="0" name="gp_frequency_days" id="gp_frequency_days" value="' . esc_attr((string) $frequency) . '" class="small-text"> ';
        echo '<p class="description">' . esc_html__('After a visitor closes the popup, hide it for this many days (cookie based). 0 = show once per session, until closed.', 'gratis-popup') . '</p>';
        echo '</td></tr>';

        // Target type.
        echo '<tr><th scope="row"><label for="gp_target_type">' . esc_html__('Show on', 'gratis-popup') . '</label></th><td>';
        echo '<select name="gp_target_type" id="gp_target_type">';
        foreach ($targets as $value => $label) {
            echo '<option value="' . esc_attr($value) . '"' . selected($targetType, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td></tr>';

        // Target IDs.
        echo '<tr><th scope="row"><label for="gp_target_ids">' . esc_html__('Specific IDs', 'gratis-popup') . '</label></th><td>';
        echo '<input type="text" name="gp_target_ids" id="gp_target_ids" value="' . esc_attr($targetIds) . '" class="regular-text" placeholder="12, 34, 56"> ';
        echo '<p class="description">' . esc_html__('Comma-separated post/page IDs. Used with the “Specific post/page IDs” target.', 'gratis-popup') . '</p>';
        echo '</td></tr>';

        echo '</tbody></table>';
    }

    /**
     * Persist the meta box values.
     *
     * @param int      $post_id Saved post ID.
     * @param \WP_Post $post    Saved post object.
     */
    public static function save(int $post_id, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $nonce = isset($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD]))
            : '';
        if ('' === $nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (PopupPostType::POST_TYPE !== $post->post_type) {
            return;
        }

        $trigger = isset($_POST['gp_trigger'])
            ? sanitize_key(wp_unslash($_POST['gp_trigger']))
            : 'time';
        $trigger = in_array($trigger, ['time', 'scroll', 'exit', 'click'], true) ? $trigger : 'time';

        $position = isset($_POST['gp_position'])
            ? sanitize_key(wp_unslash($_POST['gp_position']))
            : 'center';
        $position = in_array($position, ['center', 'top', 'bottom-right', 'bottom-left'], true) ? $position : 'center';

        $animation = isset($_POST['gp_animation'])
            ? sanitize_key(wp_unslash($_POST['gp_animation']))
            : 'fade';
        $animation = in_array($animation, ['fade', 'slide', 'scale', 'none'], true) ? $animation : 'fade';

        $target_type = isset($_POST['gp_target_type'])
            ? sanitize_key(wp_unslash($_POST['gp_target_type']))
            : 'all';
        $target_type = in_array($target_type, ['all', 'front', 'posts', 'pages', 'specific'], true) ? $target_type : 'all';

        $selector = isset($_POST['gp_click_selector'])
            ? sanitize_text_field(wp_unslash($_POST['gp_click_selector']))
            : '';

        // Normalise the comma-separated ID list to digits only.
        $raw_ids    = isset($_POST['gp_target_ids']) ? sanitize_text_field(wp_unslash($_POST['gp_target_ids'])) : '';
        $target_ids = implode(',', array_filter(array_map('absint', explode(',', $raw_ids))));

        update_post_meta($post_id, '_gp_trigger', $trigger);
        update_post_meta($post_id, '_gp_delay', isset($_POST['gp_delay']) ? absint(wp_unslash($_POST['gp_delay'])) : 5);
        update_post_meta($post_id, '_gp_scroll_percent', isset($_POST['gp_scroll_percent']) ? min(100, absint(wp_unslash($_POST['gp_scroll_percent']))) : 50);
        update_post_meta($post_id, '_gp_click_selector', $selector);
        update_post_meta($post_id, '_gp_position', $position);
        update_post_meta($post_id, '_gp_animation', $animation);
        update_post_meta($post_id, '_gp_overlay', isset($_POST['gp_overlay']) ? 1 : 0);
        update_post_meta($post_id, '_gp_frequency_days', isset($_POST['gp_frequency_days']) ? absint(wp_unslash($_POST['gp_frequency_days'])) : 0);
        update_post_meta($post_id, '_gp_target_type', $target_type);
        update_post_meta($post_id, '_gp_target_ids', $target_ids);
    }
}
