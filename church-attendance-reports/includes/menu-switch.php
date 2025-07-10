<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// --------------------------------------------
// Dynamic menu switching based on user roles
// --------------------------------------------
function car_dynamic_menu_by_role($args) {
    if (is_admin()) return $args;

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $role = $user->roles[0] ?? '';

        switch ($role) {
            case 'district_admin':
                $args['menu'] = 'District Admin Menu';
                break;
            case 'church_admin':
                $args['menu'] = 'Church Admin Menu';
                break;
            case 'church_reporter':
                $args['menu'] = 'Reporter Menu';
                break;
            case 'church_viewer':
                $args['menu'] = 'Viewer Menu';
                break;
            default:
