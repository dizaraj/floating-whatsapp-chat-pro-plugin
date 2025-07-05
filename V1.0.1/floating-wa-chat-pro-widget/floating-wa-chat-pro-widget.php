<?php
/*
Plugin Name: Floating WA Chat Pro Widget
Plugin URI: https://dizaraj.github.io/floating-whatsapp-chat-pro-plugin
Description: Add a customizable floating WhatsApp chat widget to your WordPress site. Engage visitors, manage multiple agents, and provide instant support to boost sales.
Version: 1.0.1
Requires at least: 5.0
Requires PHP: 7.2
Author: Dizaraj Dey
Author URI: https://dizaraj.github.io
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: floating-wa-chat-pro-widget
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('WCW_PRO_VERSION', '1.0.1');
define('WCW_PRO_PATH', plugin_dir_path(__FILE__));
define('WCW_PRO_URL', plugin_dir_url(__FILE__));
define('WCW_PRO_LICENSE_SERVER_URL', 'https://wacp-server.netlify.app/api/');

// Include the autoloader or individual files
require_once WCW_PRO_PATH . 'includes/wcw-pro-functions.php';
require_once WCW_PRO_PATH . 'includes/class-wcw-pro-settings.php';
require_once WCW_PRO_PATH . 'includes/class-wcw-pro-admin.php';
require_once WCW_PRO_PATH . 'includes/class-wcw-pro-frontend.php';
require_once WCW_PRO_PATH . 'includes/class-wcw-pro-ajax.php';


/**
 * Main Plugin Class
 */
final class WCW_Pro
{

    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init()
    {
        new WCW_Pro_Admin();
        new WCW_Pro_Frontend();
        new WCW_Pro_Ajax();
    }
}

// Kick it off
WCW_Pro::instance();

/**
 * Activation Hook
 */
function wcw_pro_activate()
{
    // Create asset files upon activation to ensure they exist.
    if (!file_exists(plugin_dir_path(__FILE__) . 'assets/css/style.css')) {
        wcw_pro_create_asset_files();
    }

    // Set default options if they don't exist.
    if (false === get_option('wcw_pro_settings')) {
        $defaults = [
            'popup_title' => esc_html__('Start a Conversation', 'floating-wa-chat-pro-widget'),
            'button_position' => 'bottom_right',
            'bubble_color' => '#25D366',
            'icon_color' => '#FFFFFF',
            'button_style' => 'icon_only',
            'button_text' => esc_html__('Need Help!', 'floating-wa-chat-pro-widget'),
            'api_key' => '',
            'api_key_status' => 'invalid',
            'api_key_expires' => 0,
            'agents' => [['name' => 'Support Team', 'title' => 'Customer Support', 'department' => 'Sales', 'phone' => '', 'message' => 'Hello! I have a question.', 'image_id' => 0]]
        ];
        add_option('wcw_pro_settings', $defaults);
    }
}
register_activation_hook(__FILE__, 'wcw_pro_activate');

/**
 * Create asset files if they are missing.
 */
function wcw_pro_create_asset_files_if_missing()
{
    if (!file_exists(plugin_dir_path(__FILE__) . 'assets/css/admin.css')) {
        wcw_pro_create_asset_files();
    }
}
add_action('admin_init', 'wcw_pro_create_asset_files_if_missing');
