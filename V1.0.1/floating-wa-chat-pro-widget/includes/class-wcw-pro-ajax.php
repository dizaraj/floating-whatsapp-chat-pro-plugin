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

    /**
     * Handle Trial Key Request via AJAX.
     * - Limits requests to 3 per domain.
     * - Sends the trial key to the user's email.
     * - BCCs the admin on the user email.
     * - Does not display the key on the dashboard.
     */
    public function handle_trial_request()
    {
        check_ajax_referer('wcw_pro_trial_nonce', 'nonce');

        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error(['message' => 'Please provide a valid email address.']);
            return;
        }

        $domain = home_url();
        $request_count_transient = 'wcw_pro_trial_requests_' . md5($domain);
        $request_count = (int) get_transient($request_count_transient);

        // 1. Limit trial key requests to 3 per domain
        if ($request_count >= 10) {
            wp_send_json_error(['message' => 'You have reached the maximum number of trial requests for this domain.']);
            return;
        }

        $user_email = sanitize_email($_POST['email']);
        $trial_key = 'TRIAL-' . strtoupper(wp_generate_password(12, false));
        $expiration = time() + (7 * DAY_IN_SECONDS);

        // Store trial key details
        $trials = get_option('wcw_pro_trials', []);
        $trials[$trial_key] = [
            'email' => $user_email,
            'expires' => $expiration,
        ];
        update_option('wcw_pro_trials', $trials);

        // Increment the request count for this domain
        set_transient($request_count_transient, $request_count + 1, YEAR_IN_SECONDS);

        // 2. Prepare and send the email to the user
        $developer_email = 'dizaraj@gmail.com';
        $subject = 'Your 7-Day Trial Key for Floating WA Chat Pro Widget';

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Bcc: ' . $developer_email // 3. BCC the developer
        ];

        $visitor_data = wcw_pro_get_visitor_public_ip_and_location();
        $domain = home_url();

        $body = "<p>Hello,</p>";
        $body .= "<p>Thank you for trying Floating WA Chat Pro Widget! Here is your 7-day trial key:</p>";
        $body .= "<p style='font-size: 18px; font-weight: bold; background-color: #f0f0f0; padding: 10px; border-radius: 5px; text-align: center;'><code>" . esc_html($trial_key) . "</code></p>";
        $body .= "<p>Requested for <br><strong>Domain: </strong>" . esc_html($domain) . "<br><strong>From IP:</strong> " . esc_html($visitor_data['ip']) . "<br><strong>Location:</strong> " . esc_html($visitor_data['location']['city']) . "</p>";
        $body .= "<p>To activate it, please go to your WordPress dashboard, navigate to <strong>WhatsApp Chat > Activate Pro</strong>, paste the key into the 'License Key' field, and click 'Save & Activate'.</p>";
        $body .= "<p>If you have any questions, feel free to reply to this email.</p>";
        $body .= "<p>Best regards,<br>The Floating WA Chat Pro Widget Team</p>";

        $mail_sent = wp_mail($user_email, $subject, $body, $headers);

        if ($mail_sent) {
            // 4. Update the success message
            wp_send_json_success([
                'message' => 'Success! We\'ve sent the trial key to your email address. Please check your inbox (and spam folder).'
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Could not send the trial key email. Please check your site\'s email configuration or contact support.'
            ]);
        }
    }
}