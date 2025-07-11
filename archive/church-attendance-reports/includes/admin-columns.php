<?php
// admin-columns.php

// Add custom columns to attendance report list
function car_add_custom_columns($columns) {
    unset($columns['date']);
    unset($columns['taxonomy-church']); // 🚫 Remove default taxonomy column

    $columns['church'] = 'Church';
    $columns['week_ending'] = 'Week Ending';
    $columns['in_person'] = 'In Person';
    $columns['online'] = 'Online';
    $columns['discipleship'] = 'Discipleship';
    $columns['acl'] = 'Accountability Care List';
    $columns['submitted_by'] = 'Submitted By';
    $columns['submitted_at'] = 'Submitted At';
    return $columns;
}

add_filter('manage_attendance_report_posts_columns', 'car_add_custom_columns');

// Populate custom column data
function car_render_custom_columns($column, $post_id) {
    switch ($column) {
        case 'church':
            $churches = wp_get_post_terms($post_id, 'church', ['fields' => 'names']);
            echo esc_html(implode(', ', $churches));
            break;

        case 'week_ending':
            echo esc_html(get_post_meta($post_id, 'week_ending', true));
            break;

        case 'in_person':
            echo intval(get_post_meta($post_id, 'attendance_total', true));
            break;

        case 'online':
            echo intval(get_post_meta($post_id, 'online_attendance', true));
            break;

        case 'discipleship':
            echo intval(get_post_meta($post_id, 'discipleship_attendance', true));
            break;

        case 'acl':
            echo intval(get_post_meta($post_id, 'acl_count', true));
            break;

        case 'submitted_by':
            $user_id = get_post_meta($post_id, 'submitted_by', true);
            $user = get_user_by('ID', $user_id);
            echo $user ? esc_html($user->first_name . ' ' . $user->last_name) : '<em>Unknown</em>';
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
    $columns['week_ending'] = 'week_ending';
    return $columns;
}
add_filter('manage_edit-attendance_report_sortable_columns', 'car_sortable_columns');

// Adjust the query to support sorting by meta fields
function car_sortable_columns_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('orderby') === 'week_ending') {
        $query->set('meta_key', 'week_ending');
        $query->set('orderby', 'meta_value');
    }
}
add_action('pre_get_posts', 'car_sortable_columns_orderby');
