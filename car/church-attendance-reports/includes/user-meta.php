<?php
/**
 * Additional user meta fields and helpers.
 *
 * This file extends the existing user meta functionality by adding a mobile
 * phone field to user profiles. Administrators can set this field when
 * editing a user profile. The mobile phone number is used by the ClearStream
 * reminder system to send SMS reminders to church reporters and admins.
 */

// Assign a church to a user (existing helper).
function car_assign_church_to_user($user_id, $church_id) {
    update_user_meta($user_id, 'assigned_church', intval($church_id));
}

// Retrieve a user's assigned church.
function car_get_user_church($user_id) {
    return get_user_meta($user_id, 'assigned_church', true);
}

/**
 * Display additional fields on the user profile edit screen.
 *
 * This hook outputs an input field for the user's mobile phone number.
 *
 * @param WP_User $user The user object.
 */
function car_show_mobile_phone_field($user) {
    ?>
    <h3><?php esc_html_e('Additional Contact Information', 'church-attendance-reports'); ?></h3>
    <table class="form-table">
        <tr>
            <th><label for="mobile_phone"><?php esc_html_e('Mobile Phone', 'church-attendance-reports'); ?></label></th>
            <td>
                <input type="text" name="mobile_phone" id="mobile_phone" value="<?php echo esc_attr(get_user_meta($user->ID, 'mobile_phone', true)); ?>" class="regular-text" />
                <p class="description"><?php esc_html_e('Enter the user\'s mobile phone number. This is used for attendance reminders.', 'church-attendance-reports'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'car_show_mobile_phone_field');
add_action('edit_user_profile', 'car_show_mobile_phone_field');

/**
 * Save the mobile phone field when a profile is updated.
 *
 * @param int $user_id ID of the user whose profile is being updated.
 * @return bool True on success, false on failure.
 */
function car_save_mobile_phone_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }
    if (isset($_POST['mobile_phone'])) {
        update_user_meta($user_id, 'mobile_phone', sanitize_text_field($_POST['mobile_phone']));
    }
    return true;
}
add_action('personal_options_update', 'car_save_mobile_phone_field');
add_action('edit_user_profile_update', 'car_save_mobile_phone_field');