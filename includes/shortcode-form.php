<?php
// Attendance reporting form shortcode

/**
 * Render the attendance reporting form.
 *
 * This function replaces the original version and adds several enhancements:
 *  - Displays a yellow warning banner if the logged‑in church admin or church reporter
 *    has not submitted a report in the last 30 days.
 *  - Changes the category labels to reflect averages (“Avg. In Person Worship”,
 *    “Avg. Online”, “Avg. Discipleship”, and “ACL”) without altering the
 *    underlying meta keys.
 *  - Prevents selection of a future date by applying a max attribute to the date
 *    input field based on the user’s local timezone via JavaScript.
 *
 * Only logged‑in users assigned to a church can access this form. Church admins
 * can edit past reports (by selecting the date), while other users submit only
 * for the current day.
 *
 * @return string HTML markup for the form or an error message.
 */
function car_render_attendance_form() {
    // Require login.
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to submit attendance.</p>';
    }

    $user_id   = get_current_user_id();
    $church_id = get_user_meta($user_id, 'assigned_church', true);
    if (!$church_id) {
        return '<p><strong>Error:</strong> No church assigned to your account.</p>';
    }

    // Determine if the current user is an administrator or church admin. Administrators
    // and church admins can edit past reports by selecting the date. Others can only
    // submit for today and the field is readonly.
    $is_admin = current_user_can('church_admin') || current_user_can('administrator');

    // Begin output buffering.
    ob_start();

    $editing         = false;
    $existing_report = null;
    $today           = current_time('Y-m-d');

    // Check if a report already exists for today. If so, load it for editing.
    $query = new WP_Query([
        'post_type'   => 'attendance_report',
        'post_status' => 'publish',
        'meta_query'  => [
            [
                'key'   => 'church',
                'value' => $church_id,
            ],
            [
                'key'   => 'attendance_date',
                'value' => $today,
            ],
        ],
    ]);
    if ($query->have_posts()) {
        $editing         = true;
        $existing_report = $query->posts[0];
    }

    // Determine if the user should see a warning banner. We look up the most recent
    // attendance report for this church and compare its date to now. If older than
    // 30 days (or none exists), we display a banner to church admins and church reporters.
    $show_warning = false;
    if (current_user_can('church_admin') || current_user_can('church_reporter')) {
        // Get the most recent report for this church.
        $latest_query = new WP_Query([
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
        $last_report_date = null;
        if ($latest_query->have_posts()) {
            $last_id          = $latest_query->posts[0]->ID;
            $last_report_date = get_post_meta($last_id, 'attendance_date', true);
        }
        wp_reset_postdata();

        // Convert date string to timestamp. We use strtotime directly because
        // attendance_date is stored as Y-m-d.
        $now_ts  = current_time('timestamp');
        $overdue = true;
        if ($last_report_date) {
            $last_ts = strtotime($last_report_date);
            if ($last_ts !== false && ($now_ts - $last_ts) <= 30 * DAY_IN_SECONDS) {
                $overdue = false;
            }
        }
        $show_warning = $overdue;
    }

    // Build church name + friendly greeting.
    $church_name = '';
    if (!empty($church_id)) {
        $church_term = get_term((int) $church_id, 'church');
        if ($church_term && !is_wp_error($church_term)) {
            $church_name = $church_term->name;
        }
    }

    $current_user = wp_get_current_user();
    $first_name   = $current_user ? trim((string) $current_user->first_name) : '';
    $last_name    = $current_user ? trim((string) $current_user->last_name) : '';
    $full_name    = trim($first_name . ' ' . $last_name);
    if ($full_name === '') {
        $full_name = $current_user ? $current_user->display_name : '';
    }

    echo '<h1 style="margin-top: 0;">' . esc_html($church_name ?: __('Report Attendance', 'church-attendance-reports')) . '</h1>';
    echo '<div style="margin-bottom: 15px;">';
    if ($full_name) {
        echo '<strong>' . sprintf(esc_html__('Hello %s!', 'church-attendance-reports'), esc_html($full_name)) . '</strong><br>';
    }
    echo esc_html__('Report attendance for your church.', 'church-attendance-reports') . '<br>';

    if (!empty($last_report_date)) {
        $last_ts = strtotime($last_report_date);
        $pretty  = $last_ts ? date_i18n('F j, Y', $last_ts) : $last_report_date;
        echo sprintf(
            esc_html__('Your last report was %s.', 'church-attendance-reports'),
            '<strong>' . esc_html($pretty) . '</strong>'
        );
    } else {
        echo esc_html__('Your church has not submitted a report yet.', 'church-attendance-reports');
    }
    echo '</div>';

    // Display warning banner if needed (place it below the greeting).
    if ($show_warning) {
        echo '<div style="background-color: #fffbe5; border-left: 4px solid #ffeb3b; padding: 15px; margin-bottom: 15px;">';
        echo '<strong>Reminder:</strong> It looks like this church has not submitted an attendance report in the last 30 days.';
        echo '</div>';
    }

    ?>
    <form method="post">
        <input type="hidden" name="car_attendance_form_submitted" value="1">
        <?php wp_nonce_field('car_attendance_form', 'car_attendance_form_nonce'); ?>

        <?php if ($editing) : ?>
            <h3 style="margin-top: 0;"><?php echo esc_html__('Edit Attendance Report', 'church-attendance-reports'); ?></h3>
        <?php endif; ?>

        <label for="attendance_date"><?php esc_html_e('Report Date:', 'church-attendance-reports'); ?></label><br>
        <input type="date"
               id="attendance_date"
               name="attendance_date"
               value="<?php echo esc_attr($editing ? get_post_meta($existing_report->ID, 'attendance_date', true) : $today); ?>"
               <?php echo $is_admin ? '' : 'readonly'; ?>
               required
        ><br><br>

        <table style="width: 100%; max-width: 600px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Category', 'church-attendance-reports'); ?></th>
                    <th><?php esc_html_e('Total', 'church-attendance-reports'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Define the categories with updated labels. The keys map to the
                // meta fields (attendance_in_person, etc.) while the values are
                // shown in the form.
                $categories = [
                    'in_person'    => __('Avg. In Person Worship', 'church-attendance-reports'),
                    'online'       => __('Avg. Online', 'church-attendance-reports'),
                    'discipleship' => __('Avg. Discipleship', 'church-attendance-reports'),
                    'acl'          => __('ACL', 'church-attendance-reports'),
                ];

                $totals = [];

                foreach ($categories as $key => $label) {
                    $meta_key = "attendance_{$key}";
                    $value    = '';
                    if ($editing) {
                        $value = get_post_meta($existing_report->ID, $meta_key, true);
                    }
                    $totals[$key] = $value !== '' ? intval($value) : 0;
                    echo '<tr>';
                    echo '<td>' . esc_html($label) . '</td>';
                    echo "<td><input type='number' name='attendance_{$key}' value='" . esc_attr($totals[$key]) . "' min='0' required class='attendance-field'></td>";
                    echo '</tr>';
                }
                ?>
                <tr>
                    <td><strong><?php esc_html_e('Total', 'church-attendance-reports'); ?></strong></td>
                    <td><input type="number" id="attendance_total" name="attendance_total" value="0" readonly></td>
                </tr>
            </tbody>
        </table>

        <br>
        <button type="submit" class="button button-primary">
            <?php echo $editing ? esc_html__('Update Report', 'church-attendance-reports') : esc_html__('Submit Report', 'church-attendance-reports'); ?>
        </button>
    </form>

    <?php
    // Show version history if editing an existing report.
    if ($editing) {
        $report_id      = $existing_report->ID;
        $report_date    = get_post_meta($report_id, 'attendance_date', true);
        $formatted_date = $report_date ? date_i18n('M j, Y', strtotime($report_date)) : esc_html__('Unknown Date', 'church-attendance-reports');

        $history = get_post_meta($report_id, 'version_history', true);
        if (!empty($history) && is_array($history)) {
            echo '<h3>' . sprintf(esc_html__('Version History for Report ID: %d on %s', 'church-attendance-reports'), $report_id, $formatted_date) . '</h3>';
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<thead><tr style="background: #f5f5f5;">';
            echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' . esc_html__('User', 'church-attendance-reports') . '</th>';
            echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">' . esc_html__('Date', 'church-attendance-reports') . '</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">' . esc_html__('In-Person', 'church-attendance-reports') . '</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">' . esc_html__('Online', 'church-attendance-reports') . '</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">' . esc_html__('Discipleship', 'church-attendance-reports') . '</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">' . esc_html__('ACL', 'church-attendance-reports') . '</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">' . esc_html__('Total', 'church-attendance-reports') . '</th>';
            echo '</tr></thead><tbody>';

            foreach ($history as $entry) {
                $timestamp    = $entry['timestamp'] ?? ($entry['date'] ?? '');
                $date_display = $timestamp ? date_i18n('M d, Y g:i A', strtotime($timestamp)) : esc_html__('Unknown date', 'church-attendance-reports');
                echo '<tr style="border-bottom: 1px solid #ddd;">';
                echo '<td style="padding: 10px;">' . esc_html($entry['user'] ?? esc_html__('Unknown', 'church-attendance-reports')) . '</td>';
                echo '<td style="padding: 10px;">' . esc_html($date_display) . '</td>';
                echo '<td style="padding: 10px; text-align: center;">' . esc_html($entry['in_person'] ?? '-') . '</td>';
                echo '<td style="padding: 10px; text-align: center;">' . esc_html($entry['online'] ?? '-') . '</td>';
                echo '<td style="padding: 10px; text-align: center;">' . esc_html($entry['discipleship'] ?? '-') . '</td>';
                echo '<td style="padding: 10px; text-align: center;">' . esc_html($entry['acl'] ?? '-') . '</td>';
                echo '<td style="padding: 10px; text-align: center;">' . esc_html($entry['total'] ?? '-') . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        echo '<p style="color: green; margin-top: 15px;"><span style="font-size: 18px;">&#10003;</span> ';
        esc_html_e('This report has been edited.', 'church-attendance-reports');
        echo '</p>';
    }

    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Update the total whenever any attendance field changes.
        function updateTotal() {
            let total = 0;
            document.querySelectorAll('.attendance-field').forEach(field => {
                const val = parseInt(field.value, 10);
                if (!isNaN(val)) total += val;
            });
            document.getElementById('attendance_total').value = total;
        }
        document.querySelectorAll('.attendance-field').forEach(field => {
            field.addEventListener('input', updateTotal);
        });
        updateTotal();

        // Prevent future dates by setting the max attribute to today in the user's local timezone.
        const dateInput = document.getElementById('attendance_date');
        if (dateInput) {
            const today = new Date();
            // Convert to ISO and slice the date portion (yyyy-mm-dd).
            const tzOffset = today.getTimezoneOffset() * 60000;
            const localISODate = new Date(today.getTime() - tzOffset).toISOString().slice(0, 10);
            dateInput.setAttribute('max', localISODate);
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('church_attendance_form', 'car_render_attendance_form');

/**
 * Handle form submission.
 *
 * This function processes new reports and updates existing ones. It uses the
 * same logic as the original plugin but ensures the max date check is enforced
 * on the client side. The submission handler is triggered on every request
 * by hooking into the `init` action.
 */
function car_handle_attendance_form_submission() {
    if (!isset($_POST['car_attendance_form_submitted'])) {
        return;
    }
    if (!wp_verify_nonce($_POST['car_attendance_form_nonce'], 'car_attendance_form')) {
        return;
    }
    $user_id   = get_current_user_id();
    $church_id = get_user_meta($user_id, 'assigned_church', true);
    if (!$church_id) {
        return;
    }
    $date  = sanitize_text_field($_POST['attendance_date']);
    $data  = [];
    $total = 0;
    foreach (['in_person', 'online', 'discipleship', 'acl'] as $key) {
        $val                   = max(0, intval($_POST["attendance_$key"] ?? 0));
        $data["attendance_$key"] = $val;
        $total                += $val;
    }
    $data['attendance_total'] = $total;

    // Find existing report for this date/church.
    $existing = new WP_Query([
        'post_type'   => 'attendance_report',
        'post_status' => 'publish',
        'meta_query'  => [
            ['key' => 'church', 'value' => $church_id],
            ['key' => 'attendance_date', 'value' => $date],
        ],
    ]);
    if ($existing->have_posts()) {
        $report    = $existing->posts[0];
        $report_id = $report->ID;
        foreach ($data as $k => $v) {
            update_post_meta($report_id, $k, $v);
        }
        // Append to version history.
        $history   = get_post_meta($report_id, 'version_history', true) ?: [];
        $history[] = [
            'user'         => wp_get_current_user()->user_login,
            'timestamp'    => current_time('mysql'),
            'in_person'    => $data['attendance_in_person'],
            'online'       => $data['attendance_online'],
            'discipleship' => $data['attendance_discipleship'],
            'acl'          => $data['attendance_acl'],
            'total'        => $data['attendance_total'],
        ];
        update_post_meta($report_id, 'version_history', $history);
        update_post_meta($report_id, 'submitted_by', wp_get_current_user()->user_login);
        update_post_meta($report_id, 'submitted_at', current_time('mysql'));
        wp_redirect(add_query_arg('report_status', 'updated'));
        exit;
    } else {
        // Insert new report.
        $new_id = wp_insert_post([
            'post_type'   => 'attendance_report',
            'post_status' => 'publish',
            'post_title'  => 'Attendance Report – ' . $date,
        ]);
        foreach ($data as $k => $v) {
            update_post_meta($new_id, $k, $v);
        }
        update_post_meta($new_id, 'attendance_date', $date);
        update_post_meta($new_id, 'church', $church_id);
        update_post_meta($new_id, 'submitted_by', wp_get_current_user()->user_login);
        update_post_meta($new_id, 'submitted_at', current_time('mysql'));
        // Initialize version history.
        $history = [[
            'user'         => wp_get_current_user()->user_login,
            'timestamp'    => current_time('mysql'),
            'in_person'    => $data['attendance_in_person'],
            'online'       => $data['attendance_online'],
            'discipleship' => $data['attendance_discipleship'],
            'acl'          => $data['attendance_acl'],
            'total'        => $data['attendance_total'],
        ]];
        update_post_meta($new_id, 'version_history', $history);
        wp_redirect(add_query_arg('report_status', 'success'));
        exit;
    }
}
add_action('init', 'car_handle_attendance_form_submission');