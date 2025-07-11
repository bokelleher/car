<?php
add_action('admin_footer-edit-tags.php', 'car_render_church_modal');
function car_render_church_modal() {
    $screen = get_current_screen();
    if ($screen->taxonomy !== 'church') return;
    include plugin_dir_path(__FILE__) . 'church-modal.php-html';
}

add_action('admin_enqueue_scripts', 'car_enqueue_church_modal_assets');
function car_enqueue_church_modal_assets($hook_suffix) {
    if ($hook_suffix !== 'edit-tags.php') return;
    $screen = get_current_screen();
    if ($screen->taxonomy !== 'church') return;

    wp_enqueue_script('car-church-modal-js', plugin_dir_url(__FILE__) . '../assets/js/church-modal.js', ['jquery'], null, true);
    wp_enqueue_style('car-church-modal-css', plugin_dir_url(__FILE__) . '../assets/css/church-modal.css');
}

// Optional: Hide the default "Add New Church" form
add_action('church_add_form_fields', 'car_remove_add_church_form');
function car_remove_add_church_form() {
    // suppress default form
}
?>
