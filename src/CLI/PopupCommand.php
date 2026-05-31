<?php declare(strict_types=1);
namespace Gratis\Popup\CLI;

final class PopupCommand
{
    /**
     * List popups.
     * @when after_wp_load
     */
    public function list($args, $assoc_args): void
    {
        $popups = get_posts(['post_type' => 'gratis_popup', 'numberposts' => -1, 'post_status' => 'any']);
        if (empty($popups)) { \WP_CLI::log("No popups."); return; }
        $rows = array_map(fn($p) => [
            'ID' => $p->ID, 'Title' => $p->post_title, 'Status' => $p->post_status,
            'Trigger' => get_post_meta($p->ID, '_trigger', true) ?: 'time',
        ], $popups);
        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Title', 'Status', 'Trigger']);
    }

    /**
     * Create a popup.
     * <title> : Popup title.
     * [--trigger=<trigger>] : time, scroll, or exit.
     * [--delay=<seconds>] : Delay in seconds (for time trigger).
     * [--content=<html>] : Popup content.
     * @when after_wp_load
     */
    public function create($args, $assoc_args): void
    {
        $id = wp_insert_post([
            'post_type'    => 'gratis_popup',
            'post_title'   => $args[0],
            'post_content' => $assoc_args['content'] ?? '<p>Subscribe to our newsletter!</p>',
            'post_status'  => 'publish',
            'meta_input'   => [
                '_trigger'        => $assoc_args['trigger'] ?? 'time',
                '_delay'          => $assoc_args['delay'] ?? 5,
                '_position'       => 'center',
                '_scroll_percent' => 50,
            ],
        ]);
        \WP_CLI::success("Popup #{$id} created.");
    }
}
