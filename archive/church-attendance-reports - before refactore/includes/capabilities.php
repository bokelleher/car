<?php
// includes/capabilities.php

function car_add_custom_attendance_report_caps() {
    $roles = ['administrator', 'district_admin'];

    $caps = [
        'edit_attendance_report',
        'read_attendance_report',
        'delete_attendance_report',
        'edit_attendance_reports',
        'edit_others_attendance_reports',
        'publish_attendance_reports',
        'read_private_attendance_reports',
        'create_attendance_reports',
    ];

    foreach ($roles as $role_slug) {
        $role = get_role($role_slug);
        if ($role) {
            foreach ($caps as $cap) {
                $role->add_cap($cap);
            }
        }
    }
}
add_action('init', 'car_add_custom_attendance_report_caps');
