<?php
/*
Plugin Name: WhatsApp Chat Pro
Plugin URI: dizaraj.github.io/wcw-pro
Description: Adds a customizable floating WhatsApp chat widget and a "Chat on WhatsApp" button to WooCommerce product pages. Manage multiple agents easily.
Version: 2.6.3
Author: Dizaraj Dey
Author URI: dizaraj.github.io
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Text Domain: wcw-pro
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =============================================================================
// Helper Functions
// =============================================================================

/**
 * Checks if the pro version is active.
 * @return bool
 * @since 2.5.0
 */
function wcw_pro_is_pro() {
    $options = get_option('wcw_pro_settings');
    $status = $options['api_key_status'] ?? 'invalid';
    
    if ($status === 'valid') {
        return true;
    }

    if ($status === 'valid_trial') {
        $expires = $options['api_key_expires'] ?? 0;
        return time() < $expires;
    }

    return false;
}

// =============================================================================
// ADMIN MENU & SETTINGS PAGE
// =============================================================================

/**
 * Add the admin menu page.
 * @since 2.0.0
 */
function wcw_pro_add_admin_menu() {
    add_menu_page(
        esc_html__( 'WhatsApp Chat Pro Settings', 'wcw-pro' ),
        esc_html__( 'WhatsApp Chat', 'wcw-pro' ),
        'manage_options',
        'wcw-pro-settings',
        'wcw_pro_settings_page_html',
        'dashicons-format-chat',
        100
    );
}
add_action( 'admin_menu', 'wcw_pro_add_admin_menu' );

/**
 * Register the plugin settings.
 * @since 2.5.0
 */
function wcw_pro_settings_init() {
    register_setting( 'wcw_pro_options_group', 'wcw_pro_settings', 'wcw_pro_sanitize_settings' );

    // General Section
    add_settings_section('wcw_pro_general_section', null, null, 'wcw_pro_settings_general');
    add_settings_field('wcw_pro_popup_title_field', esc_html__('Popup Title', 'wcw-pro'), 'wcw_pro_render_field', 'wcw_pro_settings_general', 'wcw_pro_general_section', ['type' => 'text', 'id' => 'popup_title', 'description' => 'The title displayed at the top of the chat popup.']);

    // Display Section
    add_settings_section('wcw_pro_display_section', null, null, 'wcw_pro_settings_display');
    add_settings_field('wcw_pro_bubble_color_field', esc_html__('Bubble Background Color', 'wcw-pro'), 'wcw_pro_render_field', 'wcw_pro_settings_display', 'wcw_pro_display_section', ['type' => 'color', 'id' => 'bubble_color', 'default' => '#25D366']);
    add_settings_field('wcw_pro_icon_color_field', esc_html__('Bubble Icon & Text Color', 'wcw-pro'), 'wcw_pro_render_field', 'wcw_pro_settings_display', 'wcw_pro_display_section', ['type' => 'color', 'id' => 'icon_color', 'default' => '#FFFFFF']);
    add_settings_field('wcw_pro_button_style_field', esc_html__('Button Style', 'wcw-pro'), 'wcw_pro_button_style_field_callback', 'wcw_pro_settings_display', 'wcw_pro_display_section');
    add_settings_field('wcw_pro_button_text_field', esc_html__('Button Text', 'wcw-pro'), 'wcw_pro_render_field', 'wcw_pro_settings_display', 'wcw_pro_display_section', ['type' => 'text', 'id' => 'button_text', 'description' => 'The text displayed next to the icon.']);

    // Agents Section
    add_settings_section('wcw_pro_agents_section', null, null, 'wcw_pro_settings_agents');
    add_settings_field('wcw_pro_agents_field', null, 'wcw_pro_agents_field_callback', 'wcw_pro_settings_agents', 'wcw_pro_agents_section');

    // License Section
    add_settings_section('wcw_pro_license_section', null, null, 'wcw_pro_settings_license');
    add_settings_field('wcw_pro_api_key_status_field', esc_html__('License Status', 'wcw-pro'), 'wcw_pro_api_key_status_field_callback', 'wcw_pro_settings_license', 'wcw_pro_license_section');
    add_settings_field('wcw_pro_api_key_field', esc_html__('API License Key', 'wcw-pro'), 'wcw_pro_render_field', 'wcw_pro_settings_license', 'wcw_pro_license_section', ['type' => 'text', 'id' => 'api_key', 'description' => 'Enter your API key to unlock Pro features.']);
    add_settings_field('wcw_pro_trial_request_field', esc_html__('Request a Trial Key', 'wcw-pro'), 'wcw_pro_trial_request_field_callback', 'wcw_pro_settings_license', 'wcw_pro_license_section');
}
add_action( 'admin_init', 'wcw_pro_settings_init' );

/**
 * Sanitize settings on save.
 * @since 2.5.0
 */
