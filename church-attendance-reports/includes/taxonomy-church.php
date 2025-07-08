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
