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