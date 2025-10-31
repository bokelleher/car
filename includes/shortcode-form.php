<?php
// shortcode-form.php

function car_render_attendance_form() {
    if (!is_user_logged_in()) return '<p>You must be logged in to submit attendance.</p>';

    $user_id = get_current_user_id();
    $church_id = get_user_meta($user_id, 'assigned_church', true);
    if (!$church_id) return '<p><strong>Error:</strong> No church assigned to your account.</p>';

    $is_admin = current_user_can('church_admin') || current_user_can('administrator');

    ob_start();

    $editing = false;
    $existing_report = null;

    // Check for existing report (by attendance_date + church)
    $today = current_time('Y-m-d');
    $query = new WP_Query([
        'post_type' => 'attendance_report',
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => 'church',
                'value' => $church_id,
            ],
            [
                'key' => 'attendance_date',
                'value' => $today,
            ]
        ]
    ]);
    if ($query->have_posts()) {
        $editing = true;
        $existing_report = $query->posts[0];
    }

    // Begin form
    ?>
    <form method="post">
        <input type="hidden" name="car_attendance_form_submitted" value="1">
        <?php wp_nonce_field('car_attendance_form', 'car_attendance_form_nonce'); ?>

        <h2>Report Attendance</h2>

        <label>Report Date:</label><br>
        <input type="date" name="attendance_date" value="<?php echo esc_attr($today); ?>" <?php echo $is_admin ? '' : 'readonly'; ?> required><br><br>

        <table style="width: 100%; max-width: 600px; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $categories = [
                    'in_person'    => 'In-Person',
                    'online'       => 'Online',
                    'discipleship' => 'Discipleship',
                    'acl'          => 'ACL'
                ];
                $totals = [];

                foreach ($categories as $key => $label) {
                    $meta_key = "attendance_{$key}";
                    $value = $editing ? get_post_meta($existing_report->ID, $meta_key, true) : '';
                    $totals[$key] = $value ?: 0;
                    echo "<tr><td>{$label}</td><td><input type='number' name='attendance_{$key}' value='" . esc_attr($totals[$key]) . "' min='0' required class='attendance-field'></td></tr>";
                }
                ?>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><input type="number" id="attendance_total" name="attendance_total" value="0" readonly></td>
                </tr>
            </tbody>
        </table>

        <br><button type="submit"><?php echo $editing ? 'Update Report' : 'Submit Report'; ?></button>
    </form>

    <?php
    // Version History Table
    if ($editing) {
        $report_id = $existing_report->ID;
        $report_date = get_post_meta($report_id, 'attendance_date', true);
        $formatted_date = $report_date ? date('M j, Y', strtotime($report_date)) : 'Unknown Date';
        
        $history = get_post_meta($report_id, 'version_history', true);
        if (!empty($history) && is_array($history)) {
            echo '<h3>Version History for Report ID: ' . esc_html($report_id) . ' on ' . esc_html($formatted_date) . '</h3>';
            echo '<table style="width: 100%; border-collapse: collapse;">';
            echo '<thead><tr style="background: #f5f5f5;">';
            echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">User</th>';
            echo '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #ddd;">Date</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">In-Person</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Online</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Discipleship</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">ACL</th>';
            echo '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #ddd;">Total</th>';
            echo '</tr></thead><tbody>';
            
            foreach ($history as $entry) {
                $timestamp = $entry['timestamp'] ?? $entry['date'] ?? ''; // Handle both old and new format
                $date_display = $timestamp ? date('M d, Y g:i A', strtotime($timestamp)) : 'Unknown date';
                
                echo '<tr style="border-bottom: 1px solid #ddd;">';
                echo '<td style="padding: 10px;">' . esc_html($entry['user'] ?? 'Unknown') . '</td>';
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

        echo '<p style="color: green; margin-top: 15px;"><span style="font-size: 18px;">✔</span> This report has been edited.</p>';
    }
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
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
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('church_attendance_form', 'car_render_attendance_form');

// Form submission handler
function car_handle_attendance_form_submission() {
    if (!isset($_POST['car_attendance_form_submitted'])) return;

    if (!wp_verify_nonce($_POST['car_attendance_form_nonce'], 'car_attendance_form')) return;

    $user_id = get_current_user_id();
    $church_id = get_user_meta($user_id, 'assigned_church', true);
    if (!$church_id) return;

    $date = sanitize_text_field($_POST['attendance_date']);
    $data = [];
    $total = 0;

    foreach (['in_person', 'online', 'discipleship', 'acl'] as $key) {
        $val = max(0, intval($_POST["attendance_$key"] ?? 0));
        $data["attendance_$key"] = $val;
        $total += $val;
    }

    $data['attendance_total'] = $total;

    // Check for existing report
    $existing = new WP_Query([
        'post_type' => 'attendance_report',
        'post_status' => 'publish',
        'meta_query' => [
            ['key' => 'church', 'value' => $church_id],
            ['key' => 'attendance_date', 'value' => $date],
        ]
    ]);

    if ($existing->have_posts()) {
        $report = $existing->posts[0];
        $report_id = $report->ID;

        foreach ($data as $k => $v) update_post_meta($report_id, $k, $v);

        // Update version history
        $history = get_post_meta($report_id, 'version_history', true) ?: [];
        $history[] = [
            'user' => wp_get_current_user()->user_login, 
            'timestamp' => current_time('mysql'),
            'in_person' => $data['attendance_in_person'],
            'online' => $data['attendance_online'],
            'discipleship' => $data['attendance_discipleship'],
            'acl' => $data['attendance_acl'],
            'total' => $data['attendance_total']
        ];
        update_post_meta($report_id, 'version_history', $history);

        update_post_meta($report_id, 'submitted_by', wp_get_current_user()->user_login);
        update_post_meta($report_id, 'submitted_at', current_time('mysql'));

        wp_redirect(add_query_arg('report_status', 'updated'));
        exit;
    } else {
        $new_id = wp_insert_post([
            'post_type' => 'attendance_report',
            'post_status' => 'publish',
            'post_title' => "Attendance Report – $date",
        ]);
        foreach ($data as $k => $v) update_post_meta($new_id, $k, $v);
        update_post_meta($new_id, 'attendance_date', $date);
        update_post_meta($new_id, 'church', $church_id);
        update_post_meta($new_id, 'submitted_by', wp_get_current_user()->user_login);
        update_post_meta($new_id, 'submitted_at', current_time('mysql'));

        $history = [[
            'user' => wp_get_current_user()->user_login, 
            'timestamp' => current_time('mysql'),
            'in_person' => $data['attendance_in_person'],
            'online' => $data['attendance_online'],
            'discipleship' => $data['attendance_discipleship'],
            'acl' => $data['attendance_acl'],
            'total' => $data['attendance_total']
        ]];
        update_post_meta($new_id, 'version_history', $history);

        wp_redirect(add_query_arg('report_status', 'success'));
        exit;
    }
}
add_action('init', 'car_handle_attendance_form_submission');