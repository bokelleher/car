<?php
// Hook to enqueue the CSS in admin
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'church') {
        wp_enqueue_style('car-church-admin', plugin_dir_url(__FILE__) . '../assets/css/church-admin.css');
    }
});

// Hook to insert the button above the taxonomy list table
add_action('church_taxonomy_pre_list', function() {
    $url = admin_url('admin.php?page=car_add_church');
    echo '<a href="' . esc_url($url) . '" class="button car-add-church-btn">Add New Church</a>';
});

// Use output buffering to inject button below the page title
add_action('admin_notices', function() {
    global $pagenow;
    if ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'church') {
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const h1 = document.querySelector("h1.wp-heading-inline");
            if (h1) {
                const btn = document.querySelector(".car-add-church-btn");
                if (btn) h1.parentNode.insertBefore(btn, h1.nextSibling);
            }
        });
        </script>';
    }
});
?>
