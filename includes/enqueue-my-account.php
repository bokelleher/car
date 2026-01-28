<?php
/**
 * Enqueue styles for the My Account page.
 *
 * This file registers and enqueues a dedicated stylesheet when the
 * "My Account" page is viewed by a logged-in user. The stylesheet
 * provides consistent styling for the account management form using
 * the same visual language as the rest of the Church Attendance
 * Reports dashboard (cards, inputs, buttons, alerts, etc.).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue the My Account page CSS when appropriate.
 */
function car_enqueue_my_account_assets() {
    if (!is_user_logged_in()) {
        return;
    }
    // Only enqueue on the My Account page. We match by slug so the
    // stylesheet is not loaded on other pages.
    if (is_page('my-account')) {
        wp_enqueue_style(
            'car-my-account',
            plugin_dir_url(__FILE__) . '../assets/css/car-my-account.css',
            [],
            '1.0.0'
        );
    }
}
add_action('wp_enqueue_scripts', 'car_enqueue_my_account_assets');