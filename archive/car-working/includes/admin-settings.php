<?php
// Add "Settings" link on the Plugins list page
function car_plugin_action_links($links) {
    $url = admin_url('options-general.php?page=car_attendance_settings');
    $settings_link = '<a href="' . esc_url($url) . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . CAR_PLUGIN_BASENAME, 'car_plugin_action_links');

// Add settings page under Settings → General
function car_add_settings_page() {
    add_options_page(
        'Church Attendance Settings',
        'Church Attendance Settings',
        'manage_options',
        'car_attendance_settings',
        'car_render_settings_page'
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
