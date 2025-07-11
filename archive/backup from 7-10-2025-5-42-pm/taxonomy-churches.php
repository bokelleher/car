<?php
// taxonomy-churches.php

// Add Pastor Email field to Add form
function car_church_taxonomy_add_form_fields() {
    ?>
    <div class="form-field term-group">
        <label for="pastor_email">Pastor Email</label>
        <input type="email" id="pastor_email" name="pastor_email" value="" />
    </div>
    <?php
}
add_action('church_add_form_fields', 'car_church_taxonomy_add_form_fields');

// Add Pastor Email field to Edit form
function car_church_taxonomy_edit_form_fields($term) {
    $email = get_term_meta($term->term_id, 'pastor_email', true);
    ?>
    <tr class="form-field term-group-wrap">
        <th scope="row"><label for="pastor_email">Pastor Email</label></th>
        <td><input type="email" id="pastor_email" name="pastor_email" value="<?php echo esc_attr($email); ?>" /></td>
    </tr>
    <?php
}
add_action('church_edit_form_fields', 'car_church_taxonomy_edit_form_fields');

// Save Pastor Email field
function car_save_church_taxonomy_meta($term_id) {
    if (isset($_POST['pastor_email'])) {
        update_term_meta($term_id, 'pastor_email', sanitize_email($_POST['pastor_email']));
    }
}
add_action('created_church', 'car_save_church_taxonomy_meta');
add_action('edited_church', 'car_save_church_taxonomy_meta');
