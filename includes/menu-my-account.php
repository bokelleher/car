<?php
/**
 * Menu injection for the My Account page.
 *
 * This file hooks into the WordPress nav menu system to insert a
 * "My Account" link for logged-in users. The link is appended
 * to the end of the menu for each role-specific menu defined by the
 * Church Attendance Reports plugin. By appending the link, it will
 * naturally appear near the Logout link which is typically the last
 * menu item.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inject a My Account link into role-based menus.
 *
 * @param string $items The HTML list items for the menu.
 * @param stdClass $args The menu arguments object.
 * @return string Modified menu items.
 */
function car_inject_my_account_menu_item($items, $args) {
    // Only modify menus for logged-in users on the front end
    if (is_admin() || !is_user_logged_in()) {
        return $items;
    }

    // Determine the name of the menu currently being rendered
    $menu_name = isset($args->menu) ? (string) $args->menu : '';

    // Define menus for which we should inject the My Account link. These
    // names correspond to the role-specific menus registered by the plugin.
    $logged_in_menus = [
        'District Admin Menu',
        'Church Admin Menu',
        'Reporter Menu',
        'Viewer Menu',
        'Default Logged-in Menu',
    ];

    // If this menu is not one of our target menus, return unchanged
    if ($menu_name && !in_array($menu_name, $logged_in_menus, true)) {
        return $items;
    }

    // Build the My Account list item. Use site_url() to build the URL
    // dynamically, so it respects the current domain and any multisite
    // configuration.
    $account_url = esc_url(site_url('/my-account/'));
    $my_item = '<li class="menu-item menu-item-my-account"><a href="' . $account_url . '">My Account</a></li>';

    // Append the My Account item to the menu items. This ensures
    // the link appears near the logout link, which is typically the
    // final item in logged-in menus.
    return $items . $my_item;
}
add_filter('wp_nav_menu_items', 'car_inject_my_account_menu_item', 20, 2);