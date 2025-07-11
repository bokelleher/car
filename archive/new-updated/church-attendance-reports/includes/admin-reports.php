<?php
function car_register_admin_pages() {
    add_menu_page(
        'Attendance Reports',
        'Attendance Reports',
        'read',
        'car_reports',
        'car_reports_dashboard_page',
        'dashicons-groups',
        30
    );

    add_submenu_page(
        'car_reports',
        'Reports List',
        'Reports',
        'read',
        'car_reports_list',
        'car_render_reports_list_page'
    );
}
add_action('admin_menu', 'car_register_admin_pages');

function car_reports_dashboard_page() {
    echo '<div class="wrap"><h1>Attendance Reports</h1><p>This page will show a summary or links to key reporting functions.</p></div>';
}

function car_render_reports_list_page() {
    echo '<div class="wrap"><h1>Submitted Attendance Reports</h1>';

    $args = [
        'post_type' => 'attendance_report',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    ];

    if (!current_user_can('manage_options')) {
        $current_user = wp_get_current_user();
        $user_church = get_user_meta($current_user->ID, 'assigned_church', true);
        $args['tax_query'] = [
            [
                'taxonomy' => 'church',
                'field' => 'slug',
                'terms' => $user_church,
            ]
        ];
    }

    $reports = get_posts($args);

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>Date</th><th>Church</th><th>In-Person</th><th>Online</th><th>Discipleship</th><th>Reported By</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    foreach ($reports as $report) {
        $church = get_the_terms($report->ID, 'church')[0]->name ?? '—';
        echo '<tr>';
        echo '<td>' . esc_html(get_post_meta($report->ID, 'report_date', true)) . '</td>';
        echo '<td>' . esc_html($church) . '</td>';
        echo '<td>' . esc_html(get_post_meta($report->ID, 'attendance_in_person', true)) . '</td>';
        echo '<td>' . esc_html(get_post_meta($report->ID, 'attendance_online', true)) . '</td>';
        echo '<td>' . esc_html(get_post_meta($report->ID, 'attendance_discipleship', true)) . '</td>';
        echo '<td>' . esc_html(get_post_meta($report->ID, 'reported_by', true)) . '</td>';
        echo '<td><a href="' . get_edit_post_link($report->ID) . '">View/Edit</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