function wcw_pro_sanitize_settings( $input ) {
    $new_input = [];
    $options = get_option('wcw_pro_settings', []);
    
    $new_input['api_key_status'] = $options['api_key_status'] ?? 'invalid';
    $new_input['api_key_expires'] = $options['api_key_expires'] ?? 0;
    
    $new_input['popup_title'] = isset( $input['popup_title'] ) ? sanitize_text_field( $input['popup_title'] ) : '';
    $new_input['bubble_color'] = isset( $input['bubble_color'] ) ? sanitize_hex_color( $input['bubble_color'] ) : '#25D366';
    $new_input['icon_color'] = isset( $input['icon_color'] ) ? sanitize_hex_color( $input['icon_color'] ) : '#FFFFFF';
    $new_input['button_style'] = isset( $input['button_style'] ) ? sanitize_text_field( $input['button_style'] ) : 'icon_only';
    $new_input['button_text'] = isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : esc_html__( 'Chat with us', 'wcw-pro' );
    
    if (isset($input['api_key'])) {
        $api_key = sanitize_text_field($input['api_key']);
        $new_input['api_key'] = $api_key;
        
        if ( $api_key === 'PRO-UNLOCK-2025' ) {
            $new_input['api_key_status'] = 'valid';
            $new_input['api_key_expires'] = 0;
            if (($options['api_key_status'] ?? 'invalid') !== 'valid') {
                add_settings_error('wcw_pro_settings', 'api_key_valid', 'Pro features have been activated!', 'updated');
            }
        } else if ( !empty($api_key) && strpos($api_key, 'TRIAL-') === 0 ) {
            $trials = get_option('wcw_pro_trials', []);
            if (isset($trials[$api_key])) {
                $expiration = $trials[$api_key]['expires'];
                $new_input['api_key_expires'] = $expiration;
                if (time() > $expiration) {
                    $new_input['api_key_status'] = 'expired';
                    add_settings_error('wcw_pro_settings', 'api_key_expired', 'Your trial key has expired.', 'error');
                } else {
                    $new_input['api_key_status'] = 'valid_trial';
                    add_settings_error('wcw_pro_settings', 'api_key_trial_valid', 'Your 7-day trial key is active!', 'updated');
                }
            } else {
                $new_input['api_key_status'] = 'invalid';
                add_settings_error('wcw_pro_settings', 'api_key_invalid', 'The trial key you entered is not valid.', 'error');
            }
        } else {
            $new_input['api_key_status'] = 'invalid';
            $new_input['api_key_expires'] = 0;
            if (!empty($api_key)) {
                add_settings_error('wcw_pro_settings', 'api_key_invalid', 'The API key you entered is not valid.', 'error');
            }
        }
    } else {
        $new_input['api_key'] = $options['api_key'] ?? '';
    }
    
    if ( isset( $input['agents'] ) && is_array( $input['agents'] ) ) {
        $agents = array_filter($input['agents'], function($agent) {
            return !empty($agent['name']);
        });

        $is_pro = ($new_input['api_key_status'] === 'valid' || ($new_input['api_key_status'] === 'valid_trial' && time() < $new_input['api_key_expires']));

        $new_input['agents'] = array_map( function( $agent ) use ($is_pro) {
            $sanitized_agent = [];
            $sanitized_agent['name'] = sanitize_text_field( $agent['name'] ?? '' );
            $sanitized_agent['title'] = sanitize_text_field( $agent['title'] ?? '' );
            $sanitized_agent['phone'] = sanitize_text_field( $agent['phone'] ?? '' );
            $sanitized_agent['message'] = sanitize_textarea_field( $agent['message'] ?? '' );

            if ($is_pro) {
                $sanitized_agent['department'] = sanitize_text_field( $agent['department'] ?? '' );
                $sanitized_agent['image_id'] = isset( $agent['image_id'] ) ? absint( $agent['image_id'] ) : 0;
            }
            return $sanitized_agent;
        }, $agents );
        
        if (!$is_pro && count($new_input['agents']) > 1) {
             $new_input['agents'] = array_slice($new_input['agents'], 0, 1);
        }

        $new_input['agents'] = array_values($new_input['agents']); // Re-index array
    }

    return $new_input;
}

/**
 * Render a generic settings field.
 * @since 2.0.0
 */
function wcw_pro_render_field($args) {
    $options = get_option( 'wcw_pro_settings', [] );
    $value = $options[$args['id']] ?? ($args['default'] ?? '');
    $type = $args['type'];
    $id = $args['id'];
    $description = $args['description'] ?? '';

    switch ($type) {
        case 'text':
            echo "<input type='text' id='{$id}' name='wcw_pro_settings[{$id}]' value='" . esc_attr($value) . "' class='regular-text'>";
            break;
        case 'color':
            echo "<input type='text' id='{$id}' name='wcw_pro_settings[{$id}]' value='" . esc_attr($value) . "' class='wcw-pro-color-picker'>";
            break;
    }

    if ($description) {
        echo "<p class='description'>" . esc_html($description) . "</p>";
    }
}

/**
 * Render Button Style Field
 * @since 2.6.0
 */
function wcw_pro_button_style_field_callback() {
    $options = get_option('wcw_pro_settings');
    $style = $options['button_style'] ?? 'icon_only';
    ?>
    <fieldset>
        <label style="margin-right: 20px;">
            <input type="radio" name="wcw_pro_settings[button_style]" value="icon_only" <?php checked($style, 'icon_only'); ?>>
            <?php esc_html_e('Icon Only', 'wcw-pro'); ?>
        </label>
        <label>
            <input type="radio" name="wcw_pro_settings[button_style]" value="icon_text" <?php checked($style, 'icon_text'); ?>>
            <?php esc_html_e('Icon with Text', 'wcw-pro'); ?>
        </label>
    </fieldset>
    <?php
}

/**
 * Render the repeater field for agents.
 * @since 2.6.3 (UI Update)
 */
