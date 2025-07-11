<?php
// Customize Church taxonomy list columns
add_filter('manage_edit-church_columns', function ($columns) {
    unset($columns['description']); // Remove Description column

    $columns['pastor'] = 'Pastor';
    $columns['city'] = 'City';
    $columns['website'] = 'Website';

    return $columns;
}, 10);

// Populate custom Church columns
add_filter('manage_church_custom_column', function ($output, $column, $term_id) {
    global $wpdb;

    $church = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}car_churches WHERE term_id = %d", $term_id)
    );

    if (!$church) return '';

    switch ($column) {
        case 'pastor':
            return esc_html($church->pastor_name);
        case 'city':
            // Extract city from address if possible
            if (!empty($church->address)) {
                preg_match('/,?\s*([^,]+),\s+[A-Z]{2}\s+\d{5}/', $church->address, $matches);
                return esc_html($matches[1] ?? '');
            }
            return '';
        case 'website':
            return $church->website 
                ? '<a href="' . esc_url($church->website) . '" target="_blank">' . esc_html(parse_url($church->website, PHP_URL_HOST) ?? $church->website) . '</a>'
                : '';
        default:
            return $output;
    }
}, 10, 3);
