<?php
/**
 * AJAX Handler for Church Dashboard Reports
 * 
 * Provides JSON endpoint for fetching attendance reports
 * 
 * @package ChurchAttendanceReports
 * @since 1.1.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler to fetch all reports as JSON
 * Used by the church dashboard to populate the reports table
 */
function car_fetch_reports_json() {
    // Query all attendance reports
    $args = array(
        'post_type' => 'attendance_report',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Get all reports
        'orderby' => 'meta_value',
        'meta_key' => 'attendance_date',
        'order' => 'DESC'
    );
    
    $query = new WP_Query($args);
    $reports = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            
            // Get all the meta data
            $church_id = get_post_meta($post_id, 'church', true);
            $attendance_date = get_post_meta($post_id, 'attendance_date', true);
            $in_person = get_post_meta($post_id, 'attendance_in_person', true);
            $online = get_post_meta($post_id, 'attendance_online', true);
            $discipleship = get_post_meta($post_id, 'attendance_discipleship', true);
            $acl = get_post_meta($post_id, 'attendance_acl', true);
            $total = get_post_meta($post_id, 'attendance_total', true);
            $submitted_by = get_post_meta($post_id, 'submitted_by', true);
            $submitted_at = get_post_meta($post_id, 'submitted_at', true);
            $version_history = get_post_meta($post_id, 'version_history', true);
            
            // Build the report object
            $report = array(
                'id' => $post_id,
                'church_id' => intval($church_id),
                'attendance_date' => $attendance_date,
                'in_person' => intval($in_person),
                'online' => intval($online),
                'discipleship' => intval($discipleship),
                'acl' => intval($acl),
                'total' => intval($total),
                'submitted_by' => $submitted_by,
                'submitted_at' => $submitted_at,
                'versions' => is_array($version_history) ? $version_history : array()
            );
            
            $reports[] = $report;
        }
        wp_reset_postdata();
    }
    
    // Return JSON response
    wp_send_json($reports);
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_car_fetch_reports_json', 'car_fetch_reports_json');
add_action('wp_ajax_nopriv_car_fetch_reports_json', 'car_fetch_reports_json');

/**
 * AJAX handler to update attendance report
 * Used by the church dashboard to save inline edits
 */
function car_update_attendance() {
    // Security check
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
        return;
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
        return;
    }
    
    // Check if post exists and user has permission
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'attendance_report') {
        wp_send_json_error('Invalid post');
        return;
    }
    
    // Check user permissions
    $user = wp_get_current_user();
    $can_edit = in_array('church_admin', $user->roles) || 
                in_array('church_reporter', $user->roles) ||
                current_user_can('edit_attendance_reports');
    
    if (!$can_edit) {
        wp_send_json_error('Permission denied');
        return;
    }
    
    // Get the attendance values
    $in_person = isset($_POST['in_person']) ? intval($_POST['in_person']) : 0;
    $online = isset($_POST['online']) ? intval($_POST['online']) : 0;
    $discipleship = isset($_POST['discipleship']) ? intval($_POST['discipleship']) : 0;
    $acl = isset($_POST['acl']) ? intval($_POST['acl']) : 0;
    $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : $user->user_login;
    
    // Calculate total - ALL FOUR FIELDS
    $total = $in_person + $online + $discipleship + $acl;
    
    // Get current version history
    $version_history = get_post_meta($post_id, 'version_history', true);
    if (!is_array($version_history)) {
        $version_history = array();
    }
    
    // Add new version to history
    $version_history[] = array(
        'user' => $username,
        'timestamp' => current_time('mysql'),
        'in_person' => $in_person,
        'online' => $online,
        'discipleship' => $discipleship,
        'acl' => $acl,
        'total' => $total
    );
    
    // Update the post meta
    update_post_meta($post_id, 'attendance_in_person', $in_person);
    update_post_meta($post_id, 'attendance_online', $online);
    update_post_meta($post_id, 'attendance_discipleship', $discipleship);
    update_post_meta($post_id, 'attendance_acl', $acl);
    update_post_meta($post_id, 'attendance_total', $total);
    update_post_meta($post_id, 'version_history', $version_history);
    
    // Return success
    wp_send_json_success(array(
        'message' => 'Report updated successfully',
        'post_id' => $post_id,
        'total' => $total
    ));
}

// Register the update handler
add_action('wp_ajax_car_update_attendance', 'car_update_attendance');
add_action('wp_ajax_nopriv_car_update_attendance', 'car_update_attendance');