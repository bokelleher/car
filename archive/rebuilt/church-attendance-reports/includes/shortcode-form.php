<?php
// shortcode-form.php

// Register the shortcode
add_shortcode('church_attendance_form', 'car_render_attendance_form');

function car_render_attendance_form() {
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to submit attendance.</p>';
    }

    $user = wp_get_current_user();

    if (!array_intersect(['church_reporter', 'church_admin'], $user->roles)) {
    return '<p>You do not have permission to submit attendance.</p>';
    }


    $instructions = esc_html(get_option('car_form_instructions', ''));
    $prevent_edits = get_option('car_report_locking') == '1';

    ob_start();

    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_submit'])) {
    global $wpdb;

    $church_id = intval(get_user_meta(get_current_user_id(), 'assigned_church', true));
    $report_date = sanitize_text_field($_POST['week_ending']);
    $in_person = intval($_POST['in_person'] ?? 0);
    $online = intval($_POST['online'] ?? 0);
    $discipleship = intval($_POST['discipleship'] ?? 0);
    $acl = intval($_POST['acl'] ?? 0);
    $submitted_by = get_current_user_id();

    // Prevent duplicate submissions
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}car_attendance_reports WHERE church_id = %d AND report_date = %s",
        $church_id, $report_date
    ));

    if ($existing) {
        echo '<div class="notice notice-error"><p>A report for this week already exists.</p></div>';
    } else {
        $result = $wpdb->insert("{$wpdb->prefix}car_attendance_reports", [
            'church_id' => $church_id,
            'report_date' => $report_date,
            'in_person' => $in_person,
            'online' => $online,
            'discipleship' => $discipleship,
            'acl' => $acl,
            'submitted_by' => $submitted_by,
            'submitted_at' => current_time('mysql', 1),
        ]);

        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Report submitted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>There was an error saving the report. Please try again.</p></div>';
        }
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
