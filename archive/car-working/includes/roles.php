<?php
// Register custom roles
function car_add_custom_roles() {
    add_role('district_admin', 'District Admin', [
        'read' => true,
        'edit_posts' => false,
        'car_manage_churches' => true,
    ]);
    add_role('church_admin', 'Church Admin', [
        'read' => true,
        'edit_posts' => false,
        'car_manage_users' => true,
    ]);
    add_role('church_reporter', 'Church Reporter', [
        'read' => true,
        'edit_attendance_reports' => true,
    ]);
    add_role('church_viewer', 'Church Viewer', [
        'read' => true,
    ]);
}
register_activation_hook(__FILE__, 'car_add_custom_roles');
