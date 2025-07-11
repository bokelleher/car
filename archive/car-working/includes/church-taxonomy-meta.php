<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Add custom fields to the Church taxonomy
 */
function car_add_church_meta_fields($term) {
    $term_id = $term->term_id;
    $fields = [
        'pastor_name' => 'Pastor Name',
        'pastor_email' => 'Pastor Email',
        'phone_number' => 'Phone Number',
        'website' => 'Website',
        'address' => 'Address'
    ];
    foreach ($fields as $key => $label) {
        $$key = get_term_meta($term_id, $key, true);
    }
    ?>
    <tr class="form-field">
        <th scope="row" valign="="top"><label for="pastor_name">Pastor Name</label></th>
        <td><input type="text" name="pastor_name" id="pastor_name" value="<?php echo esc_attr($pastor_name); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="pastor_email">Pastor Email</label></th>
        <td><input type="email" name="pastor_email" id="pastor_email" value="<?php echo esc_attr($pastor_email); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="phone_number">Phone Number</label></th>
        <td><input type="text" name="phone_number" id="phone_number" value="<?php echo esc_attr($phone_number); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="website">Website</label></th>
        <td><input type="url" name="website" id="website" value="<?php echo esc_attr($website); ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="address">Address</label></th>
        <td><textarea name="address" id="address"><?php echo esc_textarea($address); ?></textarea></td>
    </tr>
    <?php
}
add_action('church_edit_form_fields', 'car_add_church_meta_fields');

/**
 * Save custom meta fields for Church taxonomy
 */
function car_save_church_meta_fields($term_id) {
    $fields = ['pastor_name', 'pastor_email', 'phone_number', 'website', 'address'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_term_meta($term_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('edited_church', 'car_save_church_meta_fields');
