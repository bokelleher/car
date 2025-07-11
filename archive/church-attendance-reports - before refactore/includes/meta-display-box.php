<?php
// includes/meta-display-box.php

function car_add_submission_meta_box() {
    add_meta_box(
        'car_submission_meta',
        'Submission Info',
        'car_render_submission_meta_box',
        'attendance_report',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'car_add_submission_meta_box');

function car_render_submission_meta_box($post) {
    $user_id = get_post_meta($post->ID, 'submitted_by', true);
    $timestamp = get_post_meta($post->ID, 'submitted_at', true);

    $user = $user_id ? get_userdata($user_id) : null;
    $username = $user ? esc_html($user->display_name) : '<em>Unknown</em>';
    $submitted_time = $timestamp ? esc_html(date('M d, Y g:i A', strtotime($timestamp))) : '<em>Unknown</em>';

    echo "<p><strong>Submitted By:</strong><br>$username</p>";
    echo "<p><strong>Submitted At:</strong><br>$submitted_time</p>";
}
