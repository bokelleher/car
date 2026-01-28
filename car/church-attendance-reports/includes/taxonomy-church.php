<?php
// taxonomy-church.php

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
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
        'show_in_rest' => false,
        'rewrite' => [
            'slug' => 'church',
            'with_front' => false,
        ],
    ]);
}
add_action('init', 'car_register_taxonomy');

// Add custom fields to Add Church screen
function car_add_church_meta_fields() {
    ?>
    <div class="form-field">
        <label for="pastor_name">Pastor Name</label>
        <input name="pastor_name" id="pastor_name" type="text" value="">
    </div>
    <div class="form-field">
        <label for="pastor_email">Pastor Email</label>
        <input name="pastor_email" id="pastor_email" type="email" value="">
    </div>
    <div class="form-field">
        <label for="address">Address</label>
        <input name="address" id="address" type="text" value="">
    </div>
    <div class="form-field">
        <label for="phone">Phone</label>
        <input name="phone" id="phone" type="text" value="">
    </div>
    <div class="form-field">
        <label for="website">Website</label>
        <input name="website" id="website" type="url" value="">
    </div>
    <?php
}
add_action('church_add_form_fields', 'car_add_church_meta_fields');

// Edit fields on Edit Church screen
function car_edit_church_meta_fields($term) {
    $pastor_name = get_term_meta($term->term_id, 'pastor_name', true);
    $pastor_email = get_term_meta($term->term_id, 'pastor_email', true);
    $address = get_term_meta($term->term_id, 'address', true);
    $phone = get_term_meta($term->term_id, 'phone', true);
    $website = get_term_meta($term->term_id, 'website', true);
    ?>
    <tr class="form-field">
        <th><label for="pastor_name">Pastor Name</label></th>
        <td><input name="pastor_name" id="pastor_name" type="text" value="<?php echo esc_attr($pastor_name); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label for="pastor_email">Pastor Email</label></th>
        <td><input name="pastor_email" id="pastor_email" type="email" value="<?php echo esc_attr($pastor_email); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label for="address">Address</label></th>
        <td><input name="address" id="address" type="text" value="<?php echo esc_attr($address); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label for="phone">Phone</label></th>
        <td><input name="phone" id="phone" type="text" value="<?php echo esc_attr($phone); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label for="website">Website</label></th>
        <td><input name="website" id="website" type="url" value="<?php echo esc_attr($website); ?>"></td>
    </tr>
    <?php
}
add_action('church_edit_form_fields', 'car_edit_church_meta_fields');

// Save term meta
function car_save_church_meta_fields($term_id) {
    $fields = ['pastor_name', 'pastor_email', 'address', 'phone', 'website'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_term_meta($term_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('created_church', 'car_save_church_meta_fields');
add_action('edited_church', 'car_save_church_meta_fields');
