<?php
/**
 * Plugin Name: Church Attendance Reports
 * Description: Manages church attendance reports with role-based access control.
 * Version: 1.0.23
 * Author: Bo Kelleher
 */

error_log('✅ shortcode-church-dashboard.php was loaded');

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
require_once plugin_dir_path(__FILE__) . 'includes/access-control.php';
require_once plugin_dir_path(__FILE__) . 'includes/menu-switch.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcode-church-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/car-generate-churches.php';
require_once plugin_dir_path(__FILE__) . 'includes/capabilities.php';

function car_enqueue_assets() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('car-dashboard-charts', plugin_dir_url(__FILE__) . 'assets/js/dashboard-charts.js', ['chartjs'], null, true);
}
add_action('wp_enqueue_scripts', 'car_enqueue_assets');