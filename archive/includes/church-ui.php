<?php
// Hide default taxonomy form
function car_hide_default_church_form_css() {
    $screen = get_current_screen();
    if ( $screen && $screen->id === 'edit-church' ) {
        echo '<style>#addtag { display: none; }</style>';
    }
}
add_action('admin_head', 'car_hide_default_church_form_css');

// Add "Add New Church" button
function car_add_church_button() {
    $screen = get_current_screen();
    if ( $screen && $screen->id === 'edit-church' ) {
        $add_url = admin_url('admin.php?page=car_add_church');
        echo '<div style="margin: 15px 0;">
                <a href="' . esc_url($add_url) . '" class="page-title-action">Add New Church</a>
              </div>';
    }
}
add_action('admin_notices', 'car_add_church_button');