function wcw_pro_agents_field_callback() {
    $options = get_option( 'wcw_pro_settings', [] );
    $agents = $options['agents'] ?? [];
    $is_pro = wcw_pro_is_pro();
    ?>
    <p class="description" style="margin-bottom: 20px;"><?php esc_html_e( 'Add and manage your support agents here. Click the agent name to expand/collapse.', 'wcw-pro' ); ?></p>
    <div class="wcw-pro-repeater-container">
        <div id="wcw-pro-repeater" class="wcw-pro-accordion">
            <?php if ( ! empty( $agents ) ) : ?>
                <?php foreach ( $agents as $index => $agent ) : ?>
                    <?php
                        $image_id = $agent['image_id'] ?? 0;
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : ( 'https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar' );
                    ?>
                    <div class="wcw-pro-repeater-item">
                        <div class="wcw-pro-repeater-handle">
                            <h3><span class="dashicons dashicons-admin-users"></span><?php echo esc_html( $agent['name'] ?: 'New Agent' ); ?></h3>
                            <span class="wcw-pro-handle-arrow dashicons dashicons-arrow-down-alt2"></span>
                        </div>
                        <div class="wcw-pro-repeater-content">
                            <div class="wcw-pro-agent-grid">
                                <div class="wcw-pro-agent-avatar-section">
                                    <div class="wcw-pro-image-preview">
                                        <img src="<?php echo esc_url($image_url); ?>">
                                    </div>
                                    <div class="wcw-pro-field-wrapper <?php if (!$is_pro) echo 'wcw-pro-locked'; ?>">
                                        <input type="hidden" class="wcw-pro-image-id" name="wcw_pro_settings[agents][<?php echo $index; ?>][image_id]" value="<?php echo esc_attr( $image_id ); ?>">
                                        <button type="button" class="button wcw-pro-upload-image-button" <?php disabled(!$is_pro); ?>><?php esc_html_e('Upload Image', 'wcw-pro'); ?></button>
                                        <button type="button" class="button button-link wcw-pro-remove-image-button" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove', 'wcw-pro'); ?></button>
                                        <?php if (!$is_pro) echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                                    </div>
                                </div>
                                <div class="wcw-pro-agent-details-section">
                                    <div class="wcw-pro-form-row">
                                        <div class="wcw-pro-form-group">
                                            <label><?php esc_html_e( 'Agent Name', 'wcw-pro' ); ?></label>
                                            <input class="widefat agent-name-field" name="wcw_pro_settings[agents][<?php echo $index; ?>][name]" type="text" value="<?php echo esc_attr( $agent['name'] ); ?>" placeholder="e.g. John Doe">
                                        </div>
                                        <div class="wcw-pro-form-group">
                                            <label><?php esc_html_e( 'Agent Title/Role', 'wcw-pro' ); ?></label>
                                            <input class="widefat" name="wcw_pro_settings[agents][<?php echo $index; ?>][title]" type="text" value="<?php echo esc_attr( $agent['title'] ); ?>" placeholder="e.g. Sales Manager">
                                        </div>
                                    </div>
                                    <div class="wcw-pro-form-row">
                                        <div class="wcw-pro-form-group">
                                            <label><?php esc_html_e( 'WhatsApp Number', 'wcw-pro' ); ?></label>
                                            <input class="widefat" name="wcw_pro_settings[agents][<?php echo $index; ?>][phone]" type="text" value="<?php echo esc_attr( $agent['phone'] ); ?>" placeholder="e.g. +1234567890">
                                        </div>
                                         <div class="wcw-pro-form-group wcw-pro-field-wrapper <?php if (!$is_pro) echo 'wcw-pro-locked'; ?>">
                                            <label><?php esc_html_e( 'Department', 'wcw-pro' ); ?></label>
                                            <input class="widefat" name="wcw_pro_settings[agents][<?php echo $index; ?>][department]" type="text" value="<?php echo esc_attr( $agent['department'] ?? '' ); ?>" placeholder="e.g. Support" <?php disabled(!$is_pro); ?>>
                                            <?php if (!$is_pro) echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                                        </div>
                                    </div>
                                     <div class="wcw-pro-form-group">
                                        <label><?php esc_html_e( 'Prefilled Message', 'wcw-pro' ); ?></label>
                                        <textarea class="widefat" name="wcw_pro_settings[agents][<?php echo $index; ?>][message]" rows="3" placeholder="e.g. Hello! I have a question about..."><?php echo esc_textarea( $agent['message'] ); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="wcw-pro-repeater-footer">
                                <a href="#" class="button button-link-delete wcw-pro-remove-agent"><span class="dashicons dashicons-trash"></span><?php esc_html_e( 'Remove Agent', 'wcw-pro' ); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="wcw-pro-add-agent-wrapper <?php if (!$is_pro && count($agents) >= 1) echo 'wcw-pro-locked'; ?>" style="margin-top: 20px;">
             <a href="#" id="wcw-pro-add-agent" class="button button-primary button-large"><span class="dashicons dashicons-plus-alt"></span><?php esc_html_e( 'Add New Agent', 'wcw-pro' ); ?></a>
            <?php if (!$is_pro && count($agents) >= 1) echo '<span class="wcw-pro-badge">PRO feature: Add more than one agent</span>'; ?>
        </div>
    </div>
    <script type="text/template" id="tmpl-wcw-pro-repeater-template">
        <div class="wcw-pro-repeater-item">
            <div class="wcw-pro-repeater-handle">
                <h3><span class="dashicons dashicons-admin-users"></span><?php esc_html_e( 'New Agent', 'wcw-pro' ); ?></h3>
                <span class="wcw-pro-handle-arrow dashicons dashicons-arrow-down-alt2"></span>
            </div>
            <div class="wcw-pro-repeater-content">
                <div class="wcw-pro-agent-grid">
                    <div class="wcw-pro-agent-avatar-section">
                        <div class="wcw-pro-image-preview">
                             <img src="https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar">
                        </div>
                         <div class="wcw-pro-field-wrapper <?php if (!$is_pro) echo 'wcw-pro-locked'; ?>">
                            <input type="hidden" class="wcw-pro-image-id" name="wcw_pro_settings[agents][<%= index %>][image_id]" value="0">
                            <button type="button" class="button wcw-pro-upload-image-button" <?php disabled(!$is_pro); ?>><?php esc_html_e('Upload Image', 'wcw-pro'); ?></button>
                            <button type="button" class="button button-link wcw-pro-remove-image-button" style="display:none;"><?php esc_html_e('Remove', 'wcw-pro'); ?></button>
                            <?php if (!$is_pro) echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                        </div>
                    </div>
                    <div class="wcw-pro-agent-details-section">
                        <div class="wcw-pro-form-row">
                            <div class="wcw-pro-form-group">
                                <label><?php esc_html_e( 'Agent Name', 'wcw-pro' ); ?></label>
                                <input class="widefat agent-name-field" name="wcw_pro_settings[agents][<%= index %>][name]" type="text" value="" placeholder="e.g. Jane Doe">
                            </div>
                            <div class="wcw-pro-form-group">
                                <label><?php esc_html_e( 'Agent Title/Role', 'wcw-pro' ); ?></label>
                                <input class="widefat" name="wcw_pro_settings[agents][<%= index %>][title]" type="text" value="" placeholder="e.g. Customer Support">
                            </div>
                        </div>
                        <div class="wcw-pro-form-row">
                             <div class="wcw-pro-form-group">
                                <label><?php esc_html_e( 'WhatsApp Number', 'wcw-pro' ); ?></label>
                                <input class="widefat" name="wcw_pro_settings[agents][<%= index %>][phone]" type="text" value="" placeholder="e.g. +1234567890">
                            </div>
                            <div class="wcw-pro-form-group wcw-pro-field-wrapper <?php if (!$is_pro) echo 'wcw-pro-locked'; ?>">
                                <label><?php esc_html_e( 'Department', 'wcw-pro' ); ?></label>
                                <input class="widefat" name="wcw_pro_settings[agents][<%= index %>][department]" type="text" value="" placeholder="e.g. Sales" <?php disabled(!$is_pro); ?>>
                                <?php if (!$is_pro) echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                            </div>
                        </div>
                         <div class="wcw-pro-form-group">
                            <label><?php esc_html_e( 'Prefilled Message', 'wcw-pro' ); ?></label>
                            <textarea class="widefat" name="wcw_pro_settings[agents][<%= index %>][message]" rows="3" placeholder="e.g. Hello! I have a question about..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="wcw-pro-repeater-footer">
                    <a href="#" class="button button-link-delete wcw-pro-remove-agent"><span class="dashicons dashicons-trash"></span><?php esc_html_e( 'Remove Agent', 'wcw-pro' ); ?></a>
                </div>
            </div>
        </div>
    </script>
    <?php
}

