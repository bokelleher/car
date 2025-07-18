<?php
// Register Attendance Report post type
function car_register_post_type() {
    register_post_type('attendance_report', [
        'labels' => [
            'name' => 'Attendance Reports',
            'singular_name' => 'Attendance Report',
            'menu_name' => 'Attendance Reports',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-groups',
        'supports' => ['title'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'capabilities' => [
            'create_posts' => 'do_not_allow',
        ],
    ]);
}
add_action('init', 'car_register_post_type');

// Remove Add New
function car_remove_add_new_menu_item() {
    global $submenu;
    if (isset($submenu['edit.php?post_type=attendance_report'])) {
        foreach ($submenu['edit.php?post_type=attendance_report'] as $i => $item) {
            if (in_array('post-new.php?post_type=attendance_report', $item)) {
                unset($submenu['edit.php?post_type=attendance_report'][$i]);
            }
        }
    }
}
add_action('admin_menu', 'car_remove_add_new_menu_item', 999);
add_action('admin_notices', function () {
    global $pagenow;

    // Only show on the Attendance Reports post list page
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'attendance_report') {
        $url = admin_url('post-new.php?post_type=attendance_report');
        ?>
        <div class="notice notice-info inline" style="padding: 10px 15px;">
            <a class="button button-primary" href="<?php echo esc_url($url); ?>">➕ Add New Attendance Report</a>
        </div>
        <?php
    }
});
