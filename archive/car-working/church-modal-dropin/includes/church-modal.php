<?php
// ✅ Register modal and enqueue assets
add_action('admin_footer-edit-tags.php', function () {
    $screen = get_current_screen();
    if ($screen->taxonomy !== 'church') return;

    include plugin_dir_path(__FILE__) . 'church-modal.php-html';
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'edit-tags.php') return;
    if (!isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'church') return;

    wp_enqueue_style('car-church-modal', plugin_dir_url(__FILE__) . '../assets/css/church-modal.css');
    wp_enqueue_script('car-church-modal', plugin_dir_url(__FILE__) . '../assets/js/church-modal.js', [], false, true);
    wp_localize_script('car-church-modal', 'carChurchModal', [
        'nonce' => wp_create_nonce('car_add_church_nonce'),
    ]);
});

// ✅ Handle form submission via AJAX
add_action('wp_ajax_car_add_church_term', function () {
    check_ajax_referer('car_add_church_nonce');

    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = sanitize_title($_POST['slug'] ?? '');
    $pastor = sanitize_text_field($_POST['pastor_name'] ?? '');
    $website = esc_url_raw($_POST['website'] ?? '');
    $address = sanitize_textarea_field($_POST['address'] ?? '');

    if (!$name) wp_send_json_error('Church name is required.');

    $term = wp_insert_term($name, 'church', ['slug' => $slug]);
    if (is_wp_error($term)) wp_send_json_error($term->get_error_message());

    $term_id = $term['term_id'];

    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'car_churches', [
        'term_id' => $term_id,
        'pastor_name' => $pastor,
        'website' => $website,
        'address' => $address,
    ]);

    wp_send_json_success(true);
});