/**
 * Render the main settings page HTML structure.
 * @since 2.1.0
 */
function wcw_pro_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    ?>
    <div class="wrap wcw-pro-settings-wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <div class="wcw-pro-settings-content">
            <div class="wcw-pro-settings-main">
                <nav class="nav-tab-wrapper wcw-pro-nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active">General</a>
                    <a href="#display" class="nav-tab">Display & Styling</a>
                    <a href="#agents" class="nav-tab">Agents</a>
                    <a href="#license" class="nav-tab">License</a>
                </nav>
                <form action="options.php" method="post">
                    <?php settings_fields( 'wcw_pro_options_group' ); ?>
                    <div id="general" class="wcw-pro-tab-content active">
                        <table class="form-table"><?php do_settings_sections('wcw_pro_settings_general'); ?></table>
                    </div>
                    <div id="display" class="wcw-pro-tab-content">
                        <table class="form-table"><?php do_settings_sections('wcw_pro_settings_display'); ?></table>
                    </div>
                    <div id="agents" class="wcw-pro-tab-content">
                        <?php do_settings_sections('wcw_pro_settings_agents'); ?>
                    </div>
                    <div id="license" class="wcw-pro-tab-content">
                        <table class="form-table"><?php do_settings_sections('wcw_pro_settings_license'); ?></table>
                    </div>
                    <?php submit_button( 'Save Settings' ); ?>
                </form>
            </div>
            <div class="wcw-pro-settings-sidebar">
                <div class="wcw-pro-sidebar-box">
                    <h3><?php esc_html_e( 'Live Preview', 'wcw-pro' ); ?></h3>
                    <div id="wcw-pro-preview-container">
                        <div id="wcw-pro-preview-bubble">
                            <svg id="wcw-pro-preview-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="30" height="30"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.8 0-67.3-10.2-96.1-29.1l-6.7-4-71.6 18.7 19.3-68.6-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.8-16.2-54.3-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.9 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
                            <span id="wcw-pro-preview-text"></span>
                        </div>
                    </div>
                </div>
                 <div class="wcw-pro-sidebar-box wcw-pro-ad-box">
                    <h3><?php esc_html_e( 'Advertisement', 'wcw-pro' ); ?></h3>
                    <div class="wcw-pro-ad-content">
                         <a href="https://example.com" target="_blank" title="Your Ad Here"><img src="https://placehold.co/250x250/E8F5E9/333333?text=Your+Ad+Here" alt="Advertisement"/></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render License Status Field
 * @since 2.5.0
 */
function wcw_pro_api_key_status_field_callback() {
    $options = get_option('wcw_pro_settings');
    $status = $options['api_key_status'] ?? 'invalid';
    $expires = $options['api_key_expires'] ?? 0;

    echo '<div class="wcw-pro-license-status">';
    if ($status === 'valid') {
        echo '<p class="status-valid"><strong>Active:</strong> Pro License</p>';
    } else if ($status === 'valid_trial' && time() < $expires) {
        echo '<p class="status-trial"><strong>Active:</strong> Trial License (Expires on: ' . date_i18n(get_option('date_format'), $expires) . ')</p>';
    } else if ($status === 'expired') {
         echo '<p class="status-expired"><strong>Expired:</strong> Your trial has ended.</p>';
    } else {
        echo '<p class="status-invalid"><strong>Inactive:</strong> No valid license key found.</p>';
    }
    echo '</div>';
}

/**
 * Render Trial Request Field
 * @since 2.5.0
 */
