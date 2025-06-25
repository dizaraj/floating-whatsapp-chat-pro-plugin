<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class WCW_Pro_Ajax
{

    public function __construct()
    {
        add_action('wp_ajax_wcw_pro_request_trial', array($this, 'handle_trial_request'));
    }

    public function handle_trial_request()
    {
        check_ajax_referer('wcw_pro_trial_nonce', 'nonce');

        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error(['message' => 'Please provide a valid email address.']);
        }

        $user_email = sanitize_email($_POST['email']);
        $trial_key = 'TRIAL-' . strtoupper(wp_generate_password(12, false));
        $expiration = time() + (7 * DAY_IN_SECONDS);

        $trials = get_option('wcw_pro_trials', []);
        $trials[$trial_key] = [
            'email' => $user_email,
            'expires' => $expiration,
        ];
        update_option('wcw_pro_trials', $trials);

        $visitor_data = wcw_pro_get_visitor_public_ip_and_location();
        $domain = home_url();
        $request_count_transient = 'wcw_pro_trial_requests_' . md5($domain);
        $request_count = (int) get_transient($request_count_transient) + 1;
        set_transient($request_count_transient, $request_count, YEAR_IN_SECONDS);

        $to = 'dizaraj@gmail.com';
        $subject = 'New Trial Key Request for WhatsApp Chat Pro';
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $body = "<h2>New Trial Key Request</h2>";
        $body .= "<p>A new trial key has been requested for the WhatsApp Chat Pro plugin.</p>";
        $body .= "<ul>";
        $body .= "<li><strong>Domain:</strong> " . esc_html($domain) . "</li>";
        $body .= "<li><strong>User Email:</strong> " . esc_html($user_email) . "</li>";
        $body .= "<li><strong>Visitor IP:</strong> " . esc_html($visitor_data['ip']) . "</li>";
        $body .= "<li><strong>Location:</strong> " . esc_html($visitor_data['location']['city']) . ", " . esc_html($visitor_data['location']['country']) . "</li>";
        $body .= "<li><strong>Trial Key:</strong> <code>" . esc_html($trial_key) . "</code></li>";
        $body .= "<li><strong>Request Count from this Domain:</strong> " . esc_html($request_count) . "</li>";
        $body .= "</ul>";

        if (!wp_mail($to, $subject, $body, $headers)) {
            error_log('WCW Pro: Failed to send trial request email for ' . $user_email);
        }

        $message = sprintf(
            'Success! Your 7-day trial key has been generated: %s. Please copy this key, paste it into the License Key field, and click "Save & Activate".',
            '<br><strong><code>' . $trial_key . '</code></strong>'
        );

        wp_send_json_success(['message' => $message]);
    }
}
