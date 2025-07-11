<?php
// includes/taxonomy-church.php

// Register 'Church' taxonomy
function car_register_church_taxonomy() {
    $labels = [
        'name'              => 'Churches',
        'singular_name'     => 'Church',
        'search_items'      => 'Search Churches',
        'all_items'         => 'All Churches',
        'edit_item'         => 'Edit Church',
        'update_item'       => 'Update Church',
        'add_new_item'      => 'Add New Church',
        'new_item_name'     => 'New Church Name',
        'menu_name'         => 'Churches',
    ];

    register_taxonomy('church', ['attendance_report'], [
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'church'],
    ]);
}
add_action('init', 'car_register_church_taxonomy');
