<?php
// Enqueue jQuery and DataTables for sorting and CSV export
function car_enqueue_district_report_scripts() {
    if (is_page()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', ['jquery'], null, true);
        wp_enqueue_script('datatables-buttons', 'https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js', ['datatables-js'], null, true);
        wp_enqueue_script('datatables-buttons-html5', 'https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js', ['datatables-buttons'], null, true);
        wp_enqueue_script('datatables-jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js', [], null, true);

        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_style('datatables-buttons-css', 'https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css');
    }
}
add_action('wp_enqueue_scripts', 'car_enqueue_district_report_scripts');

// Shortcode for district report
function car_render_district_attendance_report() {
    if (!current_user_can('manage_options') && !in_array('district_admin', wp_get_current_user()->roles)) {
        return '<p>You do not have permission to view this report.</p>';
    }

    $reports = get_posts([
        'post_type' => 'attendance_report',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ]);

    ob_start();
    ?>
    <table id="district-report" class="display">
        <thead>
            <tr>
                <th>Church</th>
                <th>Week Ending</th>
                <th>In Person</th>
                <th>Online</th>
                <th>Discipleship</th>
                <th>ACL</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><?php echo esc_html(implode(', ', wp_get_post_terms($report->ID, 'church', ['fields' => 'names']))); ?></td>
                <td><?php echo esc_html(get_post_meta($report->ID, 'week_ending', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($report->ID, 'attendance_total', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($report->ID, 'online_attendance', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($report->ID, 'discipleship_attendance', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($report->ID, 'acl_count', true)); ?></td>
                <td>
                    <?php
                    $uid = get_post_meta($report->ID, 'submitted_by', true);
                    $u = get_user_by('ID', $uid);
                    echo $u ? esc_html($u->first_name . ' ' . $u->last_name) : '<em>Unknown</em>';
                    ?>
                </td>
                <td>
                    <?php
                    $ts = get_post_meta($report->ID, 'submitted_at', true);
                    echo $ts ? esc_html(date('M d, Y g:i A', strtotime($ts))) : '<em>Not recorded</em>';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>
        jQuery(document).ready(function($) {
            $('#district-report').DataTable({
                dom: 'Bfrtip',
                buttons: ['csv'],
                order: [[1, 'desc']]
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('district_attendance_summary', 'car_render_district_attendance_report');

