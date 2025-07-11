<?php
function car_add_reports_menu_page() {
    add_menu_page(
        'CAR Reports',
        'Attendance Reports',
        'manage_options',
        'car-attendance-reports',
        'car_render_reports_admin_page',
        'dashicons-chart-bar',
        25
    );
}
add_action('admin_menu', 'car_add_reports_menu_page');

function car_render_reports_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'car_attendance_reports';
    $church_table = $wpdb->prefix . 'car_churches';

    $results = $wpdb->get_results("
        SELECT r.*, c.name AS church_name
        FROM $table r
        LEFT JOIN $church_table c ON r.church_id = c.id
        ORDER BY r.report_date DESC
        LIMIT 100
    ");

    echo '<div class="wrap"><h1>Church Attendance Reports</h1>';
    if ($results) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>Date</th><th>Church</th><th>In Person</th><th>Online</th><th>Discipleship</th><th>ACL</th><th>Submitted By</th><th>Submitted At</th></tr></thead><tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->report_date) . '</td>';
            echo '<td>' . esc_html($row->church_name) . '</td>';
            echo '<td>' . esc_html($row->in_person) . '</td>';
            echo '<td>' . esc_html($row->online) . '</td>';
            echo '<td>' . esc_html($row->discipleship) . '</td>';
            echo '<td>' . esc_html($row->acl) . '</td>';
            echo '<td>' . esc_html($row->submitted_by) . '</td>';
            echo '<td>' . esc_html($row->submitted_at) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No reports found.</p>';
    }
    echo '</div>';
}
