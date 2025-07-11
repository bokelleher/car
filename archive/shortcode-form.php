<?php
// shortcode-form.php

// Register the shortcode
add_shortcode('church_attendance_form', 'car_render_attendance_form');

function car_render_attendance_form() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to submit attendance.</p>';
    }

    $user = wp_get_current_user();

    if (!in_array('church_reporter', $user->roles)) {
        return '<p>You do not have permission to submit attendance.</p>';
    }

    $instructions = esc_html(get_option('car_form_instructions', ''));
    $prevent_edits = get_option('car_report_locking') == '1';

    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_attendance_nonce']) && wp_verify_nonce($_POST['car_attendance_nonce'], 'submit_attendance')) {
        $church_id = intval($_POST['church_id']);
        $week_ending = sanitize_text_field($_POST['week_ending']);
        $in_person = intval($_POST['in_person_attendance']);
        $acl = intval($_POST['acl_count']);
        $online = intval($_POST['online_attendance']);
        $discipleship = intval($_POST['discipleship_attendance']);
        $user_id = get_current_user_id();
        $submitted_at = current_time('mysql');
        $submitted_name = $user->first_name . ' ' . $user->last_name;

        if ($prevent_edits) {
            $existing = get_posts([
                'post_type' => 'attendance_report',
                'post_status' => 'publish',
                'author' => $user_id,
                'meta_query' => [
                    [
                        'key' => 'week_ending',
                        'value' => $week_ending,
                        'compare' => '=',
                    ]
                ],
                'tax_query' => [
                    [
                        'taxonomy' => 'church',
                        'field' => 'term_id',
                        'terms' => [$church_id],
                    ],
                ],
            ]);

            if (!empty($existing)) {
                echo '<p><strong>Attendance report for that week already exists and cannot be edited.</strong></p>';
                return ob_get_clean();
            }
        }

        $post_id = wp_insert_post([
            'post_type' => 'attendance_report',
            'post_status' => 'publish',
            'post_title' => 'Attendance for ' . $week_ending,
            'post_author' => $user_id,
        ]);

        if ($post_id && !is_wp_error($post_id)) {
            wp_set_object_terms($post_id, [$church_id], 'church');
            update_post_meta($post_id, 'week_ending', $week_ending);
            update_post_meta($post_id, 'in_person_attendance', $in_person);
            update_post_meta($post_id, 'acl_count', $acl);
            update_post_meta($post_id, 'online_attendance', $online);
            update_post_meta($post_id, 'discipleship_attendance', $discipleship);
            update_post_meta($post_id, 'submitted_by_name', sanitize_text_field($submitted_name));
            update_post_meta($post_id, 'submitted_at', $submitted_at);
            echo '<p><strong>Attendance submitted successfully!</strong></p>';
        } else {
            echo '<p><strong>Failed to submit attendance.</strong></p>';
        }
    }

    ?>
    <form method="POST">
        <?php if (!empty($instructions)): ?>
            <p><em><?php echo $instructions; ?></em></p>
        <?php endif; ?>

        <label for="week_ending">Week Ending:</label><br>
        <input type="date" name="week_ending" required><br><br>

        <label for="in_person_attendance">In Person Attendance:</label><br>
        <input type="number" name="in_person_attendance" min="0" required><br><br>

        <label for="acl_count">Accountability Care List (ACL):</label><br>
        <input type="number" name="acl_count" min="0" required><br><br>

        <label for="online_attendance">Online Attendance:</label><br>
        <input type="number" name="online_attendance" min="0" required><br><br>

        <label for="discipleship_attendance">Discipleship Attendance:</label><br>
        <input type="number" name="discipleship_attendance" min="0" required><br><br>

        <?php
        $church_terms = wp_get_object_terms($user->ID, 'church');
        if (!empty($church_terms) && !is_wp_error($church_terms)) {
            $church_id = $church_terms[0]->term_id;
            echo '<input type="hidden" name="church_id" value="' . esc_attr($church_id) . '">';
            echo '<p>Submitting for church: <strong>' . esc_html($church_terms[0]->name) . '</strong></p>';
        } else {
            echo '<p><strong>Error: No church assigned to your account.</strong></p>';
            return ob_get_clean();
        }
        ?>

        <?php wp_nonce_field('submit_attendance', 'car_attendance_nonce'); ?>
        <input type="submit" value="Submit Attendance">
    </form>
    <?php

    return ob_get_clean();
}
