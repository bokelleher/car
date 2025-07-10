<?php
/**
 * Plugin Name: Church Attendance Reports
 * Description: Manages church attendance reports with role-based access control.
 * Version: 1.0.10
 * Author: Bo Kelleher
 */

if (!defined('ABSPATH')) exit;

define('CHURCH_ATTENDANCE_PLUGIN_FILE', __FILE__);
define('CAR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load modular plugin files
require_once plugin_dir_path(__FILE__) . 'includes/roles.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/taxonomy-church.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-user-church-list.php';
require_once plugin_dir_path(__FILE__) . 'includes/report-meta.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-columns.php';
require_once plugin_dir_path(__FILE__) . 'includes/meta-display-box.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-report-detail.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-district-report.php';

// Swap navigation menu based on custom user roles
function car_dynamic_menu_by_role($args) {
    if (is_admin()) {
        return $args; // Skip for admin dashboard
    }

    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $role = $user->roles[0] ?? '';

        switch ($role) {
            case 'district_admin':
                $args['menu'] = 'District Admin Menu'; // Match the exact menu name
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
                $args['menu'] = 'Default Logged-in Menu';
        }
    } else {
        $args['menu'] = 'Guest Menu'; // Menu for non-logged-in users
    }

    return $args;
}
add_filter('wp_nav_menu_args', 'car_dynamic_menu_by_role');