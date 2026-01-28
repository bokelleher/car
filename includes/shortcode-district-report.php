<?php
// District attendance report shortcode with missing reports list.

/**
 * Enqueue DataTables scripts and styles on pages that display the district report.
 */
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

/**
 * Render the district attendance summary table and a list of churches missing reports.
 *
 * Only users with the manage_options capability or the district_admin role may view
 * this report. The table lists all attendance reports with details. Below the table,
 * we include a "Missing Reports" section that lists churches that have not submitted
 * a report in the last 30 days, along with the names of their church admins.
 *
 * @return string HTML markup for the report table and missing reports section.
 */
function car_render_district_attendance_report() {
    // Access control.
    $current_user = wp_get_current_user();
    if (!current_user_can('manage_options') && !in_array('district_admin', (array) $current_user->roles, true)) {
        return '<p>' . __('You do not have permission to view this report.', 'church-attendance-reports') . '</p>';
    }

    // Retrieve all attendance reports.
    $reports = get_posts([
        'post_type'      => 'attendance_report',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    ob_start();
    ?>
    <table id="district-report" class="display">
        <thead>
            <tr>
                <th><?php _e('Report ID', 'church-attendance-reports'); ?></th>
                <th><?php _e('Church', 'church-attendance-reports'); ?></th>
                <th><?php _e('Week Ending', 'church-attendance-reports'); ?></th>
                <th><?php _e('In Person', 'church-attendance-reports'); ?></th>
                <th><?php _e('Online', 'church-attendance-reports'); ?></th>
                <th><?php _e('Discipleship', 'church-attendance-reports'); ?></th>
                <th><?php _e('ACL', 'church-attendance-reports'); ?></th>
                <th><?php _e('Submitted By', 'church-attendance-reports'); ?></th>
                <th><?php _e('Submitted At', 'church-attendance-reports'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($reports as $report) :
            // Get church ID and name.
            $church_id   = get_post_meta($report->ID, 'church', true);
            $church_term = $church_id ? get_term($church_id, 'church') : null;
            $church_name = ($church_term && !is_wp_error($church_term)) ? $church_term->name : '';

            // Attendance date (week ending).
            $attendance_date = get_post_meta($report->ID, 'attendance_date', true);

            // Attendance numbers (correct meta keys).
            $in_person    = get_post_meta($report->ID, 'attendance_in_person', true);
            $online       = get_post_meta($report->ID, 'attendance_online', true);
            $discipleship = get_post_meta($report->ID, 'attendance_discipleship', true);
            $acl          = get_post_meta($report->ID, 'attendance_acl', true);

            // Submission info.
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
                <td><?php echo $submitted_by ? esc_html($submitted_by) : '<em>' . __('Unknown', 'church-attendance-reports') . '</em>'; ?></td>
                <td><?php echo $submitted_at ? esc_html(date_i18n('M d, Y g:i A', strtotime($submitted_at))) : '<em>' . __('Not recorded', 'church-attendance-reports') . '</em>'; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <script>
        jQuery(document).ready(function($) {
            $('#district-report').DataTable({
                dom: 'Bfrtip',
                buttons: ['csv'],
                order: [[2, 'desc']]
            });
        });
    </script>
    <?php
    // Compute missing reports list. We'll gather all churches and find those
    // without a report in the last 30 days.
    $missing = [];
    $church_terms = get_terms([
        'taxonomy'   => 'church',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($church_terms)) {
        $now_ts    = current_time('timestamp');
        $threshold = 30 * DAY_IN_SECONDS;
        foreach ($church_terms as $term) {
            $church_id = $term->term_id;
            // Get most recent report for this church.
            $latest = new WP_Query([
                'post_type'      => 'attendance_report',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'orderby'        => 'meta_value',
                'meta_key'       => 'attendance_date',
                'order'          => 'DESC',
                'meta_query'     => [
                    [
                        'key'   => 'church',
                        'value' => $church_id,
                    ],
                ],
            ]);
            $last_date = null;
            if ($latest->have_posts()) {
                $last_id   = $latest->posts[0]->ID;
                $last_date = get_post_meta($last_id, 'attendance_date', true);
            }
            wp_reset_postdata();

            $is_missing = true;
            if ($last_date) {
                $last_ts = strtotime($last_date);
                if ($last_ts !== false && ($now_ts - $last_ts) <= $threshold) {
                    $is_missing = false;
                }
            }
            if ($is_missing) {
                // Fetch church admins assigned to this church.
                $admins = get_users([
                    'role__in'   => ['church_admin'],
                    'meta_key'   => 'assigned_church',
                    'meta_value' => $church_id,
                    'fields'     => ['display_name', 'user_login'],
                ]);
                $admin_names = [];
                if ($admins) {
                    foreach ($admins as $admin) {
                        $admin_names[] = $admin->display_name ? $admin->display_name : $admin->user_login;
                    }
                }
                $missing[] = [
                    'church' => $term->name,
                    'admins' => !empty($admin_names) ? implode(', ', $admin_names) : __('Unknown', 'church-attendance-reports'),
                ];
            }
        }
    }

    // Output missing report list.
    if (!empty($missing)) {
        echo '<h3>' . __('Missing Reports (Past 30 Days)', 'church-attendance-reports') . '</h3>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Church', 'church-attendance-reports') . '</th>';
        echo '<th>' . __('Church Admin', 'church-attendance-reports') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($missing as $item) {
            echo '<tr>';
            echo '<td>' . esc_html($item['church']) . '</td>';
            echo '<td>' . esc_html($item['admins']) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    return ob_get_clean();
}
add_shortcode('district_attendance_summary', 'car_render_district_attendance_report');