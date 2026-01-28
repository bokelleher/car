<?php
/**
 * My Account shortcode for Church Attendance Reports.
 *
 * Provides a front-end form that allows a logged-in user to update
 * their email address and mobile phone number. The form displays
 * the user's church affiliation as a read-only field with a tooltip
 * instructing them to contact the site administrator for changes.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the My Account form.
 *
 * @return string HTML for the account page.
 */
function car_my_account_shortcode() {
    // Require login. If not logged in, display a message.
    if (!is_user_logged_in()) {
        return '<div class="car-my-account"><p>Please log in to manage your account.</p></div>';
    }

    $user_id = get_current_user_id();
    $user    = wp_get_current_user();

    // Prepare message feedback variables
    $message  = '';
    $is_error = false;

    // Handle form submission
    if (isset($_POST['car_my_account_submit'])) {
        // Verify nonce for security
        if (!isset($_POST['car_my_account_nonce']) || !wp_verify_nonce($_POST['car_my_account_nonce'], 'car_my_account_update')) {
            $message  = 'Security check failed. Please try again.';
            $is_error = true;
        } else {
            $new_email  = isset($_POST['car_email']) ? sanitize_email($_POST['car_email']) : '';
            $new_mobile = isset($_POST['car_mobile_phone']) ? sanitize_text_field($_POST['car_mobile_phone']) : '';

            // Validate email
            if (!$new_email || !is_email($new_email)) {
                $message  = 'Please enter a valid email address.';
                $is_error = true;
            } else {
                // Ensure the email isn't used by another account
                $existing_id = email_exists($new_email);
                if ($existing_id && intval($existing_id) !== intval($user_id)) {
                    $message  = 'That email address is already in use.';
                    $is_error = true;
                }
            }

            // If there are no validation errors, attempt to update
            if (!$is_error) {
                // Update email only if it has changed
                if (strtolower($new_email) !== strtolower($user->user_email)) {
                    $result = wp_update_user([
                        'ID'         => $user_id,
                        'user_email' => $new_email,
                    ]);
                    if (is_wp_error($result)) {
                        $message  = $result->get_error_message();
                        $is_error = true;
                    }
                }

                // Update mobile phone meta
                if (!$is_error) {
                    update_user_meta($user_id, 'mobile_phone', $new_mobile);
                    $message = 'Account updated successfully.';
                }
            }
        }

        // Refresh user info after update
        $user = wp_get_current_user();
    }

    // Retrieve mobile phone meta
    $mobile_phone = get_user_meta($user_id, 'mobile_phone', true);

    // Determine church affiliation label
    $church_label = '—';
    if (function_exists('car_get_user_church_id')) {
        $church_id = car_get_user_church_id($user_id);
        if ($church_id) {
            $term = get_term($church_id);
            if ($term && !is_wp_error($term)) {
                $church_label = $term->name;
            }
        }
    }

    // Build the form output
    ob_start();
    ?>
    <div class="car-my-account car-card" style="max-width:720px; margin: 0 auto;">
        <h1>My Account</h1>
        <?php if ($message) : ?>
            <div class="car-alert <?php echo $is_error ? 'car-alert-error' : 'car-alert-success'; ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('car_my_account_update', 'car_my_account_nonce'); ?>
            <div class="car-field">
                <label for="car_email">Email</label>
                <input id="car_email" class="car-input" name="car_email" type="email" value="<?php echo esc_attr($user->user_email); ?>" required>
            </div>
            <div class="car-field">
                <label for="car_mobile_phone">Mobile Phone</label>
                <input id="car_mobile_phone" class="car-input" name="car_mobile_phone" type="text" value="<?php echo esc_attr($mobile_phone); ?>" placeholder="+1xxxxxxxxxx">
            </div>
            <div class="car-field">
                <label>Church Affiliation</label>
                <input type="text" class="car-input car-input-disabled" value="<?php echo esc_attr($church_label); ?>" disabled title="To change your church affiliation, email webmaster@etndi.org.">
                <small class="car-help">To change your church affiliation, email webmaster@etndi.org.</small>
            </div>
            <div class="car-field" style="margin-top: 18px;">
                <button type="submit" name="car_my_account_submit" value="1" class="car-btn">Save Changes</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('car_my_account', 'car_my_account_shortcode');