<?php
// ✅ Church Taxonomy Meta Management with Debug Logging

add_action('church_edit_form_fields', 'car_edit_church_meta_fields', 10, 2);
add_action('edited_church', 'car_save_church_meta_fields', 10, 2);

function car_edit_church_meta_fields($term) {
    global $wpdb;

    $term_id = $term->term_id;
    $table = $wpdb->prefix . 'car_churches';

    error_log("🔍 Editing Church Term ID: $term_id");
    error_log("🔍 Using Table: $table");

    $church = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE term_id = %d", $term_id)
    );

    error_log("🔍 Church Row: " . print_r($church, true));

    $pastor_name = esc_attr($church->pastor_name ?? '');
    $pastor_email = esc_attr($church->pastor_email ?? '');
    $phone_number = esc_attr($church->phone_number ?? '');
    $website = esc_attr($church->website ?? '');
    $address = esc_attr($church->address ?? '');

    ?>
    <tr class="form-field">
        <th scope="row"><label for="pastor_name">Pastor Name</label></th>
        <td><input name="pastor_name" id="pastor_name" type="text" value="<?php echo $pastor_name; ?>" class="regular-text" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="pastor_email">Pastor Email</label></th>
        <td><input name="pastor_email" id="pastor_email" type="email" value="<?php echo $pastor_email; ?>" class="regular-text" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="phone_number">Phone Number</label></th>
        <td><input name="phone_number" id="phone_number" type="text" value="<?php echo $phone_number; ?>" class="regular-text" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="website">Website</label></th>
        <td><input name="website" id="website" type="text" value="<?php echo $website; ?>" class="regular-text" /></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="address">Address</label></th>
        <td><textarea name="address" id="address" rows="3" class="large-text"><?php echo $address; ?></textarea></td>
    </tr>
    <?php
}

function car_save_church_meta_fields($term_id) {
    global $wpdb;

    $table = $wpdb->prefix . 'car_churches';

    $pastor_name   = sanitize_text_field($_POST['pastor_name'] ?? '');
    $pastor_email  = sanitize_email($_POST['pastor_email'] ?? '');
    $phone_number  = sanitize_text_field($_POST['phone_number'] ?? '');
    $website       = esc_url_raw($_POST['website'] ?? '');
    $address       = sanitize_textarea_field($_POST['address'] ?? '');

    $existing = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE term_id = %d", $term_id)
    );

    error_log("💾 Saving Church Term ID: $term_id to table: $table");
    error_log("💾 Data: " . json_encode(compact('pastor_name', 'pastor_email', 'phone_number', 'website', 'address')));

    if ($existing) {
        $wpdb->update($table,
            compact('pastor_name', 'pastor_email', 'phone_number', 'website', 'address'),
            ['term_id' => $term_id]
        );
    } else {
        $wpdb->insert($table,
            compact('term_id', 'pastor_name', 'pastor_email', 'phone_number', 'website', 'address')
        );
    }
}
?>
