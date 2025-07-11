<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// --------------------------------------------
// Redirect users on login based on role
// --------------------------------------------
function car_login_redirect($redirect_to, $request, $user) {
    if (!is_wp_error($user) && isset($user->roles[0])) {
        switch ($user->roles[0]) {
            case 'district_admin':
                return home_url('/district-dashboard');
            case 'church_admin':
                return home_url('/church-dashboard');
            case 'church_reporter':
                return home_url('/report-attendance');
            case 'church_viewer':
                return home_url('/view-reports');
            default:
                return home_url();
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'car_login_redirect', 10, 3);

// --------------------------------------------
// Hide admin bar unless user is Super Admin or District Admin
// --------------------------------------------
function car_hide_admin_bar() {
    if (!is_super_admin() && !current_user_can('district_admin')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'car_hide_admin_bar');

// --------------------------------------------
// Block wp-admin access for unauthorized roles
// --------------------------------------------
function car_block_wp_admin_access() {
    if (is_admin() && !defined('DOING_AJAX')) {
        $user = wp_get_current_user();

        if (
            !is_super_admin() &&
            !in_array('district_admin', $user->roles)
        ) {
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('init', 'car_block_wp_admin_access');
