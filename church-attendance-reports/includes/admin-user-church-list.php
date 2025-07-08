<?php
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
