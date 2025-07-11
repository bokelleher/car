<?php
// Register Churches taxonomy
function car_register_taxonomy() {
    $labels = [
        'name' => _x('Churches', 'taxonomy general name'),
        'singular_name' => _x('Church', 'taxonomy singular name'),
        'search_items' => __('Search Churches'),
        'all_items' => __('All Churches'),
        'edit_item' => __('Edit Church'),
        'update_item' => __('Update Church'),
        'add_new_item' => __('Add New Church'),
        'new_item_name' => __('New Church Name'),
        'menu_name' => __('Churches'),
    ];

    register_taxonomy('church', 'attendance_report', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
        'show_in_rest' => false,
    ]);
}
add_action('init', 'car_register_taxonomy');

// Add custom fields to the Add New Church form
add_action('church_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label for="church_pastor">Pastor</label>
        <input type="text" name="church_pastor" id="church_pastor">
    </div>
    <div class="form-field">
        <label for="church_address">Address</label>
        <textarea name="church_address" id="church_address" rows="3"></textarea>
    </div>
    <div class="form-field">
        <label for="church_phone">Phone Number</label>
        <input type="text" name="church_phone" id="church_phone">
    </div>
    <div class="form-field">
        <label for="church_website">Website</label>
        <input type="url" name="church_website" id="church_website">
    </div>
    <?php
});

// Add custom fields to the Edit Church form
add_action('church_edit_form_fields', function ($term) {
    $pastor  = get_term_meta($term->term_id, 'pastor', true);
    $address = get_term_meta($term->term_id, 'address', true);
    $phone   = get_term_meta($term->term_id, 'phone', true);
    $website = get_term_meta($term->term_id, 'website', true);
    ?>
    <tr class="form-field">
        <th><label for="church_pastor">Pastor</label></th>
        <td><input type="text" name="church_pastor" id="church_pastor" value="<?php echo esc_attr($pastor); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label for="church_address">Address</label></th>
        <td><textarea name="church_address" id="church_address" rows="3"><?php echo esc_textarea($address); ?></textarea></td>
    </tr>
    <tr class="form-field">
        <th><label for="church_phone">Phone Number</label></th>
        <td><input type="text" name="church_phone" id="church_phone" value="<?php echo esc_attr($phone); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label for="church_website">Website</label></th>
        <td><input type="url" name="church_website" id="church_website" value="<?php echo esc_attr($website); ?>"></td>
    </tr>
    <?php
});

// Save custom term meta when term is created or edited
add_action('edited_church', 'car_save_church_meta');
add_action('create_church', 'car_save_church_meta');

function car_save_church_meta($term_id) {
    if (isset($_POST['church_pastor'])) {
        update_term_meta($term_id, 'pastor', sanitize_text_field($_POST['church_pastor']));
    }
    if (isset($_POST['church_address'])) {
        update_term_meta($term_id, 'address', sanitize_textarea_field($_POST['church_address']));
    }
    if (isset($_POST['church_phone'])) {
        update_term_meta($term_id, 'phone', sanitize_text_field($_POST['church_phone']));
    }
    if (isset($_POST['church_website'])) {
        update_term_meta($term_id, 'website', esc_url_raw($_POST['church_website']));
    }
}
