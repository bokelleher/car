<?php
// includes/report-meta.php

// Capture submitted_by and submitted_at when attendance report is created
add_action('save_post_attendance_report', 'car_capture_submission_metadata', 10, 3);

function car_capture_submission_metadata($post_id, $post, $update) {
    // Avoid recursion or irrelevant saves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if ($post->post_type !== 'attendance_report') return;

    // Only run on first publish
    if ($update) return;

    // Only when post is published
    if ($post->post_status !== 'publish') return;

    $user_id = get_current_user_id();
    $timestamp = current_time('mysql');

    update_post_meta($post_id, 'submitted_by', $user_id);
    update_post_meta($post_id, 'submitted_at', $timestamp);
}

register_post_meta('attendance_report', 'acl', [
    'show_in_rest' => true,
    'single' => true,
    'type' => 'string',
    'sanitize_callback' => 'sanitize_text_field',
    'auth_callback' => function() {
        return current_user_can('edit_posts');
    }
]);

// Hook into saving the post to enforce duplicate check
add_action('save_post_attendance_report', function($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (wp_is_post_revision($post_id)) return;

    $override = isset($_POST['car_duplicate_override']) && $_POST['car_duplicate_override'] === '1';

    $week_ending = isset($_POST['car_week_ending']) ? sanitize_text_field($_POST['car_week_ending']) : '';
    $church_term = isset($_POST['tax_input']['church'][0]) ? intval($_POST['tax_input']['church'][0]) : 0;

    if ($week_ending && $church_term && !$override) {
        $duplicates = get_posts([
            'post_type' => 'attendance_report',
            'post_status' => 'publish',
            'exclude' => [$post_id],
            'meta_query' => [
                [
                    'key' => 'week_ending',
                    'value' => $week_ending,
                ],
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'church',
                    'field' => 'term_id',
                    'terms' => [$church_term],
                ],
            ],
        ]);

        if (!empty($duplicates)) {
            // Prevent saving and redirect back with error
            wp_die(
                '<p><strong>⚠️ Duplicate Report Detected:</strong> A report for this week and church already exists.</p>
                <p><a href="' . esc_url(get_edit_post_link($post_id)) . '">Go back and override</a> by checking the duplicate override box.</p>',
                'Duplicate Report',
                ['back_link' => true]
            );
        }
    }
}, 10, 3);