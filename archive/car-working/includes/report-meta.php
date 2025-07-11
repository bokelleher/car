<?php
// includes/report-meta.php

// 1. Capture submitted_by and submitted_at on first publish
add_action('save_post_attendance_report', 'car_capture_submission_metadata', 10, 3);
function car_capture_submission_metadata($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'attendance_report') return;
    if ($update) return;
    if ($post->post_status !== 'publish') return;

    $user_id = get_current_user_id();
    $timestamp = current_time('mysql');

    update_post_meta($post_id, 'submitted_by', $user_id);
    update_post_meta($post_id, 'submitted_at', $timestamp);
}

// 2. Register ACL in REST API
register_post_meta('attendance_report', 'acl', [
    'show_in_rest' => true,
    'single' => true,
    'type' => 'string',
    'sanitize_callback' => 'sanitize_text_field',
    'auth_callback' => function() {
        return current_user_can('edit_posts');
    }
]);

// 3. Add Attendance Meta Box
add_action('add_meta_boxes', function () {
    add_meta_box('car_attendance_data', 'Attendance Details', 'car_render_attendance_fields', 'attendance_report', 'normal', 'default');
});

// 4. Render Attendance Fields (no duplicates)
function car_render_attendance_fields($post) {
    wp_nonce_field('car_save_attendance_fields', 'car_attendance_fields_nonce');

    $fields = [
        'week_ending' => ['label' => 'Week Ending', 'type' => 'date'],
        'in_person_attendance' => ['label' => 'In-Person Attendance', 'type' => 'number'],
        'online_attendance' => ['label' => 'Online Attendance', 'type' => 'number'],
        'discipleship_attendance' => ['label' => 'Discipleship Attendance', 'type' => 'number'],
        'acl_count' => ['label' => 'Accountability Care List (ACL)', 'type' => 'number'],
    ];

    echo '<table class="form-table">';
    foreach ($fields as $key => $info) {
        $value = esc_attr(get_post_meta($post->ID, $key, true));
        echo '<tr>';
        echo '<th><label for="' . esc_attr($key) . '">' . esc_html($info['label']) . '</label></th>';
        echo '<td><input type="' . esc_attr($info['type']) . '" name="' . esc_attr($key) . '" value="' . $value . '" class="regular-text" /></td>';
        echo '</tr>';
    }

    echo '<tr><th><label for="car_duplicate_override">Duplicate Override:</label></th>';
    echo '<td><label><input type="checkbox" name="car_duplicate_override" value="1"> Allow duplicate week/church</label></td></tr>';
    echo '</table>';
}

// 5. Save Fields & Warn if Duplicate
add_action('save_post_attendance_report', function($post_id, $post, $update) {
    if (!isset($_POST['car_attendance_fields_nonce']) || !wp_verify_nonce($_POST['car_attendance_fields_nonce'], 'car_save_attendance_fields')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (wp_is_post_revision($post_id)) return;

    // Save fields
    $fields = ['week_ending', 'in_person_attendance', 'online_attendance', 'discipleship_attendance', 'acl_count'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }

    // Duplicate detection
    $override = isset($_POST['car_duplicate_override']) && $_POST['car_duplicate_override'] === '1';
    $week_ending = isset($_POST['week_ending']) ? sanitize_text_field($_POST['week_ending']) : '';
    $church_term = isset($_POST['tax_input']['church'][0]) ? intval($_POST['tax_input']['church'][0]) : 0;

    if ($week_ending && $church_term && !$override) {
        $duplicates = get_posts([
            'post_type' => 'attendance_report',
            'post_status' => 'publish',
            'exclude' => [$post_id],
            'meta_query' => [
                ['key' => 'week_ending', 'value' => $week_ending],
            ],
            'tax_query' => [
                ['taxonomy' => 'church', 'field' => 'term_id', 'terms' => [$church_term]],
            ],
        ]);

        if (!empty($duplicates)) {
            wp_die(
                '<p><strong>⚠️ Duplicate Report Detected:</strong> A report for this week and church already exists.</p>
                <p><a href="' . esc_url(get_edit_post_link($post_id)) . '">Go back and override</a> by checking the duplicate override box.</p>',
                'Duplicate Report',
                ['back_link' => true]
            );
        }
    }
}, 10, 3);
