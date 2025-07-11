<?php
/**
 * Plugin Name: Church Admin Add Church
 * Description: Adds a button and page for creating new churches.
 */

add_action('admin_menu', function () {
    add_submenu_page(
        null,
        'Add New Church',
        'Add New Church',
        'manage_options',
        'car_add_church',
        'car_render_add_church_page'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'church') {
        wp_enqueue_script('car-add-church-button', plugin_dir_url(__FILE__) . 'assets/js/add-church-button.js', ['jquery'], null, true);
    }
});

require_once plugin_dir_path(__FILE__) . 'includes/church-add-page.php';
