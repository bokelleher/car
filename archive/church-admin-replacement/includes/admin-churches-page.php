<?php
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=attendance_report',
        'Manage Churches',
        'Churches',
        'manage_options',
        'car_manage_churches',
        'car_render_manage_churches_page'
    );
});

function car_render_manage_churches_page() {
    echo '<div class="wrap"><h1>Manage Churches</h1>';
    echo '<button class="button button-primary" id="car-add-church-btn">Add New Church</button>';
    echo '<div id="car-church-table-container">';
    include plugin_dir_path(__FILE__) . 'partials/churches-table.php';
    echo '</div>';
    include plugin_dir_path(__FILE__) . 'partials/church-modal.php';
    echo '</div>';
}

add_action('admin_init', function () {
    if (
        is_admin() &&
        isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'church' &&
        isset($_GET['post_type']) && $_GET['post_type'] === 'attendance_report'
    ) {
        wp_redirect(admin_url('admin.php?page=car_manage_churches'));
        exit;
    }
});

add_action('wp_ajax_car_save_church', function () {
    global $wpdb;
    $table = $wpdb->prefix . 'car_churches';
    $data = [
        'name' => sanitize_text_field($_POST['name']),
        'slug' => sanitize_title($_POST['slug']),
        'pastor' => sanitize_text_field($_POST['pastor']),
        'city' => sanitize_text_field($_POST['city']),
        'website' => esc_url_raw($_POST['website']),
    ];
    if (!empty($_POST['id'])) {
        $wpdb->update($table, $data, ['id' => absint($_POST['id'])]);
    } else {
        $wpdb->insert($table, $data);
    }
    wp_send_json_success();
});
