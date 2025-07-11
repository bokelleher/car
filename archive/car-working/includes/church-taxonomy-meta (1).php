<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Adds and saves custom fields (meta) for the Church taxonomy.
 * Uses custom database table `car_churches` instead of term_meta.
 */

// Add fields to Edit Church screen
function car_edit_church_meta_fields($term) {
    global $wpdb;
    $term_id = (int) $term->term_id;
    $table = $wpdb->prefix . 'car_churches';

    $church = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE term_id = %d", $term_id),
        ARRAY_A
    );

    $pastor_name = isset($church['pastor_name']) ? esc_attr($church['pastor_name']) : '';
    $pastor_email = isset($church['pastor_email']) ? esc_attr($church['pastor_email']) : '';
    $phone_number = isset($church['phone_number']) ? esc_attr($church['phone_number']) : '';
    $website = isset($church['website']) ? esc_attr($church['website']) : '';
    $address = isset($church['address']) ? esc_textarea($church['address']) : '';
    ?>
    <tr class="form-field">
        <th scope="row"><label for="pastor_name">Pastor Name</label></th>
        <td><input name="pastor_name" id="pastor_name" type="text" value="<?php echo $pastor_name; ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="pastor_email">Pastor Email</label></th>
        <td><input name="pastor_email" id="pastor_email" type="email" value="<?php echo $pastor_email; ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="phone_number">Phone Number</label></th>
        <td><input name="phone_number" id="phone_number" type="text" value="<?php echo $phone_number; ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="website">Website</label></th>
        <td><input name="website" id="website" type="url" value="<?php echo $website; ?>" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="address">Address</label></th>
        <td><textarea name="address" id="address"><?php echo $address; ?></textarea></td>
    </tr>
    <?php
}
add_action('church_edit_form_fields', 'car_edit_church_meta_fields');

// Save fields from Edit Church screen
function car_save_church_meta_fields($term_id) {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'car_churches';

    $data = [
        'pastor_name' => sanitize_text_field($_POST['pastor_name']),
        'pastor_email' => sanitize_email($_POST['pastor_email']),
        'phone_number' => sanitize_text_field($_POST['phone_number']),
        'website' => esc_url_raw($_POST['website']),
        'address' => sanitize_textarea_field($_POST['address']),
    ];

    $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE term_id = %d", $term_id));
    if ($exists) {
        $wpdb->update($table, $data, ['term_id' => $term_id]);
    } else {
        $data['term_id'] = $term_id;
        $wpdb->insert($table, $data);
    }
}
add_action('edited_church', 'car_save_church_meta_fields');