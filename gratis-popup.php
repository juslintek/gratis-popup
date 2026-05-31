<?php
/**
 * Plugin Name: Gratis Popup
 * Description: Conversion popups + slide-ins. Exit intent, timed, scroll-triggered. Replaces OptinMonster ($108/yr).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 * Text Domain: gratis-popup
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Popup\PopupPostType::class, 'register']);
add_action('wp_footer', [\Gratis\Popup\PopupPostType::class, 'renderActive']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-popup', \Gratis\Popup\CLI\PopupCommand::class);
    }
});
