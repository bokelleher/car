<?php
// Assign custom capabilities for attendance_report post type to roles

function car_assign_custom_caps() {
    // Get the roles
    $district_admin = get_role('district_admin');
    $super_admin_capable_roles = [$district_admin];

    // Capability map
    $caps = [
        'edit_attendance_report',
        'read_attendance_report',
        'delete_attendance_report',
        'edit_attendance_reports',
        'edit_others_attendance_reports',
        'publish_attendance_reports',
        'read_private_attendance_reports',
        'delete_attendance_reports',
        'delete_private_attendance_reports',
        'delete_published_attendance_reports',
        'delete_others_attendance_reports',
        'edit_private_attendance_reports',
        'edit_published_attendance_reports',
    ];

    foreach ($super_admin_capable_roles as $role) {
        if ($role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}
add_action('init', 'car_assign_custom_caps');
