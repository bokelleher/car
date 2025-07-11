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

// Add custom fields to church taxonomy (Pastor Email)
function car_add_church_taxonomy_fields($term) {
    $email = get_term_meta($term->term_id, 'pastor_email', true);
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="pastor_email">Pastor Email</label></th>
        <td>
            <input type="email" name="pastor_email" id="pastor_email" value="<?php echo esc_attr($email); ?>" />
            <p class="description">Enter the pastor's email address for this church.</p>
        </td>
    </tr>
    <?php
}
add_action('church_edit_form_fields', 'car_add_church_taxonomy_fields');
add_action('church_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label for="pastor_email">Pastor Email</label>
        <input type="email" name="pastor_email" id="pastor_email" />
        <p class="description">Enter the pastor's email address for this church.</p>
    </div>
    <?php
});

// Save the custom field
function car_save_church_taxonomy_fields($term_id) {
    if (isset($_POST['pastor_email'])) {
        update_term_meta($term_id, 'pastor_email', sanitize_email($_POST['pastor_email']));
    }
}
add_action('edited_church', 'car_save_church_taxonomy_fields');
add_action('create_church', 'car_save_church_taxonomy_fields');
