<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class WCW_Pro_Frontend
{

    public function __construct()
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_head', array($this, 'output_dynamic_styles'));
        add_action('wp_footer', array($this, 'render_frontend_widget'));
    }

    public function enqueue_assets()
    {
        wp_enqueue_style('wcw-pro-style', WCW_PRO_URL . 'assets/css/style.css', array(), WCW_PRO_VERSION);
        wp_enqueue_script('wcw-pro-script', WCW_PRO_URL . 'assets/js/main.js', array('jquery'), WCW_PRO_VERSION, true);
    }

    public function output_dynamic_styles()
    {
        $options = get_option('wcw_pro_settings', []);
        $bubble_color = $options['bubble_color'] ?? '#25D366';
        $icon_color = $options['icon_color'] ?? '#FFFFFF';
        $position = $options['button_position'] ?? 'bottom_right';

        $css = '<style type="text/css">';

        if ($position === 'bottom_left') {
            $css .= '.wcw-pro-container { left: 25px; right: auto; }';
            $css .= '.wcw-pro-popup { left: 0; right: auto; transform-origin: bottom left; }';
        } else {
            $css .= '.wcw-pro-container { right: 25px; left: auto; }';
            $css .= '.wcw-pro-popup { right: 0; left: auto; transform-origin: bottom right; }';
        }

        $css .= '.wcw-pro-bubble { background-color: ' . esc_attr($bubble_color) . '; }';
        $css .= '.wcw-pro-bubble .wcw-pro-icon path { fill: ' . esc_attr($icon_color) . '; }';
        $css .= '.wcw-pro-bubble-text { color: ' . esc_attr($icon_color) . '; }';
        $css .= '.wcw-pro-header { background-color: ' . esc_attr($bubble_color) . '; color: ' . esc_attr($icon_color) . '; }';
        $css .= '.wcw-pro-header .wcw-pro-close { color: ' . esc_attr($icon_color) . '; }';
        $css .= '</style>';

        echo $css;
    }

    public function render_frontend_widget()
    {
        $options = get_option('wcw_pro_settings', []);
        $title = $options['popup_title'] ?? esc_html__('Start a Conversation', 'floating-wa-chat-pro-widget');
        $agents = $options['agents'] ?? [];
        $button_style = $options['button_style'] ?? 'icon_only';
        $button_text = $options['button_text'] ?? esc_html__('Need Help!', 'floating-wa-chat-pro-widget');
        $is_pro = wcw_pro_is_pro();

        if (empty($agents)) {
            return;
        }

        if (!$is_pro) {
            $agents = array_slice($agents, 0, 1);
        }

        $bubble_classes = 'wcw-pro-bubble';
        if ($button_style === 'icon_text') {
            $bubble_classes .= ' wcw-pro-bubble-with-text';
        }
        ?>
        <div class="wcw-pro-container">
            <div class="<?php echo esc_attr($bubble_classes); ?>">
                <svg class="wcw-pro-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="30" height="30">
                    <path
                        d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.8 0-67.3-10.2-96.1-29.1l-6.7-4-71.6 18.7 19.3-68.6-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.8-16.2-54.3-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.9 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                </svg>
                <?php if ($button_style === 'icon_text'): ?>
                    <span class="wcw-pro-bubble-text"><?php echo esc_html($button_text); ?></span>
                <?php endif; ?>
            </div>
            <div class="wcw-pro-popup">
                <div class="wcw-pro-header">
                    <span class="wcw-pro-title"><?php echo esc_html($title); ?></span>
                    <span class="wcw-pro-close">&times;</span>
                </div>
                <div class="wcw-pro-body">
                    <?php foreach ($agents as $agent): ?>
                        <?php
                        $agent_name = $agent['name'] ?? '';
                        $agent_title = $agent['title'] ?? '';
                        $agent_department = ($is_pro && isset($agent['department'])) ? $agent['department'] : '';
                        $agent_phone = $agent['phone'] ?? '';
                        $agent_message = $agent['message'] ?? '';
                        $image_id = ($is_pro && isset($agent['image_id'])) ? $agent['image_id'] : 0;
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, [50, 50]) : 'https://placehold.co/50x50/EFEFEF/AAAAAA&text=Avatar';
                        $phone_number = preg_replace('/\D/', '', $agent_phone);
                        $prefilled_message = !empty($agent_message) ? $agent_message : sprintf(esc_html__('Hello %s!', 'floating-wa-chat-pro-widget'), $agent_name);
                        $whatsapp_url = 'https://wa.me/' . esc_attr($phone_number) . '?text=' . urlencode($prefilled_message);
                        $display_title = $agent_title;
                        if (!empty($agent_department))
                            $display_title .= ' | ' . $agent_department;
                        ?>
                        <a href="<?php echo esc_url($whatsapp_url); ?>" target="_blank" rel="noopener noreferrer"
                            class="wcw-pro-agent">
                            <div class="wcw-pro-agent-avatar">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($agent_name); ?>" width="50"
                                    height="50">
                            </div>
                            <div class="wcw-pro-agent-details">
                                <span class="wcw-pro-agent-name"><?php echo esc_html($agent_name); ?></span>
                                <span class="wcw-pro-agent-title"><?php echo esc_html($display_title); ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
