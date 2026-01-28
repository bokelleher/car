// Show church dropdown on user profile
function car_show_church_dropdown($user) {
    if (!current_user_can('edit_users')) return;

    $assigned = wp_get_object_terms($user->ID, 'church', ['fields' => 'ids']);
    $terms = get_terms(['taxonomy' => 'church', 'hide_empty' => false]);

    ?>
    <h2>Church Affiliation</h2>
    <table class="form-table">
        <tr>
            <th><label for="assigned_church">Assigned Church</label></th>
            <td>
                <select name="assigned_church" id="assigned_church">
                    <option value="">-- None --</option>
                    <?php foreach ($terms as $term): ?>
                        <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected(in_array($term->term_id, $assigned)); ?>>
                            <?php echo esc_html($term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Assign this user to a church.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'car_show_church_dropdown');
add_action('edit_user_profile', 'car_show_church_dropdown');

// Save church assignment
function car_save_church_dropdown($user_id) {
    if (!current_user_can('edit_users')) return;
    if (isset($_POST['assigned_church'])) {
        $church_id = intval($_POST['assigned_church']);
        if ($church_id) {
            wp_set_object_terms($user_id, [$church_id], 'church', false);
        } else {
            wp_delete_object_term_relationships($user_id, 'church');
        }
    }
}
add_action('personal_options_update', 'car_save_church_dropdown');
add_action('edit_user_profile_update', 'car_save_church_dropdown');
