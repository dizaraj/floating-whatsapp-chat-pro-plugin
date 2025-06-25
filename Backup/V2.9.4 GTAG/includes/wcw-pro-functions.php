<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checks if the pro version is active.
 * @return bool
 */
function wcw_pro_is_pro()
{
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


/**
 * Get visitor's public IP.
 * @return string
 */
function wcw_pro_get_visitor_ip()
{
    $ip = '';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = sanitize_text_field(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
    }
    return $ip;
}


/**
 * Get visitor's public IP and location details using an external API.
 * @return array
 */
function wcw_pro_get_visitor_public_ip_and_location()
{
    $ip = wcw_pro_get_visitor_ip();

    if (empty($ip)) {
        return ['ip' => 'N/A', 'location' => ['city' => 'Unknown', 'country' => 'Unknown']];
    }

    $response = wp_safe_remote_get("http://ip-api.com/json/{$ip}");

    if (is_array($response) && !is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($data && $data['status'] === 'success') {
            return [
                'ip' => sanitize_text_field($data['query']),
                'location' => [
                    'city' => sanitize_text_field($data['city']),
                    'country' => sanitize_text_field($data['country'])
                ]
            ];
        }
    }

    return ['ip' => $ip, 'location' => ['city' => 'Unknown', 'country' => 'Unknown']];
}


/**
 * Get essential server and WordPress info.
 * @return array
 */
function wcw_pro_get_essential_info()
{
    global $wp_version;
    $visitor_data = wcw_pro_get_visitor_public_ip_and_location();
    return [
        'domain' => home_url(),
        'server_ip' => $_SERVER['SERVER_ADDR'] ?? 'N/A',
        'your_public_ip' => $visitor_data['ip'],
        'php_version' => PHP_VERSION,
        'wp_version' => $wp_version,
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
    ];
}


/**
 * Create or update asset files.
 */
function wcw_pro_create_asset_files()
{
    $plugin_dir = plugin_dir_path(__DIR__);
    wp_mkdir_p($plugin_dir . 'assets/css');
    wp_mkdir_p($plugin_dir . 'assets/js');

    $css_content = '/* Main Frontend Styles */.wcw-pro-container{position:fixed;bottom:25px;z-index:1000}.wcw-pro-bubble{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.2);transition:all .2s ease-in-out}.wcw-pro-bubble:hover{transform:scale(1.1)}.wcw-pro-bubble-with-text{width:auto;border-radius:30px;padding:0 20px 0 15px;height:60px}.wcw-pro-bubble-with-text .wcw-pro-icon{margin-right:8px}.wcw-pro-bubble-text{font-size:16px;font-weight:700;white-space:nowrap}.wcw-pro-popup{position:absolute;bottom:80px;width:320px;background:#fff;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,.2);opacity:0;visibility:hidden;transform:scale(.95) translateY(10px);transition:all .2s ease-in-out}.wcw-pro-popup.open{opacity:1;visibility:visible;transform:scale(1) translateY(0)}.wcw-pro-header{padding:15px;border-top-left-radius:10px;border-top-right-radius:10px;display:flex;justify-content:space-between;align-items:center}.wcw-pro-title{font-weight:700}.wcw-pro-close{font-size:24px;cursor:pointer;line-height:1;opacity:.8}.wcw-pro-close:hover{opacity:1}.wcw-pro-body{padding:10px;max-height:400px;overflow-y:auto}.wcw-pro-agent{display:flex;align-items:center;padding:10px;text-decoration:none;color:#333;border-radius:8px;transition:background-color .2s}.wcw-pro-agent:hover{background-color:#f5f5f5}.wcw-pro-agent-avatar{margin-right:15px;width:50px;height:50px}.wcw-pro-agent-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%}.wcw-pro-agent-details{display:flex;flex-direction:column}.wcw-pro-agent-name{font-weight:700}.wcw-pro-agent-title{font-size:.9em;color:#777}';
    file_put_contents($plugin_dir . 'assets/css/style.css', $css_content);

    $admin_css_content = '/* Admin Settings Styles */.wcw-pro-settings-wrap h1{margin-bottom:20px}.wcw-pro-settings-wrap .wcw-pro-settings-content{display:flex;gap:30px;margin-top:0}.wcw-pro-settings-main{flex:1;min-width:0}.wcw-pro-settings-sidebar{flex-shrink:0;width:300px}.wcw-pro-sidebar-box{background:#fff;padding:20px;border:1px solid #c3c4c7;border-radius:4px;margin-bottom:20px}.wcw-pro-sidebar-box h3{margin-top:0;padding-bottom:10px;border-bottom:1px solid #ddd;display:flex;align-items:center;gap:8px}.wcw-pro-nav-tab-wrapper{margin:0 -20px 20px;padding:0 20px;border-bottom:1px solid #c3c4c7}.wcw-pro-nav-tab-wrapper .nav-tab{border:1px solid #c3c4c7;border-bottom:none;background:#f0f0f1;border-top-left-radius:3px;border-top-right-radius:3px;padding:8px 16px;margin:0 5px -1px 0}.wcw-pro-nav-tab-wrapper .nav-tab-active{background:#fff;border-color:#c3c4c7;border-bottom-color:#fff}.wcw-pro-tab-content{display:none;padding-top:20px}.wcw-pro-tab-content.active{display:block}.wcw-pro-settings-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px}.wcw-pro-settings-card{background:#fff;border:1px solid #e0e0e0;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.04); margin-bottom: 20px}.wcw-pro-card-header{padding:15px 20px;border-bottom:1px solid #e0e0e0;background:#fafafa}.wcw-pro-card-header h3{margin:0;font-size:16px;display:flex;align-items:center;gap:10px;color: #f30000}.wcw-pro-card-body{padding:20px}.wcw-pro-form-group{margin-bottom:15px}.wcw-pro-form-group:last-child{margin-bottom:0}.wcw-pro-form-group label{display:block;margin-bottom:5px;font-weight:600}.wcw-pro-radio-label{margin-right:20px;display:inline-flex;align-items:center;gap:5px}/* Repeater / Agent Styles */.wcw-pro-repeater-item{border:1px solid #e0e0e0;border-radius:4px;margin-bottom:15px;background:#fff;box-shadow:0 1px 1px rgba(0,0,0,.04);overflow:hidden}.wcw-pro-repeater-handle{display:flex;justify-content:space-between;align-items:center;padding:15px 20px;background:#fafafa;border-bottom:1px solid #e0e0e0;cursor:pointer;transition:background .2s}.wcw-pro-repeater-handle:hover{background:#f5f5f5}.wcw-pro-repeater-handle h3{margin:0;font-size:16px;display:flex;align-items:center;gap:10px; color: #f30000}.wcw-pro-repeater-handle .wcw-pro-handle-arrow{transition:transform .2s;font-size:20px}.wcw-pro-repeater-item.open>.wcw-pro-repeater-handle .wcw-pro-handle-arrow{transform:rotate(180deg)}.wcw-pro-repeater-content{display:none;padding:20px}.wcw-pro-agent-grid{display:grid;grid-template-columns:150px 1fr;gap:30px;align-items:flex-start}.wcw-pro-agent-avatar-section{text-align:center}.wcw-pro-image-preview{width:100px;height:100px;border:2px solid #ddd;background:#f0f0f1;margin:0 auto 15px;border-radius:50%;overflow:hidden}.wcw-pro-image-preview img{width:100%;height:100%;object-fit:cover}.wcw-pro-agent-avatar-section .button{margin-bottom:5px}.wcw-pro-agent-avatar-section .wcw-pro-remove-image-button{color:#b32d2e}.wcw-pro-agent-details-section{display:flex;flex-direction:column;gap:15px}.wcw-pro-form-row{display:flex;gap:20px}.wcw-pro-form-row .wcw-pro-form-group{flex:1}.wcw-pro-repeater-footer{margin-top:20px;text-align:right}.wcw-pro-repeater-footer .wcw-pro-remove-agent{color:#b32d2e;text-decoration:none;display:inline-flex;align-items:center;gap:5px}.wcw-pro-add-agent-wrapper .button-large{font-size:16px;height:46px;line-height:44px;padding:0 20px;display:inline-flex;align-items:center;gap:8px}/* Locked Pro Fields */.wcw-pro-field-wrapper{position:relative}.wcw-pro-field-wrapper.wcw-pro-locked{opacity:.6;pointer-events:none}.wcw-pro-badge{position:absolute;top:-10px;right:-10px;background:#ffba00;color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;text-transform:uppercase;z-index:1}/* Preview & Info Boxes */#wcw-pro-preview-container{background:#f0f0f1;padding:20px;text-align:center;border:1px dashed #ccc;border-radius:4px}#wcw-pro-preview-bubble{width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;transition:all .2s}#wcw-pro-preview-bubble.has-text{width:auto;padding:0 20px 0 15px;border-radius:30px}#wcw-pro-preview-bubble.has-text #wcw-pro-preview-icon{margin-right:8px}#wcw-pro-preview-text{font-size:16px;font-weight:700;white-space:nowrap;display:none}#wcw-pro-preview-bubble.has-text #wcw-pro-preview-text{display:inline}#wcw-pro-preview-icon path{transition:fill .2s}.wcw-pro-essential-info ul,.wcw-pro-benefits-list ul,.wcw-pro-contact-list{list-style:none;margin:0;padding:0}.wcw-pro-essential-info li{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f1}.wcw-pro-essential-info li:last-child{border-bottom:none}.wcw-pro-benefits-list li,.wcw-pro-contact-list li{display:flex;align-items:center;gap:8px;padding-bottom:5px;color:#3c434a}.wcw-pro-benefits-list li .dashicons{color:#4CAF50}.wcw-pro-license-status p{padding:10px 15px;border-radius:4px;margin:0}.status-valid{background-color:#e0f2e1;border-left:4px solid #4CAF50}.status-trial{background-color:#fff3e0;border-left:4px solid #ff9800}.status-expired,.status-invalid{background-color:#ffeb ee;border-left:4px solid #f44336}#wcw-pro-trial-message{padding:10px 15px;border-radius:4px;border-left-width:4px;border-left-style:solid}#wcw-pro-trial-message.updated{background-color:#e0f2e1;border-color:#4CAF50}#wcw-pro-trial-message.error{background-color:#ffeb ee;border-color:#f44336}.wcw-pro-settings-main .form-table{margin-top:0}.wcw-pro-license-input-wrapper{display:flex;align-items:center;gap:5px}.wcw-pro-get-license-btn{background-color:#4CAF50!important;border-color:#45a049!important;color:#fff!important;font-weight:600!important}.wcw-pro-get-license-btn:hover{background-color:#45a049!important;color:#fff!important}';
    file_put_contents($plugin_dir . 'assets/css/admin.css', $admin_css_content);

    $js_content = "jQuery(document).ready(function(o){\"use strict\";o(\".wcw-pro-bubble\").on(\"click\",function(c){c.stopPropagation(),o(\".wcw-pro-popup\").toggleClass(\"open\")}),o(\".wcw-pro-close\").on(\"click\",function(){o(\".wcw-pro-popup\").removeClass(\"open\")}),o(document).on(\"click\",function(c){!o(c.target).closest(\".wcw-pro-container\").length&&o(\".wcw-pro-popup\").hasClass(\"open\")&&o(\".wcw-pro-popup\").removeClass(\"open\")})});";
    file_put_contents($plugin_dir . 'assets/js/main.js', $js_content);

    $admin_js_content = "jQuery(function(e){\"use strict\";var c;var t=e(\"#wcw-pro-repeater\"),n=e(\"#wcw-pro-add-agent\"),a=wp.template(\"wcw-pro-repeater-template\"),r=e('input[name=\"wcw_pro_settings[bubble_color]\"]'),o=e('input[name=\"wcw_pro_settings[icon_color]\"]'),i=e('input[name=\"wcw_pro_settings[button_style]\"]'),d=e('input[name=\"wcw_pro_settings[button_text]\"]'),l=e(\"#wcw-pro-preview-bubble\"),s=e(\"#wcw-pro-preview-icon\"),p=e(\"#wcw-pro-preview-text\");function u(){var t=e('input[name=\"wcw_pro_settings[button_style]\"]:checked').val(),c=e(\"#button_text_field_group\");\"icon_text\"===t?(c.show(),l.addClass(\"has-text\"),p.text(d.val()||'Need Help!').show()):(c.hide(),l.removeClass(\"has-text\"),p.hide()),w()}function w(){l.css(\"background-color\",r.val()),s.find(\"path\").attr(\"fill\",o.val()),p.css(\"color\",o.val())}function f(){t.children(\".wcw-pro-repeater-item\").each(function(){var t=e(this),c=t.find(\".wcw-pro-repeater-handle\"),n=t.find(\".wcw-pro-repeater-content\");c.off(\"click.wcwpro\").on(\"click.wcwpro\",function(){t.toggleClass(\"open\"),n.slideToggle(200)})})}e(\".wcw-pro-color-picker\").wpColorPicker({change:function(e,t){w()}}),n.on(\"click\",function(c){c.preventDefault();if(e(this).parent().hasClass(\"wcw-pro-locked\"))return;var r={index:t.children(\".wcw-pro-repeater-item\").length};t.append(a(r));var o=t.children(\".wcw-pro-repeater-item\").last();o.addClass(\"open\").find(\".wcw-pro-repeater-content\").slideDown(200),f(),!wcwProData.is_pro&&t.children(\".wcw-pro-repeater-item\").length>=1&&e(this).parent().addClass(\"wcw-pro-locked\")}),t.on(\"click\",\".wcw-pro-remove-agent\",function(c){c.preventDefault(),confirm(\"Are you sure you want to remove this agent?\")&&e(this).closest(\".wcw-pro-repeater-item\").slideUp(300,function(){e(this).remove();var c=e(\".wcw-pro-add-agent-wrapper\");wcwProData.is_pro||t.children(\".wcw-pro-repeater-item\").length<1&&c.removeClass(\"wcw-pro-locked\")})}),t.on(\"keyup\",\".agent-name-field\",function(){var t=e(this).val(),c=e(this).closest(\".wcw-pro-repeater-item\").find(\".wcw-pro-repeater-handle h3\");c.find(\"span\").next().text(t||\"New Agent\")}),t.on(\"click\",\".wcw-pro-upload-image-button\",function(t){if(t.preventDefault(),!e(this).is(\":disabled\")){var n=e(this),a=n.siblings(\".wcw-pro-image-id\"),r=n.closest(\".wcw-pro-agent-avatar-section\").find(\".wcw-pro-image-preview img\"),o=n.siblings(\".wcw-pro-remove-image-button\");c&&c.off(\"select\"),c=wp.media({title:\"Select Agent Image\",button:{text:\"Use this image\"},multiple:!1}).on(\"select\",function(){var e=c.state().get(\"selection\").first().toJSON();a.val(e.id),r.attr(\"src\",e.sizes.thumbnail?e.sizes.thumbnail.url:e.url),o.show()}),c.open()}}),t.on(\"click\",\".wcw-pro-remove-image-button\",function(t){t.preventDefault();var c=e(this),n=c.closest(\".wcw-pro-agent-avatar-section\").find(\".wcw-pro-image-preview img\");c.hide().siblings(\".wcw-pro-image-id\").val(\"0\"),n.attr(\"src\",\"https://placehold.co/100x100/EFEFEF/AAAAAA&text=Avatar\")}),e(\".wcw-pro-nav-tab-wrapper .nav-tab\").on(\"click\",function(t){t.preventDefault();var c=e(this);c.addClass(\"nav-tab-active\").siblings().removeClass(\"nav-tab-active\");var n=c.attr(\"href\");e(\".wcw-pro-tab-content\").removeClass(\"active\"),e(n).addClass(\"active\")}),e('input[name=\"wcw_pro_settings[button_style]\"]').on(\"change\",u),d.on(\"keyup\",function(){p.text(e(this).val())}),u(),w(),f();var g=e(\"#api_key\"),h=g.val();g.on(\"focus\",function(){e(this).val().indexOf(\"✱\")!==-1&&e(this).val(\"\")}),g.on(\"blur\",function(){\"\"===e(this).val()&&e(this).val(h)}),e(\"#wcw-pro-request-trial-btn\").on(\"click\",function(t){t.preventDefault();var c=e(this),n=e(\"#wcw-pro-trial-email\"),a=n.val(),r=c.siblings(\".spinner\"),o=e(\"#wcw-pro-trial-message\");return a?(r.addClass(\"is-active\"),c.prop(\"disabled\",!0),o.hide(),void e.ajax({url:ajaxurl,type:\"POST\",data:{action:\"wcw_pro_request_trial\",email:a,nonce:wcwProData.nonce},success:function(t){t.success?(o.html(t.data.message).removeClass(\"error\").addClass(\"updated notice notice-success\").show(),n.val(\"\")):o.html(t.data.message).addClass(\"error notice notice-error\").show()},error:function(){o.html(\"An unknown error occurred. Please try again.\").addClass(\"error notice notice-error\").show()},complete:function(){r.removeClass(\"is-active\"),c.prop(\"disabled\",!1)}})):(o.html(\"Please enter your email address.\").addClass(\"error notice notice-error\").show(),!1)})});";
    file_put_contents($plugin_dir . 'assets/js/admin.js', $admin_js_content);
}
