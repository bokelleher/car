<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Add meta boxes to the Attendance Report edit screen
add_action('add_meta_boxes', function () {
    add_meta_box('car_submission_info', 'Submission Info', 'car_render_submission_meta_box', 'attendance_report', 'side', 'default');
    add_meta_box('car_attendance_fields', 'Attendance Details', 'car_render_attendance_fields_meta_box', 'attendance_report', 'normal', 'default');
});

// Renders the submission info meta box
function car_render_submission_meta_box($post) {
    $submitted_by = get_post_meta($post->ID, 'submitted_by_name', true);
    $submitted_at = get_post_meta($post->ID, 'submitted_at', true);

    echo '<p><strong>Submitted By:</strong> ' . esc_html($submitted_by ?: '—') . '</p>';
    echo '<p><strong>Submitted At:</strong> ' . esc_html($submitted_at ?: '—') . '</p>';
}

// Renders the editable attendance fields
function car_render_attendance_fields_meta_box($post) {
    $fields = [
        'week_ending' => ['label' => 'Week Ending (YYYY-MM-DD)', 'type' => 'date'],
        'in_person_attendance' => ['label' => 'In-Person Attendance', 'type' => 'number'],
        'online_attendance' => ['label' => 'Online Attendance', 'type' => 'number'],
        'discipleship_attendance' => ['label' => 'Discipleship Attendance', 'type' => 'number'],
        'acl_count' => ['label' => 'Accountability Care List (ACL)', 'type' => 'number'],
    ];

    wp_nonce_field('car_save_attendance_fields', 'car_attendance_fields_nonce');

    echo '<table class="form-table">';
    foreach ($fields as $key => $info) {
        $value = esc_attr(get_post_meta($post->ID, $key, true));
        echo '<tr>';
        echo '<th><label for="' . esc_attr($key) . '">' . esc_html($info['label']) . '</label></th>';
        echo '<td><input type="' . esc_attr($info['type']) . '" name="' . esc_attr($key) . '" id="' . esc_attr($key) . '" value="' . $value . '" class="regular-text"></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Save the custom fields
add_action('save_post', function ($post_id) {
    if (!isset($_POST['car_attendance_fields_nonce']) || !wp_verify_nonce($_POST['car_attendance_fields_nonce'], 'car_save_attendance_fields')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = ['week_ending', 'in_person_attendance', 'online_attendance', 'discipleship_attendance', 'acl_count'];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            update_post_meta($post_id, $field, $value);
        }
    }
});
