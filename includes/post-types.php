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
        'capability_type' => 'attendance_report',
        'map_meta_cap' => true,
        'capabilities' => [
            'edit_post' => 'edit_attendance_report',
            'read_post' => 'read_attendance_report',
            'delete_post' => 'delete_attendance_report',
            'edit_posts' => 'edit_attendance_reports',
            'edit_others_posts' => 'edit_others_attendance_reports',
            'publish_posts' => 'publish_attendance_reports',
            'read_private_posts' => 'read_private_attendance_reports',
            'create_posts' => 'create_attendance_reports',
            'delete_posts' => 'delete_attendance_reports',
            'delete_others_posts' => 'delete_others_attendance_reports',
            'delete_private_posts' => 'delete_private_attendance_reports',
            'delete_published_posts' => 'delete_published_attendance_reports',
        ],
    ]);
}
add_action('init', 'car_register_post_type');

// Remove Add New from submenu (will be conditionally shown via custom logic)
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

// Show custom Add New button if user has permission
add_action('admin_notices', function () {
    global $pagenow;
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'attendance_report') {
        if (current_user_can('create_attendance_reports')) {
            $url = admin_url('post-new.php?post_type=attendance_report');
            ?>
            <div class="notice notice-info inline" style="padding: 10px 15px;">
                <a class="button button-primary" href="<?php echo esc_url($url); ?>">➕ Add New Attendance Report</a>
            </div>
            <?php
        }
    }
});