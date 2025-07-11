<?php
// Debug version of church-ui.php
function car_add_church_button_to_taxonomy_page() {
    error_log('✅ car_add_church_button_to_taxonomy_page triggered');

    if (isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'church' &&
        isset($_GET['post_type']) && $_GET['post_type'] === 'attendance_report') {

        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><a href="' . admin_url('admin.php?page=car_add_church') . '" class="button button-primary">➕ Add New Church (Debug)</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'car_add_church_button_to_taxonomy_page');

function car_enqueue_church_admin_styles() {
    $screen = get_current_screen();
    error_log('✅ car_enqueue_church_admin_styles triggered on screen: ' . $screen->id);

    if ($screen->id === 'edit-church') {
        wp_enqueue_style('car-church-admin-css', plugin_dir_url(__FILE__) . '../assets/css/church-admin.css');
    }
}
add_action('admin_enqueue_scripts', 'car_enqueue_church_admin_styles');
