<?php declare(strict_types=1);
namespace Gratis\Popup;

final class PopupPostType
{
    public static function register(): void
    {
        register_post_type('gratis_popup', [
            'labels'       => ['name' => 'Popups', 'singular_name' => 'Popup'],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-megaphone',
            'supports'     => ['title', 'editor', 'custom-fields'],
        ]);
    }

    public static function renderActive(): void
    {
        if (is_admin() || is_user_logged_in()) return;

        $popups = get_posts([
            'post_type'   => 'gratis_popup',
            'post_status' => 'publish',
            'numberposts' => 5,
        ]);

        if (empty($popups)) return;

        foreach ($popups as $popup) {
            $trigger = get_post_meta($popup->ID, '_trigger', true) ?: 'time';
            $delay = (int) (get_post_meta($popup->ID, '_delay', true) ?: 5);
            $scroll = (int) (get_post_meta($popup->ID, '_scroll_percent', true) ?: 50);
            $position = get_post_meta($popup->ID, '_position', true) ?: 'center';

            $posClass = match ($position) {
                'bottom-right' => 'gratis-popup--br',
                'bottom-left'  => 'gratis-popup--bl',
                'top'          => 'gratis-popup--top',
                default        => 'gratis-popup--center',
            };

            echo '<div class="gratis-popup ' . $posClass . '" id="gp-' . $popup->ID . '" data-trigger="' . esc_attr($trigger) . '" data-delay="' . $delay . '" data-scroll="' . $scroll . '" style="display:none">';
            echo '<button class="gratis-popup-close" onclick="this.parentElement.style.display=\'none\';localStorage.setItem(\'gp_closed_' . $popup->ID . '\',1)">&times;</button>';
            echo '<div class="gratis-popup-content">' . apply_filters('the_content', $popup->post_content) . '</div>';
            echo '</div>';
        }

        echo '<style>' . self::css() . '</style>';
        echo '<script>' . self::js() . '</script>';
    }

    private static function css(): string
    {
        return '.gratis-popup{position:fixed;z-index:99998;background:#1a1e2e;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:2rem;max-width:480px;width:90%;box-shadow:0 25px 50px rgba(0,0,0,0.5);color:#fff}.gratis-popup--center{top:50%;left:50%;transform:translate(-50%,-50%)}.gratis-popup--br{bottom:20px;right:20px;transform:none}.gratis-popup--bl{bottom:20px;left:20px;transform:none}.gratis-popup--top{top:0;left:0;right:0;max-width:100%;border-radius:0}.gratis-popup-close{position:absolute;top:10px;right:14px;background:none;border:none;color:rgba(255,255,255,0.5);font-size:24px;cursor:pointer;line-height:1}.gratis-popup-close:hover{color:#fff}';
    }

    private static function js(): string
    {
        return '!function(){document.querySelectorAll(".gratis-popup").forEach(function(p){var id=p.id;if(localStorage.getItem("gp_closed_"+id.replace("gp-","")))return;var t=p.dataset.trigger,d=parseInt(p.dataset.delay)*1000,s=parseInt(p.dataset.scroll);function show(){p.style.display="block"}if(t==="time")setTimeout(show,d);else if(t==="scroll")window.addEventListener("scroll",function once(){if(window.scrollY/(document.body.scrollHeight-window.innerHeight)*100>=s){show();window.removeEventListener("scroll",once)}});else if(t==="exit")document.addEventListener("mouseleave",function once(e){if(e.clientY<0){show();document.removeEventListener("mouseleave",once)}})})}()';
    }
}
