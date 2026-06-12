<?php declare(strict_types=1);

namespace Gratis\Popup\CLI;

use Gratis\Popup\PopupPostType;

defined('ABSPATH') || exit;

final class PopupCommand {

    /**
     * List popups.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Associative args (unused).
     */
    public function list($args, $assoc_args): void {
        unset($args, $assoc_args);

        $popups = get_posts([
            'post_type'   => PopupPostType::POST_TYPE,
            'numberposts' => -1,
            'post_status' => 'any',
        ]);

        if (empty($popups)) {
            \WP_CLI::log('No popups.');
            return;
        }

        $rows = array_map(
            static fn($p) => [
                'ID'      => $p->ID,
                'Title'   => $p->post_title,
                'Status'  => $p->post_status,
                'Trigger' => get_post_meta($p->ID, '_gp_trigger', true) ?: 'time',
                'Show on' => get_post_meta($p->ID, '_gp_target_type', true) ?: 'all',
            ],
            $popups
        );

        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Title', 'Status', 'Trigger', 'Show on']);
    }

    /**
     * Create a popup.
     *
     * ## OPTIONS
     *
     * <title>
     * : Popup title.
     *
     * [--trigger=<trigger>]
     * : time, scroll, exit, or click.
     *
     * [--delay=<seconds>]
     * : Delay in seconds (for the time trigger).
     *
     * [--content=<html>]
     * : Popup content.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args.
     * @param array<string,string> $assoc_args Associative args.
     */
    public function create($args, $assoc_args): void {
        $trigger = $assoc_args['trigger'] ?? 'time';
        $trigger = in_array($trigger, ['time', 'scroll', 'exit', 'click'], true) ? $trigger : 'time';

        $id = wp_insert_post([
            'post_type'    => PopupPostType::POST_TYPE,
            'post_title'   => $args[0],
            'post_content' => $assoc_args['content'] ?? '<p>Subscribe to our newsletter!</p>',
            'post_status'  => 'publish',
            'meta_input'   => [
                '_gp_trigger'        => $trigger,
                '_gp_delay'          => isset($assoc_args['delay']) ? absint($assoc_args['delay']) : 5,
                '_gp_position'       => 'center',
                '_gp_animation'      => 'fade',
                '_gp_overlay'        => 1,
                '_gp_scroll_percent' => 50,
                '_gp_frequency_days' => 0,
                '_gp_target_type'    => 'all',
            ],
        ]);

        if (is_wp_error($id)) {
            \WP_CLI::error($id->get_error_message());
            return;
        }

        \WP_CLI::success("Popup #{$id} created.");
    }
}
