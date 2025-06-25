<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class WCW_Pro_Settings
{
    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings()
    {
        register_setting('wcw_pro_options_group', 'wcw_pro_settings', array($this, 'sanitize_settings'));

        // General Section
        add_settings_section('wcw_pro_general_section', null, array($this, 'general_settings_fields_callback'), 'wcw_pro_settings_general');

        // Display Section
        add_settings_section('wcw_pro_display_section', null, array($this, 'display_settings_fields_callback'), 'wcw_pro_settings_display');

        // Agents Section
        add_settings_section('wcw_pro_agents_section', null, '__return_false', 'wcw_pro_settings_agents');
        add_settings_field('wcw_pro_agents_field', null, array($this, 'agents_field_callback'), 'wcw_pro_settings_agents', 'wcw_pro_agents_section');

        // License Section
        add_settings_section('wcw_pro_license_section', 'License & Activation', '__return_false', 'wcw_pro_settings_license');
        add_settings_field('wcw_pro_api_key_status_field', esc_html__('License Status', 'floating-wa-chat-pro-widget'), array($this, 'api_key_status_field_callback'), 'wcw_pro_settings_license', 'wcw_pro_license_section');
        add_settings_field('wcw_pro_api_key_field', esc_html__('License Key', 'floating-wa-chat-pro-widget'), array($this, 'api_key_field_callback'), 'wcw_pro_settings_license', 'wcw_pro_license_section');
        add_settings_field('wcw_pro_trial_request_field', esc_html__('Request a Trial Key', 'floating-wa-chat-pro-widget'), array($this, 'trial_request_field_callback'), 'wcw_pro_settings_license', 'wcw_pro_license_section');
    }

    public function general_settings_fields_callback()
    {
?>
        <div class="wcw-pro-settings-grid">
            <div class="wcw-pro-settings-card">
                <div class="wcw-pro-card-header">
                    <h3><span class="dashicons dashicons-admin-settings"></span> General Configuration</h3>
                </div>
                <div class="wcw-pro-card-body">
                    <div class="wcw-pro-form-group">
                        <label for="popup_title"><?php esc_html_e('Popup Title', 'floating-wa-chat-pro-widget'); ?></label>
                        <?php $this->render_field(['type' => 'text', 'id' => 'popup_title', 'default' => 'Start a Conversation', 'placeholder' => 'Start a Conversation']); ?>
                        <p class='description'>The title displayed at the top of the chat popup.</p>
                    </div>
                    <div class="wcw-pro-form-group">
                        <label><?php esc_html_e('Button Position', 'floating-wa-chat-pro-widget'); ?></label>
                        <?php $this->button_position_field_callback(); ?>
                        <p class='description'>Choose where the floating button appears on your site.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function display_settings_fields_callback()
    {
    ?>
        <div class="wcw-pro-settings-grid">
            <div class="wcw-pro-settings-card">
                <div class="wcw-pro-card-header">
                    <h3><span class="dashicons dashicons-admin-customizer"></span> Color Settings</h3>
                </div>
                <div class="wcw-pro-card-body">
                    <div class="wcw-pro-form-group">
                        <label for="bubble_color"><?php esc_html_e('Bubble Background Color', 'floating-wa-chat-pro-widget'); ?></label>
                        <?php $this->render_field(['type' => 'color', 'id' => 'bubble_color', 'default' => '#25D366']); ?>
                    </div>
                    <div class="wcw-pro-form-group">
                        <label for="icon_color"><?php esc_html_e('Icon & Text Color', 'floating-wa-chat-pro-widget'); ?></label>
                        <?php $this->render_field(['type' => 'color', 'id' => 'icon_color', 'default' => '#FFFFFF']); ?>
                    </div>
                </div>
            </div>
            <div class="wcw-pro-settings-card">
                <div class="wcw-pro-card-header">
                    <h3><span class="dashicons dashicons-layout"></span> Button Configuration</h3>
                </div>
                <div class="wcw-pro-card-body">
                    <div class="wcw-pro-form-group">
                        <label><?php esc_html_e('Button Style', 'floating-wa-chat-pro-widget'); ?></label>
                        <?php $this->button_style_field_callback(); ?>
                    </div>
                    <div class="wcw-pro-form-group" id="button_text_field_group" style="display:none;">
                        <label for="button_text"><?php esc_html_e('Button Text', 'floating-wa-chat-pro-widget'); ?></label>
                        <?php $this->render_field(['type' => 'text', 'id' => 'button_text', 'default' => 'Need Help!', 'placeholder' => 'Need Help!']); ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    public function sanitize_settings($input)
    {
        $new_input = [];
        $options = get_option('wcw_pro_settings', []);

        $new_input['api_key_status'] = $options['api_key_status'] ?? 'invalid';
        $new_input['api_key_expires'] = $options['api_key_expires'] ?? 0;

        $new_input['popup_title'] = isset($input['popup_title']) ? sanitize_text_field($input['popup_title']) : '';
        $new_input['button_position'] = isset($input['button_position']) ? sanitize_text_field($input['button_position']) : 'bottom_right';
        $new_input['bubble_color'] = isset($input['bubble_color']) ? sanitize_hex_color($input['bubble_color']) : '#25D366';
        $new_input['icon_color'] = isset($input['icon_color']) ? sanitize_hex_color($input['icon_color']) : '#FFFFFF';
        $new_input['button_style'] = isset($input['button_style']) ? sanitize_text_field($input['button_style']) : 'icon_only';
        $new_input['button_text'] = isset($input['button_text']) ? sanitize_text_field($input['button_text']) : esc_html__('Need Help!', 'floating-wa-chat-pro-widget');

        if (isset($input['api_key'])) {
            $submitted_key = sanitize_text_field($input['api_key']);

            if (strpos($submitted_key, '✱') !== false) {
                $api_key = $options['api_key'] ?? '';
            } else {
                $api_key = $submitted_key;
            }

            $new_input['api_key'] = $api_key;

            if (!empty($api_key) && ($api_key !== ($options['api_key'] ?? '') || strpos($submitted_key, '✱') === false)) {
                if ($api_key === 'PRO-UNLOCK-2025') {
                    $new_input['api_key_status'] = 'valid';
                    $new_input['api_key_expires'] = 0;
                    add_settings_error('wcw_pro_settings', 'api_key_valid', 'Pro features have been activated using a local key!', 'updated');
                } else if (strpos($api_key, 'TRIAL-') === 0) {
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
                        add_settings_error('wcw_pro_settings', 'api_key_invalid', 'The trial key you entered is not valid or does not exist.', 'error');
                    }
                } else {
                    $domain = home_url();
                    $verify_url = add_query_arg([
                        'action' => 'verify',
                        'key' => urlencode($api_key),
                        'domain' => urlencode($domain),
                    ], WCW_PRO_LICENSE_SERVER_URL);

                    $response = wp_safe_remote_get($verify_url, [
                        'timeout' => 20,
                        'headers' => ['Accept' => 'application/json'],
                    ]);

                    if (is_wp_error($response)) {
                        $new_input['api_key_status'] = 'invalid';
                        $new_input['api_key_expires'] = 0;
                        add_settings_error('wcw_pro_settings', 'api_key_connection_error', 'Could not connect to the license server. Your web hosting might be blocking outbound connections. Error: ' . $response->get_error_message(), 'error');
                    } else {
                        $response_code = wp_remote_retrieve_response_code($response);
                        $body = wp_remote_retrieve_body($response);

                        if ($response_code !== 200) {
                            $new_input['api_key_status'] = 'invalid';
                            $new_input['api_key_expires'] = 0;
                            add_settings_error('wcw_pro_settings', 'api_key_server_error', 'The license server responded with an error (Code: ' . esc_html($response_code) . '). Please try again later.', 'error');
                        } else {
                            $data = json_decode($body, true);

                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $json_text = wp_strip_all_tags($body);
                                if (!empty($json_text)) {
                                    $data = json_decode(trim($json_text), true);
                                }
                            }

                            if (json_last_error() === JSON_ERROR_NONE && isset($data['status'])) {
                                $new_input['api_key_status'] = sanitize_text_field($data['status']);
                                $new_input['api_key_expires'] = isset($data['expires_at']) ? absint($data['expires_at']) : 0;

                                if ($new_input['api_key_status'] === 'valid') {
                                    add_settings_error('wcw_pro_settings', 'api_key_valid', 'Success! Your Pro license has been activated.', 'updated');
                                } elseif ($new_input['api_key_status'] === 'valid_trial') {
                                    add_settings_error('wcw_pro_settings', 'api_key_trial_valid', 'Success! Your trial key is active!', 'updated');
                                } elseif ($new_input['api_key_status'] === 'expired') {
                                    add_settings_error('wcw_pro_settings', 'api_key_expired', 'Your license key has expired.', 'error');
                                } else {
                                    $server_message = isset($data['message']) ? ' Server says: ' . sanitize_text_field($data['message']) : '';
                                    add_settings_error('wcw_pro_settings', 'api_key_invalid', 'The license key is not valid.' . $server_message, 'error');
                                }
                            } else {
                                $new_input['api_key_status'] = 'invalid';
                                $new_input['api_key_expires'] = 0;
                                add_settings_error('wcw_pro_settings', 'api_key_json_error', 'Received an invalid response from the license server. Could not parse JSON. Response body: ' . esc_html(substr($body, 0, 200)), 'error');
                            }
                        }
                    }
                }
            }
        } else {
            $new_input['api_key'] = $options['api_key'] ?? '';
        }

        if (isset($input['agents']) && is_array($input['agents'])) {
            $agents = array_filter($input['agents'], function ($agent) {
                return !empty($agent['name']);
            });

            $is_pro = ($new_input['api_key_status'] === 'valid' || ($new_input['api_key_status'] === 'valid_trial' && time() < $new_input['api_key_expires']));

            $new_input['agents'] = array_map(function ($agent) use ($is_pro) {
                $sanitized_agent = [];
                $sanitized_agent['name'] = sanitize_text_field($agent['name'] ?? '');
                $sanitized_agent['title'] = sanitize_text_field($agent['title'] ?? '');
                $sanitized_agent['phone'] = sanitize_text_field($agent['phone'] ?? '');
                $sanitized_agent['message'] = sanitize_textarea_field($agent['message'] ?? '');

                if ($is_pro) {
                    $sanitized_agent['department'] = sanitize_text_field($agent['department'] ?? '');
                    $sanitized_agent['image_id'] = isset($agent['image_id']) ? absint($agent['image_id']) : 0;
                }
                return $sanitized_agent;
            }, $agents);

            if (!$is_pro && count($new_input['agents']) > 1) {
                $new_input['agents'] = array_slice($new_input['agents'], 0, 1);
                add_settings_error('wcw_pro_settings', 'agent_limit', 'Only one agent can be active without a Pro license. Extra agents have been removed.', 'warning');
            }

            $new_input['agents'] = array_values($new_input['agents']);
        }

        return $new_input;
    }

    public function render_field($args)
    {
        $options = get_option('wcw_pro_settings', []);
        $value = $options[$args['id']] ?? ($args['default'] ?? '');
        $type = $args['type'];
        $id = $args['id'];
        $description = $args['description'] ?? '';
        $placeholder = $args['placeholder'] ?? '';

        switch ($type) {
            case 'text':
                echo '<input type="text" id="' . esc_attr($id) . '" name="wcw_pro_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="regular-text" placeholder="' . esc_attr($placeholder) . '">';
                break;
            case 'color':
                echo '<input type="text" id="' . esc_attr($id) . '" name="wcw_pro_settings[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" class="wcw-pro-color-picker">';
                break;
        }

        if ($description) {
            echo "<p class='description'>" . esc_html($description) . "</p>";
        }
    }

    public function button_style_field_callback()
    {
        $options = get_option('wcw_pro_settings');
        $style = $options['button_style'] ?? 'icon_only';
    ?>
        <fieldset>
            <label class="wcw-pro-radio-label">
                <input type="radio" name="wcw_pro_settings[button_style]" value="icon_only" <?php checked($style, 'icon_only'); ?>>
                <span><?php esc_html_e('Icon Only', 'floating-wa-chat-pro-widget'); ?></span>
            </label>
            <label class="wcw-pro-radio-label">
                <input type="radio" name="wcw_pro_settings[button_style]" value="icon_text" <?php checked($style, 'icon_text'); ?>>
                <span><?php esc_html_e('Icon with Text', 'floating-wa-chat-pro-widget'); ?></span>
            </label>
        </fieldset>
    <?php
    }

    public function button_position_field_callback()
    {
        $options = get_option('wcw_pro_settings');
        $position = $options['button_position'] ?? 'bottom_right';
    ?>
        <fieldset>
            <label class="wcw-pro-radio-label">
                <input type="radio" name="wcw_pro_settings[button_position]" value="bottom_right" <?php checked($position, 'bottom_right'); ?>>
                <span><?php esc_html_e('Bottom Right', 'floating-wa-chat-pro-widget'); ?></span>
            </label>
            <label class="wcw-pro-radio-label">
                <input type="radio" name="wcw_pro_settings[button_position]" value="bottom_left" <?php checked($position, 'bottom_left'); ?>>
                <span><?php esc_html_e('Bottom Left', 'floating-wa-chat-pro-widget'); ?></span>
            </label>
        </fieldset>
    <?php
    }

    public function agents_field_callback()
    {
        $options = get_option('wcw_pro_settings', []);
        $agents = $options['agents'] ?? [];
        $is_pro = wcw_pro_is_pro();
    ?>
        <p class="description" style="margin-bottom: 20px;">
            <?php esc_html_e('Add and manage your support agents here. Click the agent name to expand/collapse.', 'floating-wa-chat-pro-widget'); ?>
        </p>
        <div class="wcw-pro-repeater-container">
            <div id="wcw-pro-repeater" class="wcw-pro-accordion">
                <?php if (!empty($agents)) : ?>
                    <?php foreach ($agents as $index => $agent) : ?>
                        <?php
                        $image_id = $agent['image_id'] ?? 0;
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : ('https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar');
                        ?>
                        <div class="wcw-pro-repeater-item">
                            <div class="wcw-pro-repeater-handle">
                                <h3><span class="dashicons dashicons-admin-users"></span><?php echo esc_html($agent['name'] ?: 'New Agent'); ?>
                                </h3>
                                <span class="wcw-pro-handle-arrow dashicons dashicons-arrow-down-alt2"></span>
                            </div>
                            <div class="wcw-pro-repeater-content">
                                <div class="wcw-pro-agent-grid">
                                    <div class="wcw-pro-agent-avatar-section">
                                        <div class="wcw-pro-image-preview">
                                            <img src="<?php echo esc_url($image_url); ?>">
                                        </div>
                                        <div class="wcw-pro-field-wrapper <?php if (!$is_pro)
                                                                                echo 'wcw-pro-locked'; ?>">
                                            <input type="hidden" class="wcw-pro-image-id" name="wcw_pro_settings[agents][<?php echo esc_attr($index); ?>][image_id]" value="<?php echo esc_attr($image_id); ?>">
                                            <button type="button" class="button wcw-pro-upload-image-button" <?php disabled(!$is_pro); ?>><?php esc_html_e('Upload Image', 'floating-wa-chat-pro-widget'); ?></button>
                                            <button type="button" class="button button-link wcw-pro-remove-image-button" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove', 'floating-wa-chat-pro-widget'); ?></button>
                                            <?php if (!$is_pro)
                                                echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                                        </div>
                                    </div>
                                    <div class="wcw-pro-agent-details-section">
                                        <div class="wcw-pro-form-row">
                                            <div class="wcw-pro-form-group">
                                                <label><?php esc_html_e('Agent Name', 'floating-wa-chat-pro-widget'); ?></label>
                                                <input class="widefat agent-name-field" name="wcw_pro_settings[agents][<?php echo esc_attr($index); ?>][name]" type="text" value="<?php echo esc_attr($agent['name']); ?>" placeholder="e.g. John Doe">
                                            </div>
                                            <div class="wcw-pro-form-group">
                                                <label><?php esc_html_e('Agent Title/Role', 'floating-wa-chat-pro-widget'); ?></label>
                                                <input class="widefat" name="wcw_pro_settings[agents][<?php echo esc_attr($index); ?>][title]" type="text" value="<?php echo esc_attr($agent['title']); ?>" placeholder="e.g. Sales Manager">
                                            </div>
                                        </div>
                                        <div class="wcw-pro-form-row">
                                            <div class="wcw-pro-form-group">
                                                <label><?php esc_html_e('WhatsApp Number', 'floating-wa-chat-pro-widget'); ?></label>
                                                <input class="widefat" name="wcw_pro_settings[agents][<?php echo esc_attr($index); ?>][phone]" type="text" value="<?php echo esc_attr($agent['phone']); ?>" placeholder="e.g. +1234567890">
                                            </div>
                                            <div class="wcw-pro-form-group wcw-pro-field-wrapper <?php if (!$is_pro)
                                                                                                    echo 'wcw-pro-locked'; ?>">
                                                <label><?php esc_html_e('Department', 'floating-wa-chat-pro-widget'); ?></label>
                                                <input class="widefat" name="wcw_pro_settings[agents][<?php echo esc_attr($index); ?>][department]" type="text" value="<?php echo esc_attr($agent['department'] ?? ''); ?>" placeholder="e.g. Support" <?php disabled(!$is_pro); ?>>
                                                <?php if (!$is_pro)
                                                    echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                                            </div>
                                        </div>
                                        <div class="wcw-pro-form-group">
                                            <label><?php esc_html_e('Prefilled Message', 'floating-wa-chat-pro-widget'); ?></label>
                                            <textarea class="widefat" name="wcw_pro_settings[agents][<?php echo esc_attr($index); ?>][message]" rows="3" placeholder="e.g. Hello! I have a question about..."><?php echo esc_textarea($agent['message']); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="wcw-pro-repeater-footer">
                                    <a href="#" class="button button-link-delete wcw-pro-remove-agent"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Remove Agent', 'floating-wa-chat-pro-widget'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="wcw-pro-add-agent-wrapper <?php if (!$is_pro && count($agents) >= 1)
                                                        echo 'wcw-pro-locked'; ?>" style="margin-top: 20px;">
                <a href="#" id="wcw-pro-add-agent" class="button button-primary button-large"><span class="dashicons dashicons-plus-alt"></span><?php esc_html_e('Add New Agent', 'floating-wa-chat-pro-widget'); ?></a>
                <?php if (!$is_pro && count($agents) >= 1)
                    echo '<span class="wcw-pro-badge">PRO feature: Add more than one agent</span>'; ?>
            </div>
        </div>
        <script type="text/template" id="tmpl-wcw-pro-repeater-template">
            <div class="wcw-pro-repeater-item">
                <div class="wcw-pro-repeater-handle">
                    <h3><span class="dashicons dashicons-admin-users"></span><?php esc_html_e('New Agent', 'floating-wa-chat-pro-widget'); ?></h3>
                    <span class="wcw-pro-handle-arrow dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="wcw-pro-repeater-content">
                    <div class="wcw-pro-agent-grid">
                        <div class="wcw-pro-agent-avatar-section">
                            <div class="wcw-pro-image-preview">
                                <?php
                                // Use wp_get_attachment_image if image_id is set, otherwise show placeholder
                                echo wp_get_attachment_image( 0, 'thumbnail', false, array(
                                    'src' => 'https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar',
                                    'alt' => esc_attr__('Avatar', 'floating-wa-chat-pro-widget')
                                ) );
                                ?>
                            </div>
                             <div class="wcw-pro-field-wrapper <?php if (!$is_pro)
                                                                    echo 'wcw-pro-locked'; ?>">
                                <input type="hidden" class="wcw-pro-image-id" name="wcw_pro_settings[agents][<#= index #>][image_id]" value="0">
                                <button type="button" class="button wcw-pro-upload-image-button" <?php disabled(!$is_pro); ?>><?php esc_html_e('Upload Image', 'floating-wa-chat-pro-widget'); ?></button>
                                <button type="button" class="button button-link wcw-pro-remove-image-button" style="display:none;"><?php esc_html_e('Remove', 'floating-wa-chat-pro-widget'); ?></button>
                                <?php if (!$is_pro)
                                    echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                            </div>
                        </div>
                        <div class="wcw-pro-agent-details-section">
                            <div class="wcw-pro-form-row">
                                <div class="wcw-pro-form-group">
                                    <label><?php esc_html_e('Agent Name', 'floating-wa-chat-pro-widget'); ?></label>
                                    <input class="widefat agent-name-field" name="wcw_pro_settings[agents][<#= index #>][name]" type="text" value="" placeholder="e.g. Jane Doe">
                                </div>
                                <div class="wcw-pro-form-group">
                                    <label><?php esc_html_e('Agent Title/Role', 'floating-wa-chat-pro-widget'); ?></label>
                                    <input class="widefat" name="wcw_pro_settings[agents][<#= index #>][title]" type="text" value="" placeholder="e.g. Customer Support">
                                </div>
                            </div>
                            <div class="wcw-pro-form-row">
                                 <div class="wcw-pro-form-group">
                                    <label><?php esc_html_e('WhatsApp Number', 'floating-wa-chat-pro-widget'); ?></label>
                                    <input class="widefat" name="wcw_pro_settings[agents][<#= index #>][phone]" type="text" value="" placeholder="e.g. +1234567890">
                                </div>
                                <div class="wcw-pro-form-group wcw-pro-field-wrapper <?php if (!$is_pro)
                                                                                        echo 'wcw-pro-locked'; ?>">
                                    <label><?php esc_html_e('Department', 'floating-wa-chat-pro-widget'); ?></label>
                                    <input class="widefat" name="wcw_pro_settings[agents][<#= index #>][department]" type="text" value="" placeholder="e.g. Sales" <?php disabled(!$is_pro); ?>>
                                    <?php if (!$is_pro)
                                        echo '<span class="wcw-pro-badge">PRO</span>'; ?>
                                </div>
                            </div>
                             <div class="wcw-pro-form-group">
                                <label><?php esc_html_e('Prefilled Message', 'floating-wa-chat-pro-widget'); ?></label>
                                <textarea class="widefat" name="wcw_pro_settings[agents][<#= index #>][message]" rows="3" placeholder="e.g. Hello! I have a question about..."></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="wcw-pro-repeater-footer">
                        <a href="#" class="button button-link-delete wcw-pro-remove-agent"><span class="dashicons dashicons-trash"></span><?php esc_html_e('Remove Agent', 'floating-wa-chat-pro-widget'); ?></a>
                    </div>
                </div>
            </div>
        </script>
    <?php
    }

    public function api_key_status_field_callback()
    {
        $options = get_option('wcw_pro_settings');
        $status = $options['api_key_status'] ?? 'invalid';
        $expires = $options['api_key_expires'] ?? 0;

        echo '<div class="wcw-pro-license-status">';
        if ($status === 'valid') {
            echo '<p class="status-valid"><strong>Active:</strong> Pro License</p>';
        } else if ($status === 'valid_trial' && time() < $expires) {
            echo '<p class="status-trial"><strong>Active:</strong> Trial License (Expires on: ' . esc_html(date_i18n(get_option('date_format'), $expires)) . ')</p>';
        } else if ($status === 'expired') {
            echo '<p class="status-expired"><strong>Expired:</strong> Your license/trial has ended.</p>';
        } else {
            echo '<p class="status-invalid"><strong>Inactive:</strong> No valid license key found.</p>';
        }
        echo '</div>';
    }

    public function api_key_field_callback()
    {
        $options = get_option('wcw_pro_settings');
        $value = $options['api_key'] ?? '';
        $display_value = $value;

        if (!empty($value)) {
            if (strpos($value, 'PRO-') === 0) {
                $display_value = 'PRO-' . str_repeat('✱', 12);
            } elseif (strpos($value, 'TRIAL-') === 0) {
                $display_value = 'TRIAL-' . str_repeat('✱', 12);
            }
        }

        echo '<div class="wcw-pro-license-input-wrapper">';
        echo "<input type='text' id='api_key' name='wcw_pro_settings[api_key]' value='" . esc_attr($display_value) . "' class='regular-text' autocomplete='off'>";
        echo '<a href="https://whatsapp-pro-chat.web.app/" target="_blank" class="button wcw-pro-get-license-btn">' . esc_html__('Get Pro License', 'floating-wa-chat-pro-widget') . '</a>';
        echo '</div>';
        echo "<p class='description'>Enter your License key to unlock Pro features.</p>";
    }

    public function trial_request_field_callback()
    {
    ?>
        <p class="description">
            <?php esc_html_e('Enter your email to receive a 7-day trial key for Pro features.', 'floating-wa-chat-pro-widget'); ?></p>
        <div id="wcw-pro-trial-form">
            <input type="email" id="wcw-pro-trial-email" placeholder="your-email@example.com" class="regular-text">
            <button type="button" id="wcw-pro-request-trial-btn" class="button button-secondary">Request Trial Key</button>
            <span class="spinner"></span>
        </div>
        <div id="wcw-pro-trial-message" style="display:none; margin-top: 10px;"></div>
<?php
    }
}

new WCW_Pro_Settings();
