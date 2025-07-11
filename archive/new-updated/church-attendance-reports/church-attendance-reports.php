<?php
/**
 * Plugin Name: Church Attendance Reports
 * Description: Manages church attendance reports with role-based access control.
 * Version: 1.0.17
 * Author: Bo Kelleher
 */

if (!defined('ABSPATH')) exit;

define('CHURCH_ATTENDANCE_PLUGIN_FILE', __FILE__);
define('CAR_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once plugin_dir_path(__FILE__) . 'includes/admin-reports.php';
