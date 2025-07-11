<?php
/**
 * Trigger sample data insertion for the Church Attendance Reports plugin.
 * Visit: https://yourdomain.com/wp-admin/?car_generate_samples=true
 */

if (!defined('ABSPATH')) exit;

function car_generate_sample_reports() {
    if (!is_admin() || !current_user_can('manage_options')) return;

    $church_id = 1; // Replace with actual church term ID or taxonomy term slug
    $user_id = get_current_user_id();
    $now = current_time('mysql');

    for ($i = 0; $i < 12; $i++) {
        $date = date('Y-m-d', strtotime("-$i weeks"));

        $post_id = wp_insert_post([
            'post_type' => 'attendance_report',
            'post_title' => "Sample Report $date",
            'post_status' => 'publish',
        ]);

        if ($post_id) {
            update_post_meta($post_id, 'church_id', $church_id);
            update_post_meta($post_id, 'report_date', $date);
            update_post_meta($post_id, 'in_person', rand(30, 100));
            update_post_meta($post_id, 'online', rand(10, 50));
            update_post_meta($post_id, 'discipleship', rand(5, 30));
            update_post_meta($post_id, 'submitted_by', $user_id);
            update_post_meta($post_id, 'submitted_at', $now);
        }
    }

    echo '✅ Sample data inserted.';
    exit;
}
add_action('admin_init', 'car_generate_sample_reports_trigger');

function car_generate_sample_reports_trigger() {
    if (isset($_GET['car_generate_samples']) && $_GET['car_generate_samples'] === 'true') {
        car_generate_sample_reports();
    }
}
