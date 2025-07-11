<?php
// Church Assignment UI in User Profile
add_action('show_user_profile', 'car_show_church_selector');
add_action('edit_user_profile', 'car_show_church_selector');

function car_show_church_selector($user) {
    $current_user = wp_get_current_user();
    $is_district_or_super_admin = current_user_can('manage_options') || in_array('district_admin', (array) $current_user->roles);

    if (!$is_district_or_super_admin) return;

    $user_church = get_user_meta($user->ID, 'assigned_church', true);
    $churches = get_terms(['taxonomy' => 'church', 'hide_empty' => false]);

    ?>
    <h3>Church Assignment</h3>
    <table class="form-table">
        <tr>
            <th><label for="assigned_church">Assigned Church</label></th>
            <td>
                <select name="assigned_church" id="assigned_church">
                    <option value="">— Select Church —</option>
                    <?php foreach ($churches as $church): ?>
                        <option value="<?php echo esc_attr($church->term_id); ?>" <?php selected($user_church, $church->term_id); ?>>
                            <?php echo esc_html($church->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Assign this user to a church.</p>
            </td>
        </tr>
    </table>
    <?php
}

// Save Church Assignment
add_action('personal_options_update', 'car_save_church_selector');
add_action('edit_user_profile_update', 'car_save_church_selector');

function car_save_church_selector($user_id) {
    $current_user = wp_get_current_user();
    $can_assign = current_user_can('manage_options') || in_array('district_admin', (array) $current_user->roles);

    if (!$can_assign || !current_user_can('edit_user', $user_id)) return;

    if (isset($_POST['assigned_church'])) {
        update_user_meta($user_id, 'assigned_church', intval($_POST['assigned_church']));
    }
}

// View Users Grouped by Church
function car_render_affiliated_users_by_church() {
    if (!current_user_can('edit_users')) return;

    $churches = get_terms(['taxonomy' => 'church', 'hide_empty' => false]);
    $roles = [
        '' => 'All Roles',
        'church_admin' => 'Church Admin',
        'church_reporter' => 'Church Reporter',
        'church_viewer' => 'Church Viewer',
        'district_admin' => 'District Admin'
    ];

    echo '<div class="wrap"><h1>Users by Church</h1>';
    foreach ($churches as $church) {
        $church_id = $church->term_id;
        $selected_role = $_GET["role_{$church_id}"] ?? '';
        echo '<h2>' . esc_html($church->name) . '</h2>';
        echo '<form method="GET"><input type="hidden" name="page" value="users-by-church">';
        echo '<select name="role_' . esc_attr($church_id) . '" onchange="this.form.submit()">';
        foreach ($roles as $key => $label) {
            echo "<option value=\"$key\" " . selected($selected_role, $key, false) . ">$label</option>";
        }
        echo '</select></form>';

        $args = ['meta_key' => 'assigned_church', 'meta_value' => $church_id];
        if ($selected_role) $args['role'] = $selected_role;

        $users = get_users($args);
        if ($users) {
            echo '<ul>';
            foreach ($users as $user) {
                echo '<li>' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p><em>No users found.</em></p>';
        }
    }
    echo '</div>';
}