function wcw_pro_trial_request_field_callback() {
    ?>
    <p class="description"><?php esc_html_e('Enter your email to receive a 7-day trial key for Pro features.', 'wcw-pro'); ?></p>
    <div id="wcw-pro-trial-form">
        <input type="email" id="wcw-pro-trial-email" placeholder="your-email@example.com" class="regular-text">
        <button type="button" id="wcw-pro-request-trial-btn" class="button button-secondary">Request Trial Key</button>
        <span class="spinner"></span>
    </div>
    <div id="wcw-pro-trial-message" style="display:none; margin-top: 10px;"></div>
    <?php
}

// =============================================================================
// AJAX HANDLER
// =============================================================================

/**
 * Handle Trial Key Request via AJAX
 * @since 2.5.0
 */
function wcw_pro_handle_trial_request() {
    check_ajax_referer('wcw_pro_trial_nonce', 'nonce');

    if (!isset($_POST['email']) || !is_email($_POST['email'])) {
        wp_send_json_error(['message' => 'Please provide a valid email address.']);
    }

    $email = sanitize_email($_POST['email']);
    $trial_key = 'TRIAL-' . strtoupper(wp_generate_password(12, false));
    $expiration = time() + (7 * DAY_IN_SECONDS);

    $trials = get_option('wcw_pro_trials', []);
    $trials[$trial_key] = [
        'email' => $email,
        'expires' => $expiration,
    ];
    update_option('wcw_pro_trials', $trials);

    $message = sprintf(
        'Success! Your 7-day trial key has been generated: %s. Please copy this key, paste it into the API License Key field above, then click "Save Settings".',
        '<br><strong><code>' . $trial_key . '</code></strong>'
    );
    
    wp_send_json_success(['message' => $message]);
}
add_action('wp_ajax_wcw_pro_request_trial', 'wcw_pro_handle_trial_request');

// =============================================================================
// FRONTEND WIDGET
// =============================================================================

/**
 * Render the floating chat widget on the frontend.
 * @since 2.4.0
 */
function wcw_pro_render_frontend_widget() {
    $options = get_option('wcw_pro_settings', []);
    $title = $options['popup_title'] ?? esc_html__( 'Start a Conversation', 'wcw-pro' );
    $agents = $options['agents'] ?? [];
    $button_style = $options['button_style'] ?? 'icon_only';
    $button_text = $options['button_text'] ?? esc_html__('Chat with us', 'wcw-pro');
    $is_pro = wcw_pro_is_pro();

    if ( empty( $agents ) ) return;

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
             <svg class="wcw-pro-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="30" height="30"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.8 0-67.3-10.2-96.1-29.1l-6.7-4-71.6 18.7 19.3-68.6-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5c0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.8-16.2-54.3-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.9 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
            <?php if ($button_style === 'icon_text'): ?>
                <span class="wcw-pro-bubble-text"><?php echo esc_html($button_text); ?></span>
            <?php endif; ?>
        </div>
        <div class="wcw-pro-popup">
            <div class="wcw-pro-header">
                <span class="wcw-pro-title"><?php echo esc_html( $title ); ?></span>
                <span class="wcw-pro-close">&times;</span>
            </div>
            <div class="wcw-pro-body">
                <?php foreach ( $agents as $agent ) : ?>
                    <?php
                    $agent_name = $agent['name'] ?? '';
                    $agent_title = $agent['title'] ?? '';
                    $agent_department = ($is_pro && isset($agent['department'])) ? $agent['department'] : '';
                    $agent_phone = $agent['phone'] ?? '';
                    $agent_message = $agent['message'] ?? '';
                    $image_id = ($is_pro && isset($agent['image_id'])) ? $agent['image_id'] : 0;
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, [50, 50]) : 'https://placehold.co/50x50/EFEFEF/AAAAAA&text=Avatar';
                    $phone_number = preg_replace( '/\D/', '', $agent_phone );
                    $prefilled_message = ! empty( $agent_message ) ? $agent_message : sprintf(esc_html__( 'Hello %s!', 'wcw-pro' ), $agent_name);
                    $whatsapp_url = 'https://wa.me/' . esc_attr( $phone_number ) . '?text=' . urlencode( $prefilled_message );
                    $display_title = $agent_title;
                    if (!empty($agent_department)) $display_title .= ' | ' . $agent_department;
                    ?>
                    <a href="<?php echo esc_url( $whatsapp_url ); ?>" target="_blank" rel="noopener noreferrer" class="wcw-pro-agent">
                        <div class="wcw-pro-agent-avatar">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($agent_name); ?>" width="50" height="50">
                        </div>
                        <div class="wcw-pro-agent-details">
                            <span class="wcw-pro-agent-name"><?php echo esc_html( $agent_name ); ?></span>
                            <span class="wcw-pro-agent-title"><?php echo esc_html( $display_title ); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'wcw_pro_render_frontend_widget');

// =============================================================================
// ASSETS (CSS & JS)
// =============================================================================

/**
 * Enqueue scripts and styles.
 * @since 2.0.0
 */
