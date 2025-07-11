<?php
// Shortcode to display details of an attendance report
function car_render_attendance_report_detail($atts) {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view this report.</p>';
    }

    $atts = shortcode_atts([
        'id' => 0,
    ], $atts);

    $report_id = intval($atts['id']);
    if (!$report_id || get_post_type($report_id) !== 'attendance_report') {
        return '<p>Invalid or missing attendance report.</p>';
    }

    $user = wp_get_current_user();
    $allowed_roles = ['district_admin', 'church_admin', 'church_viewer'];
    if (!array_intersect($allowed_roles, $user->roles)) {
        return '<p>You do not have permission to view this report.</p>';
    }

    $week_ending = get_post_meta($report_id, 'week_ending', true);
    $attendance_total = get_post_meta($report_id, 'attendance_total', true);
    $acl_count = get_post_meta($report_id, 'acl_count', true);
    $submitted_by = get_post_meta($report_id, 'submitted_by', true);
    $submitted_at = get_post_meta($report_id, 'submitted_at', true);

    $submitter = $submitted_by ? get_userdata($submitted_by) : null;
    $church_terms = wp_get_post_terms($report_id, 'church');
    $church_name = !empty($church_terms) ? esc_html($church_terms[0]->name) : 'N/A';

    ob_start();
    ?>
    <style>
        .car-attendance-report {
            max-width: 600px;
            margin: 2em auto;
            background: #fdfdfd;
            border: 1px solid #dcdcdc;
            padding: 2em;
            border-radius: 8px;
            font-family: "Segoe UI", sans-serif;
        }
        .car-attendance-report h2 {
            color: #00457c;
            margin-bottom: 1em;
            font-size: 1.6rem;
        }
        .car-attendance-report p {
            margin: 0.6em 0;
            font-size: 1.05rem;
        }
        .car-attendance-report strong {
            display: inline-block;
            width: 180px;
            color: #333;
        }
        .car-attendance-report hr {
            margin: 1.5em 0;
            border: none;
            border-top: 1px solid #ccc;
        }
    </style>

    <div class="car-attendance-report">
        <h2>Attendance Report Details</h2>
        <p><strong>Week Ending:</strong> <?php echo esc_html($week_ending); ?></p>
        <p><strong>Church:</strong> <?php echo $church_name; ?></p>
        <p><strong>Total Attendance:</strong> <?php echo esc_html($attendance_total); ?></p>
        <p><strong>ACL (Accountability Care List):</strong> <?php echo esc_html($acl_count); ?></p>

        <hr>

        <p><strong>Submitted By:</strong> <?php echo $submitter ? esc_html($submitter->display_name) : 'Unknown'; ?></p>
        <p><strong>Submitted At:</strong> <?php echo $submitted_at ? esc_html(date('M d, Y g:i A', strtotime($submitted_at))) : 'Unknown'; ?></p>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('attendance_report_detail', 'car_render_attendance_report_detail');
