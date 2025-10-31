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
                <th>Report ID</th>
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
        <?php foreach ($reports as $report): 
            // Get church ID and name
            $church_id = get_post_meta($report->ID, 'church', true);
            $church_term = get_term($church_id, 'church');
            $church_name = ($church_term && !is_wp_error($church_term)) ? $church_term->name : '';
            
            // Get attendance date (Week Ending)
            $attendance_date = get_post_meta($report->ID, 'attendance_date', true);
            
            // Get attendance numbers (using correct meta keys)
            $in_person = get_post_meta($report->ID, 'attendance_in_person', true);
            $online = get_post_meta($report->ID, 'attendance_online', true);
            $discipleship = get_post_meta($report->ID, 'attendance_discipleship', true);
            $acl = get_post_meta($report->ID, 'attendance_acl', true);
            
            // Get submission info
            $submitted_by = get_post_meta($report->ID, 'submitted_by', true);
            $submitted_at = get_post_meta($report->ID, 'submitted_at', true);
        ?>
            <tr>
                <td><strong><?php echo intval($report->ID); ?></strong></td>
                <td><?php echo esc_html($church_name); ?></td>
                <td><?php echo esc_html($attendance_date); ?></td>
                <td><?php echo esc_html($in_person); ?></td>
                <td><?php echo esc_html($online); ?></td>
                <td><?php echo esc_html($discipleship); ?></td>
                <td><?php echo esc_html($acl); ?></td>
                <td><?php echo $submitted_by ? esc_html($submitted_by) : '<em>Unknown</em>'; ?></td>
                <td><?php echo $submitted_at ? esc_html(date('M d, Y g:i A', strtotime($submitted_at))) : '<em>Not recorded</em>'; ?></td>
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