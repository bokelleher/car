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

    <style>
    .car-attendance-form {
        max-width: 600px;
        margin: 0 auto;
        padding: 2em;
        background: #f9f9f9;
        border-radius: 10px;
        border: 1px solid #ddd;
    }

    .car-attendance-form label {
        font-weight: 600;
        margin-top: 1em;
        display: block;
    }

    .car-attendance-form input[type="number"],
    .car-attendance-form input[type="date"] {
        width: 100%;
        padding: 0.75em;
        font-size: 1em;
        margin-top: 0.3em;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #fff;
        box-sizing: border-box;
        appearance: auto;
        -webkit-appearance: none;
        min-height: 2.8em;
    }

    @media (max-width: 480px) {
        .car-attendance-form input[type="date"] {
            font-size: 1em;
            padding: 0.65em;
            min-height: 3em;
        }
    }

    .car-attendance-form input[type="submit"] {
        margin-top: 1.5em;
        padding: 0.75em 1.5em;
        font-size: 1em;
        background-color: #007c91;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .car-attendance-form input[type="submit"]:hover {
        background-color: #005f6b;
    }

    .car-attendance-form em {
        display: block;
        margin-bottom: 1em;
    }

    .car-attendance-form p {
        margin-top: 1em;
    }
    </style>

    <form method="POST" class="car-attendance-form">
        <?php if (!empty($instructions)): ?>
            <p><em><?php echo $instructions; ?></em></p>
        <?php endif; ?>

        <label for="week_ending">Date</label>
        <input type="date" name="week_ending" required>

        <label for="in_person_attendance">In-Person Attendance</label>
        <input type="number" name="in_person_attendance" min="0" required>

        <label for="online_attendance">Online Attendance</label>
        <input type="number" name="online_attendance" min="0" required>

        <label for="discipleship_attendance">Discipleship Attendance</label>
        <input type="number" name="discipleship_attendance" min="0" required>

        <label for="acl_count">Accountability Care List (ACL)</label>
        <input type="number" name="acl_count" min="0" required>

        <?php
        $church_terms = wp_get_object_terms($user->ID, 'church');
        if (!empty($church_terms) && !is_wp_error($church_terms)) {
            $church_id = $church_terms[0]->term_id;
            echo '<input type="hidden" name="church_id" value="' . esc_attr($church_id) . '">';
            echo '<p><strong>Submitting for: ' . esc_html($church_terms[0]->name) . '</strong></p>';
        } else {
            echo '<p><strong>Error: No church assigned to your account.</strong></p>';
            return ob_get_clean();
        }
        ?>

        <?php wp_nonce_field('submit_attendance', 'car_attendance_nonce'); ?>
        <input type="submit" value="Submit Report">
    </form>

    <?php
    return ob_get_clean();
}
