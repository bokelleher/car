<?php
/**
 * Plugin Name: Church Attendance Reports
 * Description: Manages church attendance reports with a custom church management interface.
 * Version: 1.1.0
 * Author: Bo Kelleher
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/admin-churches-page.php';

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'attendance_report_page_car_manage_churches') {
        wp_enqueue_script('car-church-ui', plugin_dir_url(__FILE__) . 'assets/js/church-ui.js', ['jquery'], null, true);
    }
});
