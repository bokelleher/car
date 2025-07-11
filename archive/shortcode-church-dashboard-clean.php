<?php
$allowed_fields = array('in_person', 'online', 'discipleship');
    if (!in_array($field, $allowed_fields)) wp_send_json_error(array('message' => 'Invalid field'));

    if ($post_id && $field) {
        update_post_meta($post_id, $field, $value);
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => 'Invalid data'));
    }
}
add_action('wp_ajax_car_update_report_field', 'car_update_report_field_ajax');

// CSV Export Handler
function car_export_csv_handler() {
    if (!is_user_logged_in()) wp_die('Not authorized');
    $user = wp_get_current_user();
    $church_id = isset($_POST['church_id']) ? sanitize_text_field($_POST['church_id']) : '';

    if (!$church_id || get_user_meta($user->ID, 'assigned_church', true) != $church_id) {
        wp_die('Invalid church ID');
    }

    $args = array(
        'post_type' => 'attendance_report',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'church_id',
                'value' => $church_id,
                'compare' => '='
            )
        )
    );
    $query = new WP_Query($args);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=church_attendance.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Date', 'In-Person', 'Online', 'Discipleship', 'Submitted By', 'Submitted At'));

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        fputcsv($output, array(
            get_post_meta($post_id, 'report_date', true),
            get_post_meta($post_id, 'in_person', true),
            get_post_meta($post_id, 'online', true),
            get_post_meta($post_id, 'discipleship', true),
            get_post_meta($post_id, 'submitted_by', true),
            get_post_meta($post_id, 'submitted_at', true),
        ));
    }

    fclose($output);
    wp_die();
}
add_action('admin_post_car_export_csv', 'car_export_csv_handler');