<?php
// includes/capabilities.php

function car_add_custom_attendance_report_caps() {
    // Administrators get full access including delete
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $admin_caps = [
            'edit_attendance_report',
            'read_attendance_report',
            'delete_attendance_report',
            'delete_attendance_reports',
            'delete_others_attendance_reports',
            'delete_private_attendance_reports',
            'delete_published_attendance_reports',
            'edit_attendance_reports',
            'edit_others_attendance_reports',
            'publish_attendance_reports',
            'read_private_attendance_reports',
            'create_attendance_reports',
        ];
        
        foreach ($admin_caps as $cap) {
            $admin_role->add_cap($cap);
        }
    }
    
    // District Admins get read/edit but NO delete
    $district_role = get_role('district_admin');
    if ($district_role) {
        $district_caps = [
            'edit_attendance_report',
            'read_attendance_report',
            'edit_attendance_reports',
            'edit_others_attendance_reports',
            'publish_attendance_reports',
            'read_private_attendance_reports',
            'create_attendance_reports',
        ];
        
        foreach ($district_caps as $cap) {
            $district_role->add_cap($cap);
        }
        
        // Explicitly remove delete capabilities from district admins
        $district_role->remove_cap('delete_attendance_report');
        $district_role->remove_cap('delete_attendance_reports');
        $district_role->remove_cap('delete_others_attendance_reports');
        $district_role->remove_cap('delete_private_attendance_reports');
        $district_role->remove_cap('delete_published_attendance_reports');
    }
}
add_action('init', 'car_add_custom_attendance_report_caps');

/**
 * Extra security: Prevent deletion of attendance reports by non-administrators
 * This is a safety net in case capabilities are somehow bypassed
 */
add_filter('user_has_cap', 'car_restrict_attendance_report_deletion', 10, 4);
function car_restrict_attendance_report_deletion($allcaps, $caps, $args, $user) {
    // Check if this is a delete operation on an attendance report
    if (isset($args[0]) && strpos($args[0], 'delete') !== false) {
        if (isset($args[2]) && get_post_type($args[2]) === 'attendance_report') {
            // Only administrators (super admins) can delete
            if (!in_array('administrator', $user->roles)) {
                // Remove all delete capabilities for this specific request
                $allcaps['delete_attendance_report'] = false;
                $allcaps['delete_attendance_reports'] = false;
                $allcaps['delete_others_attendance_reports'] = false;
                $allcaps['delete_private_attendance_reports'] = false;
                $allcaps['delete_published_attendance_reports'] = false;
            }
        }
    }
    
    return $allcaps;
}

/**
 * Hide the delete action links for non-administrators in the admin list
 */
add_filter('post_row_actions', 'car_remove_delete_action_for_non_admins', 10, 2);
function car_remove_delete_action_for_non_admins($actions, $post) {
    if ($post->post_type === 'attendance_report') {
        $user = wp_get_current_user();
        
        // Only administrators can see delete actions
        if (!in_array('administrator', $user->roles)) {
            unset($actions['trash']);
            unset($actions['delete']);
        }
    }
    
    return $actions;
}

/**
 * Add a visual indicator in the admin list showing who can delete
 */
add_action('admin_notices', 'car_show_delete_permission_notice');
function car_show_delete_permission_notice() {
    global $pagenow;
    
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'attendance_report') {
        $user = wp_get_current_user();
        
        if (!in_array('administrator', $user->roles)) {
            ?>
            <div class="notice notice-info">
                <p><strong>Note:</strong> Only Super Administrators can delete attendance reports. You can view and edit reports, but cannot delete them.</p>
            </div>
            <?php
        }
    }
}