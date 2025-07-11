<?php
function car_render_add_church_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_church_nonce']) && wp_verify_nonce($_POST['car_church_nonce'], 'car_add_church')) {
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $pastor_name = sanitize_text_field($_POST['pastor_name']);
        $pastor_email = sanitize_email($_POST['pastor_email']);
        $phone = sanitize_text_field($_POST['phone_number']);
        $website = esc_url_raw($_POST['website']);
        $address = sanitize_text_field($_POST['address']);
        $slug = sanitize_title($name);

        // Insert term
        $term = wp_insert_term($name, 'church', ['description' => $description, 'slug' => $slug]);

        if (!is_wp_error($term)) {
            global $wpdb;
            $table = $wpdb->prefix . 'car_churches';
            $wpdb->insert($table, [
                'term_id' => $term['term_id'],
                'name' => $name,
                'pastor_name' => $pastor_name,
                'pastor_email' => $pastor_email,
                'phone_number' => $phone,
                'website' => $website,
                'address' => $address,
                'created_at' => current_time('mysql')
            ]);
            echo '<div class="notice notice-success"><p>Church added successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error: ' . $term->get_error_message() . '</p></div>';
        }
    }
?>
<div class="wrap">
    <h1>Add New Church</h1>
    <form method="post">
        <?php wp_nonce_field('car_add_church', 'car_church_nonce'); ?>
        <table class="form-table">
            <tr><th><label>Name</label></th><td><input name="name" type="text" class="regular-text" required></td></tr>
            <tr><th><label>Description</label></th><td><textarea name="description" class="large-text"></textarea></td></tr>
            <tr><th><label>Pastor Name</label></th><td><input name="pastor_name" type="text" class="regular-text"></td></tr>
            <tr><th><label>Pastor Email</label></th><td><input name="pastor_email" type="email" class="regular-text"></td></tr>
            <tr><th><label>Phone Number</label></th><td><input name="phone_number" type="text" class="regular-text"></td></tr>
            <tr><th><label>Website</label></th><td><input name="website" type="url" class="regular-text"></td></tr>
            <tr><th><label>Address</label></th><td><input name="address" type="text" class="regular-text"></td></tr>
        </table>
        <?php submit_button('Add Church'); ?>
    </form>
</div>
<?php } ?>
