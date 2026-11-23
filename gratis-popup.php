<?php
/**
 * Plugin Name: Gratis Popup
 * Plugin URI: https://github.com/juslintek/gratis-popup
 * Description: Conversion popups + slide-ins. Exit intent, timed, scroll-triggered, and click triggers with page targeting, cookie frequency caps, and an accessible modal.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-popup
 */

defined('ABSPATH') || exit;

define('GRATIS_POPUP_VERSION', '1.0.0');
define('GRATIS_POPUP_FILE', __FILE__);
define('GRATIS_POPUP_DIR', plugin_dir_path(__FILE__));
define('GRATIS_POPUP_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Popup\PopupPostType::class, 'register']);
add_action('add_meta_boxes', [\Gratis\Popup\MetaBox::class, 'register']);
add_action('save_post_gratis_popup', [\Gratis\Popup\MetaBox::class, 'save'], 10, 2);

add_action('wp_enqueue_scripts', [\Gratis\Popup\Renderer::class, 'enqueue']);
add_action('wp_footer', [\Gratis\Popup\Renderer::class, 'render']);

add_action('admin_menu', [\Gratis\Popup\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Popup\Settings::class, 'register_settings']);

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-popup', \Gratis\Popup\CLI\PopupCommand::class);
    }
});
