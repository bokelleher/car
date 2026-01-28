<?php
// admin-columns.php

// Add custom columns to attendance report list
function car_add_custom_columns($columns) {
    unset($columns['date']);
    unset($columns['taxonomy-church']); // 🚫 Remove default taxonomy column

    // Reorder columns with Report ID first
    $new_columns = array();
    $new_columns['cb'] = $columns['cb']; // Checkbox
    $new_columns['report_id'] = 'Report ID';
    $new_columns['title'] = $columns['title']; // Title
    $new_columns['church'] = 'Church';
    $new_columns['week_ending'] = 'Week Ending';
    $new_columns['in_person'] = 'In Person';
    $new_columns['online'] = 'Online';
    $new_columns['discipleship'] = 'Discipleship';
    $new_columns['acl'] = 'Accountability Care List';
    $new_columns['submitted_by'] = 'Submitted By';
    $new_columns['submitted_at'] = 'Submitted At';
    
    return $new_columns;
}

add_filter('manage_attendance_report_posts_columns', 'car_add_custom_columns');

// Populate custom column data
function car_render_custom_columns($column, $post_id) {
    switch ($column) {
        case 'report_id':
            echo '<strong>' . intval($post_id) . '</strong>';
            break;

        case 'church':
            // Get church ID from meta and look up the term
            $church_id = get_post_meta($post_id, 'church', true);
            if ($church_id) {
                $church_term = get_term($church_id, 'church');
                echo ($church_term && !is_wp_error($church_term)) ? esc_html($church_term->name) : '';
            }
            break;

        case 'week_ending':
            echo esc_html(get_post_meta($post_id, 'attendance_date', true));
            break;

        case 'in_person':
            echo intval(get_post_meta($post_id, 'attendance_in_person', true));
            break;

        case 'online':
            echo intval(get_post_meta($post_id, 'attendance_online', true));
            break;

        case 'discipleship':
            echo intval(get_post_meta($post_id, 'attendance_discipleship', true));
            break;

        case 'acl':
            echo intval(get_post_meta($post_id, 'attendance_acl', true));
            break;

        case 'submitted_by':
            // Submitted by is stored as username string, not user ID
            $username = get_post_meta($post_id, 'submitted_by', true);
            echo $username ? esc_html($username) : '<em>Unknown</em>';
            break;

        case 'submitted_at':
            $timestamp = get_post_meta($post_id, 'submitted_at', true);
            echo $timestamp ? esc_html(date('M d, Y g:i A', strtotime($timestamp))) : '<em>Not recorded</em>';
            break;
    }
}
add_action('manage_attendance_report_posts_custom_column', 'car_render_custom_columns', 10, 2);

// Make some columns sortable
function car_sortable_columns($columns) {
    $columns['week_ending'] = 'attendance_date';
    return $columns;
}
add_filter('manage_edit-attendance_report_sortable_columns', 'car_sortable_columns');

// Adjust the query to support sorting by meta fields
function car_sortable_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('orderby') === 'attendance_date') {
        $query->set('meta_key', 'attendance_date');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'car_sortable_columns_orderby');