function wcw_pro_enqueue_assets() {
    wp_enqueue_style( 'wcw-pro-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '2.6.3' );
    wp_enqueue_script( 'wcw-pro-script', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array( 'jquery' ), '2.6.3', true );
}
add_action( 'wp_enqueue_scripts', 'wcw_pro_enqueue_assets' );

/**
 * Output dynamic CSS to wp_head to avoid caching issues.
 * @since 2.6.2
 */
function wcw_pro_output_dynamic_styles() {
    $options = get_option('wcw_pro_settings', []);
    $bubble_color = $options['bubble_color'] ?? '#25D366';
    $icon_color = $options['icon_color'] ?? '#FFFFFF';
    
    $css = '<style type="text/css">';
    $css .= '.wcw-pro-bubble { background-color: ' . esc_attr($bubble_color) . '; }';
    $css .= '.wcw-pro-bubble .wcw-pro-icon path { fill: ' . esc_attr($icon_color) . '; }';
    $css .= '.wcw-pro-bubble-text { color: ' . esc_attr($icon_color) . '; }';
    $css .= '.wcw-pro-header { background-color: ' . esc_attr($bubble_color) . '; color: ' . esc_attr($icon_color) . '; }';
    $css .= '.wcw-pro-header .wcw-pro-close { color: ' . esc_attr($icon_color) . '; }';
    $css .= '</style>';
    
    echo $css;
}
add_action('wp_head', 'wcw_pro_output_dynamic_styles');


/**
 * Enqueue admin scripts.
 * @since 2.3.0
 */
function wcw_pro_enqueue_admin_scripts( $hook ) {
    if ( 'toplevel_page_wcw-pro-settings' !== $hook ) return;
    
    wp_enqueue_media();
    
    $options = get_option('wcw_pro_settings', []);

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style( 'wcw-pro-admin-style', plugin_dir_url( __FILE__ ) . 'assets/css/admin.css', array(), '2.6.3' );
    wp_enqueue_script( 'wcw-pro-admin-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery', 'wp-util', 'wp-color-picker', 'jquery-ui-accordion' ), '2.6.3', true );
    wp_localize_script( 'wcw-pro-admin-script', 'wcwProData', [
        'is_pro' => wcw_pro_is_pro(),
        'agent_count' => count($options['agents'] ?? []),
        'nonce' => wp_create_nonce('wcw_pro_trial_nonce'),
        'settings' => [
            'button_style' => $options['button_style'] ?? 'icon_only',
            'button_text' => $options['button_text'] ?? esc_html__('Chat with us', 'wcw-pro'),
        ],
    ]);
}
add_action( 'admin_enqueue_scripts', 'wcw_pro_enqueue_admin_scripts' );

// =============================================================================
// PLUGIN ACTIVATION & ASSET CREATION
// =============================================================================

/**
 * Set default options on plugin activation.
 * @since 2.0.0
 */
function wcw_pro_activate() {
    wcw_pro_create_asset_files();
    if ( false === get_option( 'wcw_pro_settings' ) ) {
        $defaults = [
            'popup_title' => esc_html__( 'Start a Conversation', 'wcw-pro' ),
            'bubble_color' => '#25D366',
            'icon_color' => '#FFFFFF',
            'button_style' => 'icon_only',
            'button_text' => esc_html__( 'Chat with us', 'wcw-pro' ),
            'api_key' => '',
            'api_key_status' => 'invalid',
            'api_key_expires' => 0,
            'agents' => [[ 'name' => 'Support Team', 'title' => 'Customer Support', 'department' => 'Sales', 'phone' => '', 'message' => 'Hello! I have a question.', 'image_id' => 0 ]]
        ];
        add_option( 'wcw_pro_settings', $defaults );
    }
}
register_activation_hook( __FILE__, 'wcw_pro_activate' );

/**
 * Create or update asset files to ensure they are current.
 * @since 2.6.3 (UI Update)
 */
