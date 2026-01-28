<?php
// Add "Settings" link on the Plugins list page
function car_plugin_action_links($links) {
    // Point the Settings action link to the new Attendance Reports settings page
    $url = admin_url('edit.php?post_type=attendance_report&page=car_attendance_settings');
    $settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . CAR_PLUGIN_BASENAME, 'car_plugin_action_links');

// Add settings page under Settings → General
function car_add_settings_page() {
    // Attach the settings page as a submenu under Attendance Reports
    add_submenu_page(
        'edit.php?post_type=attendance_report', // Parent slug
        'Attendance Reports Settings',         // Page title
        'Settings',                           // Menu title
        'manage_options',                     // Capability
        'car_attendance_settings',            // Menu slug
        'car_render_settings_page'            // Callback to render page
    );
}
add_action('admin_menu', 'car_add_settings_page');

// Render settings form
function car_render_settings_page() {
    ?>
    <div class="wrap">
        <h1>Church Attendance Plugin Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('car_settings_group');
            do_settings_sections('car_attendance_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
function car_register_settings() {
    register_setting('car_settings_group', 'car_form_instructions');
    register_setting('car_settings_group', 'car_report_locking');
    // Google Maps API key for Church Finder
    register_setting('car_settings_group', 'car_google_maps_api_key');

    // ClearStream API key for SMS reminders
    register_setting('car_settings_group', 'car_clearstream_api_key');

    add_settings_section('car_main_section', '', null, 'car_attendance_settings');

    add_settings_field(
        'car_form_instructions',
        'Form Instructions',
        'car_form_instructions_callback',
        'car_attendance_settings',
        'car_main_section'
    );

    add_settings_field(
        'car_report_locking',
        'Enable Report Locking',
        'car_report_locking_callback',
        'car_attendance_settings',
        'car_main_section'
    );

    // Add a field for the Google Maps API key used by the Church Finder
    add_settings_field(
        'car_google_maps_api_key',
        'Google Maps API Key',
        'car_google_maps_api_key_callback',
        'car_attendance_settings',
        'car_main_section'
    );

    // Add a field for the ClearStream API key used for SMS reminders
    add_settings_field(
        'car_clearstream_api_key',
        'SMS API Key',
        'car_clearstream_api_key_callback',
        'car_attendance_settings',
        'car_main_section'
    );
}
add_action('admin_init', 'car_register_settings');

function car_form_instructions_callback() {
    $value = esc_textarea(get_option('car_form_instructions', ''));
    echo '<textarea name="car_form_instructions" rows="5" cols="60">' . $value . '</textarea>';
    echo '<p class="description">Shown above the attendance form.</p>';
}

function car_report_locking_callback() {
    $checked = checked(1, get_option('car_report_locking', 0), false);
    echo '<input type="checkbox" name="car_report_locking" value="1" ' . $checked . ' /> Prevent edits after submission';
}

/**
 * Display the Google Maps API key input field.
 *
 * Allows administrators to enter the API key used by the Church Finder map.
 */
function car_google_maps_api_key_callback() {
    $value = esc_attr(get_option('car_google_maps_api_key', ''));
    echo '<input type="text" name="car_google_maps_api_key" id="car_google_maps_api_key" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">Enter your Google Maps JavaScript API key. This is required for the Church Finder map to load properly.</p>';
}

/**
 * Display the SMS API key input field.
 *
 * Allows administrators to enter the API key used for sending reminder texts via the SMS service.
 */
function car_clearstream_api_key_callback() {
    $value = esc_attr(get_option('car_clearstream_api_key', ''));
    echo '<input type="text" name="car_clearstream_api_key" id="car_clearstream_api_key" value="' . $value . '" class="regular-text" />';
    echo '<p class="description">Enter the API key used for sending attendance reminder texts. Leave blank to disable reminders.</p>';
}
