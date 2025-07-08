<?php
// Meta assignment UI and helper
function car_assign_church_to_user($user_id, $church_id) {
    update_user_meta($user_id, 'assigned_church', intval($church_id));
}

function car_get_user_church($user_id) {
    return get_user_meta($user_id, 'assigned_church', true);
}