function wcw_pro_create_asset_files() {
    $plugin_dir = plugin_dir_path( __FILE__ );
    wp_mkdir_p( $plugin_dir . 'assets/css' );
    wp_mkdir_p( $plugin_dir . 'assets/js' );

    // Static CSS for frontend layout. Dynamic colors are now handled by wcw_pro_output_dynamic_styles().
    $css_content = '/* Main Frontend Styles */.wcw-pro-container{position:fixed;bottom:25px;right:25px;z-index:1000}.wcw-pro-bubble{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.2);transition:all .2s ease-in-out}.wcw-pro-bubble:hover{transform:scale(1.1)}.wcw-pro-bubble-with-text{width:auto;border-radius:30px;padding:0 20px 0 15px;height:60px}.wcw-pro-bubble-with-text .wcw-pro-icon{margin-right:8px}.wcw-pro-bubble-text{font-size:16px;font-weight:700;white-space:nowrap}.wcw-pro-popup{position:absolute;bottom:80px;right:0;width:320px;background:#fff;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,.2);opacity:0;visibility:hidden;transform:scale(.95) translateY(10px);transform-origin:bottom right;transition:all .2s ease-in-out}.wcw-pro-popup.open{opacity:1;visibility:visible;transform:scale(1) translateY(0)}.wcw-pro-header{padding:15px;border-top-left-radius:10px;border-top-right-radius:10px;display:flex;justify-content:space-between;align-items:center}.wcw-pro-title{font-weight:700}.wcw-pro-close{font-size:24px;cursor:pointer;line-height:1;opacity:.8}.wcw-pro-close:hover{opacity:1}.wcw-pro-body{padding:10px;max-height:400px;overflow-y:auto}.wcw-pro-agent{display:flex;align-items:center;padding:10px;text-decoration:none;color:#333;border-radius:8px;transition:background-color .2s}.wcw-pro-agent:hover{background-color:#f5f5f5}.wcw-pro-agent-avatar{margin-right:15px;width:50px;height:50px}.wcw-pro-agent-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}.wcw-pro-agent-details{display:flex;flex-direction:column}.wcw-pro-agent-name{font-weight:700}.wcw-pro-agent-title{font-size:.9em;color:#777}';
    file_put_contents($plugin_dir . 'assets/css/style.css', $css_content);
    
    // Admin CSS - Updated for new Agent UI
    $admin_css_content = '/* Admin Settings Styles */
.wcw-pro-settings-wrap{margin-top:20px}
.wcw-pro-settings-content{display:flex;gap:20px;margin-top:0}
.wcw-pro-settings-main{flex:2;background:#fff;padding:1px 20px 20px;border:1px solid #c3c4c7;border-radius:4px;}
.wcw-pro-settings-sidebar{flex:1;max-width:300px}
.wcw-pro-sidebar-box{background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px;margin-bottom:20px}
.wcw-pro-sidebar-box h3{margin-top:0;padding-bottom:10px;border-bottom:1px solid #ddd}
.wcw-pro-nav-tab-wrapper{margin-bottom:1px;border-bottom:1px solid #c3c4c7}
.wcw-pro-nav-tab-wrapper .nav-tab{border:1px solid transparent;border-top:1px solid #c3c4c7;border-left:1px solid #c3c4c7;border-right:1px solid #c3c4c7;margin:0 5px -1px 0;background:#f0f0f1;border-top-left-radius:3px;border-top-right-radius:3px}
.wcw-pro-nav-tab-wrapper .nav-tab-active{background:#fff;border-color:#c3c4c7;border-bottom-color:#fff}
.wcw-pro-tab-content{display:none;padding-top:20px}
.wcw-pro-tab-content.active{display:block}
#button_text_field_row{display:none}

/* New Agent Form Styles */
.wcw-pro-repeater-item { border: 1px solid #e0e0e0; border-radius: 4px; margin-bottom: 15px; background: #fff; box-shadow: 0 1px 1px rgba(0,0,0,.04); overflow: hidden; }
.wcw-pro-repeater-handle { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; background: #fafafa; border-bottom: 1px solid #e0e0e0; cursor: pointer; transition: background .2s; }
.wcw-pro-repeater-handle:hover { background: #f5f5f5; }
.wcw-pro-repeater-handle h3 { margin: 0; font-size: 16px; display: flex; align-items: center; gap: 10px; color: #2271b1; }
.wcw-pro-repeater-handle .wcw-pro-handle-arrow { transition: transform .2s; font-size: 20px; }
.wcw-pro-repeater-item.open > .wcw-pro-repeater-handle .wcw-pro-handle-arrow { transform: rotate(180deg); }
.wcw-pro-repeater-content { display: none; padding: 20px; }
.wcw-pro-agent-grid { display: grid; grid-template-columns: 150px 1fr; gap: 30px; align-items: flex-start; }
.wcw-pro-agent-avatar-section { text-align: center; }
.wcw-pro-image-preview { width: 100px; height: 100px; border: 2px solid #ddd; background: #f0f0f1; margin: 0 auto 15px; border-radius: 50%; overflow: hidden; }
.wcw-pro-image-preview img { width: 100%; height: 100%; object-fit: cover; }
.wcw-pro-agent-avatar-section .button { margin-bottom: 5px; }
.wcw-pro-agent-avatar-section .wcw-pro-remove-image-button { color: #b32d2e; }
.wcw-pro-agent-details-section { display: flex; flex-direction: column; gap: 15px; }
.wcw-pro-form-row { display: flex; gap: 20px; }
.wcw-pro-form-group { flex: 1; display: flex; flex-direction: column; }
.wcw-pro-form-group label { margin-bottom: 5px; font-weight: 600; color: #333; }
.wcw-pro-form-group input, .wcw-pro-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,.07); }
.wcw-pro-repeater-footer { margin-top: 20px; text-align: right; }
.wcw-pro-repeater-footer .wcw-pro-remove-agent { color: #b32d2e; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
.wcw-pro-repeater-footer .wcw-pro-remove-agent:hover { color: #000; }
.wcw-pro-add-agent-wrapper .button-large { font-size: 16px; height: 46px; line-height: 44px; padding: 0 20px; display: inline-flex; align-items: center; gap: 8px; }

/* Locked Pro Fields */
.wcw-pro-field-wrapper { position: relative; }
.wcw-pro-field-wrapper.wcw-pro-locked { opacity:.6; pointer-events:none; }
.wcw-pro-badge { position:absolute; top:-10px; right:-10px; background:#ffba00; color:#fff; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; text-transform:uppercase; z-index: 1; }

#wcw-pro-preview-container{background:#f0f0f1;padding:20px;text-align:center;border:1px dashed #ccc;border-radius:4px}
#wcw-pro-preview-bubble{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;transition:all .2s}
#wcw-pro-preview-bubble.has-text{width:auto;padding:0 20px 0 15px;border-radius:30px}
#wcw-pro-preview-bubble.has-text #wcw-pro-preview-icon{margin-right:8px}
#wcw-pro-preview-text{font-size:16px;font-weight:700;white-space:nowrap;display:none}
#wcw-pro-preview-bubble.has-text #wcw-pro-preview-text{display:inline}
#wcw-pro-preview-icon path{transition:fill .2s}
.wcw-pro-ad-box .wcw-pro-ad-content{text-align:center}
.wcw-pro-ad-box img{max-width:100%;height:auto;border-radius:4px}
.wcw-pro-license-status p{padding:10px;border-radius:4px;margin:0}
.status-valid{background-color:#e0f2e1;border-left:4px solid #4CAF50}
.status-trial{background-color:#fff3e0;border-left:4px solid #ff9800}
.status-expired,.status-invalid{background-color:#ffeb ee;border-left:4px solid #f44336}
#wcw-pro-trial-message{padding:10px;border-radius:4px;border-left-width:4px;border-left-style:solid}
#wcw-pro-trial-message.updated{background-color:#e0f2e1;border-color:#4CAF50}
#wcw-pro-trial-message.error{background-color:#ffeb ee;border-color:#f44336}';
    file_put_contents($plugin_dir . 'assets/css/admin.css', $admin_css_content);
    
    // JS files
    $js_content = "jQuery(document).ready(function(o){\"use strict\";o(\".wcw-pro-bubble\").on(\"click\",function(c){c.stopPropagation(),o(\".wcw-pro-popup\").toggleClass(\"open\")}),o(\".wcw-pro-close\").on(\"click\",function(){o(\".wcw-pro-popup\").removeClass(\"open\")}),o(document).on(\"click\",function(c){o(c.target).closest(\".wcw-pro-container\").length||o(\".wcw-pro-popup\").hasClass(\"open\")&&o(\".wcw-pro-popup\").removeClass(\"open\")})});";
    file_put_contents($plugin_dir . 'assets/js/main.js', $js_content);
    
    // Admin JS - Updated for new Accordion
    $admin_js_content = "jQuery(function(e){\"use strict\";var c;var t=e(\"#wcw-pro-repeater\"),n=e(\"#wcw-pro-add-agent\"),a=wp.template(\"wcw-pro-repeater-template\"),r=e('input[name=\"wcw_pro_settings[bubble_color]\"]'),o=e('input[name=\"wcw_pro_settings[icon_color]\"]'),i=e('input[name=\"wcw_pro_settings[button_style]\"]'),d=e('input[name=\"wcw_pro_settings[button_text]\"]'),l=e(\"#wcw-pro-preview-bubble\"),s=e(\"#wcw-pro-preview-icon\"),p=e(\"#wcw-pro-preview-text\");function u(){var t=e('input[name=\"wcw_pro_settings[button_style]\"]:checked').val(),c=d.closest(\"tr\");\"icon_text\"===t?(c.show(),l.addClass(\"has-text\"),p.text(d.val())):(c.hide(),l.removeClass(\"has-text\")),w()}function w(){l.css(\"background-color\",r.val()),s.find(\"path\").attr(\"fill\",o.val()),p.css(\"color\",o.val())}function f(){t.children(\".wcw-pro-repeater-item\").each(function(){var t=e(this),c=t.find(\".wcw-pro-repeater-handle\"),n=t.find(\".wcw-pro-repeater-content\");c.off(\"click.wcwpro\").on(\"click.wcwpro\",function(){t.toggleClass(\"open\"),n.slideToggle(200)})})}e(\".wcw-pro-color-picker\").wpColorPicker({change:function(e,t){w()}}),n.on(\"click\",function(c){c.preventDefault();if(e(this).is(\":disabled\"))return;var r={index:t.children(\".wcw-pro-repeater-item\").length};t.append(a(r));var o=t.children(\".wcw-pro-repeater-item\").last();o.addClass(\"open\").find(\".wcw-pro-repeater-content\").slideDown(200),f()}),t.on(\"click\",\".wcw-pro-remove-agent\",function(c){c.preventDefault(),confirm(\"Are you sure you want to remove this agent?\")&&e(this).closest(\".wcw-pro-repeater-item\").slideUp(300,function(){e(this).remove(),wcwProData.is_pro||t.children(\".wcw-pro-repeater-item\").length<1&&e(\"#wcw-pro-add-agent\").prop(\"disabled\",!1).parent().removeClass(\"wcw-pro-locked\")})}),t.on(\"keyup\",\".agent-name-field\",function(){var t=e(this).val(),c=e(this).closest(\".wcw-pro-repeater-item\").find(\".wcw-pro-repeater-handle h3\");c.find(\"span\").next().text(t||\"New Agent\")}),t.on(\"click\",\".wcw-pro-upload-image-button\",function(t){if(t.preventDefault(),!e(this).is(\":disabled\")){var n=e(this),a=n.siblings(\".wcw-pro-image-id\"),r=n.closest(\".wcw-pro-agent-avatar-section\").find(\".wcw-pro-image-preview img\"),o=n.siblings(\".wcw-pro-remove-image-button\");c&&c.off(\"select\"),c=wp.media({title:\"Select Agent Image\",button:{text:\"Use this image\"},multiple:!1}).on(\"select\",function(){var e=c.state().get(\"selection\").first().toJSON();a.val(e.id),r.attr(\"src\",e.sizes.thumbnail?e.sizes.thumbnail.url:e.url),o.show()}),c.open()}}),t.on(\"click\",\".wcw-pro-remove-image-button\",function(t){t.preventDefault();var c=e(this),n=c.closest(\".wcw-pro-agent-avatar-section\").find(\".wcw-pro-image-preview img\");c.hide().siblings(\".wcw-pro-image-id\").val(\"0\"),n.attr(\"src\",\"https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar\")}),e(\".wcw-pro-nav-tab-wrapper .nav-tab\").on(\"click\",function(t){t.preventDefault();var c=e(this);c.addClass(\"nav-tab-active\").siblings().removeClass(\"nav-tab-active\");var n=c.attr(\"href\");e(\".wcw-pro-tab-content\").removeClass(\"active\"),e(n).addClass(\"active\")}),e('input[name=\"wcw_pro_settings[button_style]\"]').on(\"change\",u),d.on(\"keyup\",function(){p.text(e(this).val())}),u(),w(),f(),e(\"#wcw-pro-request-trial-btn\").on(\"click\",function(t){t.preventDefault();var c=e(this),n=e(\"#wcw-pro-trial-email\"),a=n.val(),r=c.siblings(\".spinner\"),o=e(\"#wcw-pro-trial-message\");return a?(r.addClass(\"is-active\"),c.prop(\"disabled\",!0),o.hide(),void e.ajax({url:ajaxurl,type:\"POST\",data:{action:\"wcw_pro_request_trial\",email:a,nonce:wcwProData.nonce},success:function(t){t.success?(o.html(t.data.message).removeClass(\"error\").addClass(\"updated notice notice-success\").show(),n.val(\"\")):o.html(t.data.message).addClass(\"error notice notice-error\").show()},error:function(){o.html(\"An unknown error occurred. Please try again.\").addClass(\"error notice notice-error\").show()},complete:function(){r.removeClass(\"is-active\"),c.prop(\"disabled\",!1)}})):(o.html(\"Please enter your email address.\").addClass(\"error notice notice-error\").show(),!1)})});";
    file_put_contents($plugin_dir . 'assets/js/admin.js', $admin_js_content);
}
add_action('admin_init', 'wcw_pro_create_asset_files');
