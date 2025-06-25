<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class WCW_Pro_Admin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_head', array($this, 'admin_menu_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            esc_html__('WhatsApp Chat Settings', 'wcw-pro'),
            esc_html__('WhatsApp Chat', 'wcw-pro'),
            'manage_options',
            'wcw-pro',
            array($this, 'settings_page_html'),
            'dashicons-format-chat',
            100
        );

        add_submenu_page(
            'wcw-pro',
            esc_html__('WhatsApp Chat Settings', 'wcw-pro'),
            esc_html__('Settings', 'wcw-pro'),
            'manage_options',
            'wcw-pro',
            array($this, 'settings_page_html')
        );

        add_submenu_page(
            'wcw-pro',
            esc_html__('Activate Pro License', 'wcw-pro'),
            esc_html__('Activate Pro', 'wcw-pro'),
            'manage_options',
            'wcw-pro-activate',
            array($this, 'activate_page_html')
        );

        add_submenu_page(
            'wcw-pro',
            esc_html__('About WhatsApp Chat Pro', 'wcw-pro'),
            esc_html__('About', 'wcw-pro'),
            'manage_options',
            'wcw-pro-about',
            array($this, 'about_page_html')
        );
    }

    public function admin_menu_styles()
    {
?>
        <style>
            a[href="admin.php?page=wcw-pro-activate"] {
                background-color: #4CAF50 !important;
                color: #FFFFFF !important;
                font-weight: 600 !important;
                margin: 5px 0 !important;
            }

            a[href="admin.php?page=wcw-pro-activate"]:hover {
                background-color: #45a049 !important;
                color: #FFFFFF !important;
            }
        </style>
    <?php
    }

    public function settings_page_html()
    {
        if (!current_user_can('manage_options'))
            return;
    ?>
        <div class="wrap wcw-pro-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="wcw-pro-settings-content">
                <div class="wcw-pro-settings-main">
                    <nav class="nav-tab-wrapper wcw-pro-nav-tab-wrapper">
                        <a href="#general" class="nav-tab nav-tab-active">General</a>
                        <a href="#display" class="nav-tab">Display & Styling</a>
                        <a href="#agents" class="nav-tab">Agents</a>
                    </nav>
                    <form action="options.php" method="post">
                        <?php settings_fields('wcw_pro_options_group'); ?>
                        <div id="general" class="wcw-pro-tab-content active">
                            <?php do_settings_sections('wcw_pro_settings_general'); ?>
                        </div>
                        <div id="display" class="wcw-pro-tab-content">
                            <?php do_settings_sections('wcw_pro_settings_display'); ?>
                        </div>
                        <div id="agents" class="wcw-pro-tab-content">
                            <?php do_settings_sections('wcw_pro_settings_agents'); ?>
                        </div>
                        <?php submit_button('Save Settings'); ?>
                    </form>
                </div>
                <div class="wcw-pro-settings-sidebar">
                    <div class="wcw-pro-sidebar-box">
                        <h3><?php esc_html_e('Live Preview', 'wcw-pro'); ?></h3>
                        <div id="wcw-pro-preview-container">
                            <div id="wcw-pro-preview-bubble">
                                <svg id="wcw-pro-preview-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="30" height="30">
                                    <path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.8 0-67.3-10.2-96.1-29.1l-6.7-4-71.6 18.7 19.3-68.6-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.8-16.2-54.3-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.9 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" />
                                </svg>
                                <span id="wcw-pro-preview-text"></span>
                            </div>
                        </div>
                    </div>
                    <div class="wcw-pro-sidebar-box">
                        <h3><span class="dashicons dashicons-info-outline"></span>
                            <?php esc_html_e('Essential Info', 'wcw-pro'); ?></h3>
                        <div class="wcw-pro-essential-info">
                            <?php $info = wcw_pro_get_essential_info(); ?>
                            <ul>
                                <li><strong><?php esc_html_e('Domain:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['domain']); ?></span></li>
                                <li><strong><?php esc_html_e('Server IP:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['server_ip']); ?></span></li>
                                <li><strong><?php esc_html_e('Your Public IP:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['your_public_ip']); ?></span></li>
                                <li><strong><?php esc_html_e('PHP Version:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['php_version']); ?></span></li>
                                <li><strong><?php esc_html_e('WordPress Version:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['wp_version']); ?></span></li>
                                <li><strong><?php esc_html_e('Memory Limit:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['memory_limit']); ?></span></li>
                                <li><strong><?php esc_html_e('Memory Usage:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['memory_usage']); ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function activate_page_html()
    {
        if (!current_user_can('manage_options'))
            return;
    ?>
        <div class="wrap wcw-pro-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors(); ?>
            <div class="wcw-pro-settings-content">
                <div class="wcw-pro-settings-main">
                    <form action="options.php" method="post">
                        <?php settings_fields('wcw_pro_options_group'); ?>
                        <div class="wcw-pro-settings-card">
                            <div class="wcw-pro-card-header">
                                <h3><span class="dashicons dashicons-awards"></span> License & Activation</h3>
                            </div>
                            <div class="wcw-pro-card-body">
                                <table class="form-table">
                                    <?php do_settings_sections('wcw_pro_settings_license'); ?>
                                </table>
                            </div>
                        </div>
                        <?php submit_button('Save & Activate'); ?>
                    </form>
                </div>
                <div class="wcw-pro-settings-sidebar">
                    <div class="wcw-pro-sidebar-box">
                        <h3><span class="dashicons dashicons-star-filled"></span> Benefits of Activating Pro</h3>
                        <div class="wcw-pro-benefits-list">
                            <ul>
                                <li><span class="dashicons dashicons-groups"></span> Add multiple support agents.</li>
                                <li><span class="dashicons dashicons-id"></span> Assign custom avatars to agents.</li>
                                <li><span class="dashicons dashicons-tag"></span> Organize agents by department.</li>
                                <li><span class="dashicons dashicons-email-alt"></span> Receive priority email support.</li>
                                <li><span class="dashicons dashicons-update"></span> Get continuous updates & new features.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function about_page_html()
    {
        if (!current_user_can('manage_options'))
            return;
    ?>
        <div class="wrap wcw-pro-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="wcw-pro-settings-content">
                <div class="wcw-pro-settings-main">
                    <div class="wcw-pro-settings-card">
                        <div class="wcw-pro-card-header">
                            <h3><span class="dashicons dashicons-book-alt"></span> How to Use WhatsApp Chat Pro</h3>
                        </div>
                        <div class="wcw-pro-card-body">
                            <h4>Initial Setup:</h4>
                            <ol>
                                <li>Go to <strong>WhatsApp Chat > Settings</strong>.</li>
                                <li>Under the <strong>General</strong> tab, configure the popup title and button position.</li>
                                <li>Under the <strong>Display & Styling</strong> tab, customize the colors and button style to
                                    match your brand.</li>
                                <li>Under the <strong>Agents</strong> tab, add at least one support agent. You must provide a
                                    name and a valid WhatsApp number (including country code).</li>
                                <li>Click <strong>Save Settings</strong>.</li>
                            </ol>
                            <h4>Activating Pro Features:</h4>
                            <ol>
                                <li>Navigate to <strong>WhatsApp Chat > Activate Pro</strong>.</li>
                                <li>You can request a 7-day trial key by entering your email.</li>
                                <li>Once you have a license key (either trial or full), enter it in the 'License Key' field and
                                    click <strong>Save & Activate</strong>.</li>
                                <li>With a Pro license, you can add multiple agents, upload custom avatars, and assign
                                    departments.</li>
                            </ol>
                        </div>
                    </div>

                    <div class="wcw-pro-settings-card">
                        <div class="wcw-pro-card-header">
                            <h3><span class="dashicons dashicons-businessman"></span> About the Developer</h3>
                        </div>
                        <div class="wcw-pro-card-body">
                            <p>This plugin is developed and maintained by <strong>Dizaraj Dey</strong>, a passionate WordPress
                                developer dedicated to creating high-quality, user-friendly plugins.</p>
                            <h4>Contact Details:</h4>
                            <p>For support, inquiries, or custom development, please feel free to reach out. Your feedback is
                                always welcome!</p>
                            <ul class="wcw-pro-contact-list">
                                <li><strong>Email:</strong> <a href="mailto:dizaraj@gmail.com">dizaraj@gmail.com</a></li>
                                <li><strong>Messenger:</strong> <a href="https://m.me/dizaraj" target="_blank">m.me/dizaraj</a>
                                </li>
                                <li><strong>WhatsApp:</strong> <a href="https://wa.me/8801717035081" target="_blank">+8801717035081</a></li>
                            </ul>
                        </div>
                    </div>

                </div>
                <div class="wcw-pro-settings-sidebar">
                    <div class="wcw-pro-sidebar-box">
                        <h3><span class="dashicons dashicons-info-outline"></span>
                            <?php esc_html_e('Essential Info', 'wcw-pro'); ?></h3>
                        <div class="wcw-pro-essential-info">
                            <?php $info = wcw_pro_get_essential_info(); ?>
                            <ul>
                                <li><strong><?php esc_html_e('Domain:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['domain']); ?></span></li>
                                <li><strong><?php esc_html_e('Server IP:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['server_ip']); ?></span></li>
                                <li><strong><?php esc_html_e('Your Public IP:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['your_public_ip']); ?></span></li>
                                <li><strong><?php esc_html_e('PHP Version:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['php_version']); ?></span></li>
                                <li><strong><?php esc_html_e('WordPress Version:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['wp_version']); ?></span></li>
                                <li><strong><?php esc_html_e('Memory Limit:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['memory_limit']); ?></span></li>
                                <li><strong><?php esc_html_e('Memory Usage:', 'wcw-pro'); ?></strong>
                                    <span><?php echo esc_html($info['memory_usage']); ?></span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function enqueue_admin_scripts($hook)
    {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->base, 'wcw-pro') === false) {
            return;
        }

        wp_enqueue_media();

        $options = get_option('wcw_pro_settings', []);

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('wcw-pro-admin-style', WCW_PRO_URL . 'assets/css/admin.css', array(), WCW_PRO_VERSION);
        wp_enqueue_script('wcw-pro-admin-script', WCW_PRO_URL . 'assets/js/admin.js', array('jquery', 'wp-util', 'wp-color-picker', 'jquery-ui-accordion'), WCW_PRO_VERSION, true);
        wp_localize_script('wcw-pro-admin-script', 'wcwProData', [
            'is_pro' => wcw_pro_is_pro(),
            'agent_count' => count($options['agents'] ?? []),
            'nonce' => wp_create_nonce('wcw_pro_trial_nonce'),
            'settings' => [
                'button_style' => $options['button_style'] ?? 'icon_only',
                'button_text' => $options['button_text'] ?? esc_html__('Need Help!', 'wcw-pro'),
            ],
        ]);
    }
}

