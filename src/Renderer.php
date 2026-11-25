<?php declare(strict_types=1);

namespace Gratis\Popup;

defined('ABSPATH') || exit;

/**
 * Front-end renderer: decides which popups apply to the current request,
 * enqueues the (accessible) modal assets, and prints the markup in the footer.
 *
 * Display gating that depends on per-visitor state (the cookie frequency cap)
 * is handled client-side in popup.js so the markup stays cache-friendly.
 */
final class Renderer {

    /** @var array<int,\WP_Post>|null Cached list of popups for this request. */
    private static ?array $active = null;

    /**
     * Whether popups should run at all on the current request, honouring the
     * global master switch and the "show to logged-in users" setting.
     */
    private static function globallyEnabled(): bool {
        if (is_admin()) {
            return false;
        }
        if (!Settings::get_bool('enabled', true)) {
            return false;
        }
        if (is_user_logged_in() && !Settings::get_bool('show_logged_in', false)) {
            return false;
        }
        return true;
    }

    /**
     * Resolve the published popups that target the current request.
     *
     * @return array<int,\WP_Post>
     */
    private static function activePopups(): array {
        if (null !== self::$active) {
            return self::$active;
        }

        if (!self::globallyEnabled()) {
            self::$active = [];
            return self::$active;
        }

        $popups = get_posts([
            'post_type'        => PopupPostType::POST_TYPE,
            'post_status'      => 'publish',
            'numberposts'      => 20,
            'suppress_filters' => false,
        ]);

        self::$active = array_values(array_filter(
            $popups,
            static fn(\WP_Post $popup): bool => self::matchesTarget($popup->ID)
        ));

        return self::$active;
    }

    /**
     * Evaluate a popup's page-targeting rule against the current query.
     */
    private static function matchesTarget(int $popup_id): bool {
        $type = (string) get_post_meta($popup_id, '_gp_target_type', true);
        $type = '' !== $type ? $type : 'all';

        switch ($type) {
            case 'front':
                return is_front_page();
            case 'posts':
                return is_singular('post');
            case 'pages':
                return is_page();
            case 'specific':
                $ids = array_filter(array_map('absint', explode(',', (string) get_post_meta($popup_id, '_gp_target_ids', true))));
                $current = (int) get_queried_object_id();
                return $current > 0 && in_array($current, $ids, true);
            case 'all':
            default:
                return true;
        }
    }

    /**
     * Register and enqueue the front-end assets when popups are present.
     */
    public static function enqueue(): void {
        if (empty(self::activePopups())) {
            return;
        }

        wp_enqueue_style(
            'gratis-popup',
            GRATIS_POPUP_URL . 'assets/popup.css',
            [],
            GRATIS_POPUP_VERSION
        );

        wp_enqueue_script(
            'gratis-popup',
            GRATIS_POPUP_URL . 'assets/popup.js',
            [],
            GRATIS_POPUP_VERSION,
            true
        );
    }

    /**
     * Print popup markup in the footer.
     */
    public static function render(): void {
        $popups = self::activePopups();
        if (empty($popups)) {
            return;
        }

        foreach ($popups as $popup) {
            self::renderOne($popup);
        }
    }

    /**
     * Render a single accessible popup dialog.
     */
    private static function renderOne(\WP_Post $popup): void {
        $id        = (int) $popup->ID;
        $trigger   = (string) (get_post_meta($id, '_gp_trigger', true) ?: 'time');
        $delay     = (int) (get_post_meta($id, '_gp_delay', true) ?: 0);
        $scroll    = (int) (get_post_meta($id, '_gp_scroll_percent', true) ?: 0);
        $selector  = (string) get_post_meta($id, '_gp_click_selector', true);
        $position  = (string) (get_post_meta($id, '_gp_position', true) ?: 'center');
        $animation = (string) (get_post_meta($id, '_gp_animation', true) ?: 'fade');
        $overlay   = (int) get_post_meta($id, '_gp_overlay', true);

        $frequency = get_post_meta($id, '_gp_frequency_days', true);
        $frequency = '' === $frequency ? Settings::get_int('default_frequency', 0) : (int) $frequency;

        $position_classes = [
            'center'       => 'gratis-popup--center',
            'top'          => 'gratis-popup--top',
            'bottom-right' => 'gratis-popup--br',
            'bottom-left'  => 'gratis-popup--bl',
        ];
        $pos_class = $position_classes[$position] ?? 'gratis-popup--center';

        $title_id    = 'gratis-popup-title-' . $id;
        $has_title   = '' !== trim((string) $popup->post_title);
        $content     = do_shortcode(wpautop(wptexturize(do_blocks((string) $popup->post_content))));
        $close_label = __('Close dialog', 'gratis-popup');

        $wrapper_classes = 'gratis-popup-root';
        if ($overlay) {
            $wrapper_classes .= ' gratis-popup-root--overlay';
        }

        printf(
            '<div class="%1$s" id="gratis-popup-%2$d" data-popup-id="%2$d" data-trigger="%3$s" data-delay="%4$d" data-scroll="%5$d" data-selector="%6$s" data-frequency="%7$d" data-animation="%8$s" data-overlay="%9$d" hidden>',
            esc_attr($wrapper_classes),
            absint($id),
            esc_attr($trigger),
            absint($delay),
            absint($scroll),
            esc_attr($selector),
            absint($frequency),
            esc_attr($animation),
            absint($overlay)
        );

        printf(
            '<div class="gratis-popup %1$s gratis-popup--anim-%2$s" role="dialog" aria-modal="%3$s"%4$s tabindex="-1">',
            esc_attr($pos_class),
            esc_attr($animation),
            $overlay ? 'true' : 'false',
            $has_title ? ' aria-labelledby="' . esc_attr($title_id) . '"' : ' aria-label="' . esc_attr__('Promotion', 'gratis-popup') . '"'
        );

        printf(
            '<button type="button" class="gratis-popup-close" aria-label="%1$s" data-popup-close>&times;</button>',
            esc_attr($close_label)
        );

        if ($has_title) {
            printf(
                '<h2 class="gratis-popup-title" id="%1$s">%2$s</h2>',
                esc_attr($title_id),
                esc_html($popup->post_title)
            );
        }

        echo '<div class="gratis-popup-content">' . wp_kses_post($content) . '</div>';

        echo '</div>'; // .gratis-popup
        echo '</div>'; // .gratis-popup-root
    }
}
