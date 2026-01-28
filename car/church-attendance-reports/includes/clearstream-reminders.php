<?php
/**
 * ClearStream reminder integration
 *
 * This file schedules a weekly cron event that sends SMS reminders via the
 * ClearStream API. Each week, every church reporter receives a gentle
 * reminder to submit their attendance report. If a church does not have
 * an assigned reporter, the church admin will be notified instead. Should
 * a church be more than 30 days overdue on reporting, both the reporter
 * and admin are notified.
 *
 * The ClearStream API key is defined in the main plugin file as
 * CAR_CLEARSTREAM_API_KEY. You can override it via WordPress options by
 * setting the `car_clearstream_api_key` option.
 */

/**
 * Ensure a 'weekly' cron schedule exists.
 *
 * Some WordPress installs do not register a weekly interval by default.
 * We add it defensively so scheduling works everywhere.
 */
function car_add_weekly_cron_schedule($schedules) {
    if (!isset($schedules['weekly'])) {
        $schedules['weekly'] = [
            'interval' => 7 * DAY_IN_SECONDS,
            'display'  => __('Once Weekly', 'church-attendance-reports'),
        ];
    }
    return $schedules;
}
add_filter('cron_schedules', 'car_add_weekly_cron_schedule');

// Schedule the weekly event on init.
function car_schedule_clearstream_reminder_event() {
    if (!wp_next_scheduled('car_clearstream_weekly_reminder')) {
        wp_schedule_event(time(), 'weekly', 'car_clearstream_weekly_reminder');
    }
}
add_action('init', 'car_schedule_clearstream_reminder_event');

// Hook our reminder sender to the scheduled event.
add_action('car_clearstream_weekly_reminder', 'car_send_clearstream_reminders');

/**
 * Admin utility page (under Attendance Reports) to run reminder tests on-demand.
 */
function car_clearstream_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=attendance_report',
        __('Attendance Reminders', 'church-attendance-reports'),
        __('Reminders', 'church-attendance-reports'),
        'manage_options',
        'car-attendance-reminders',
        'car_render_clearstream_admin_page'
    );
}
add_action('admin_menu', 'car_clearstream_admin_menu');

function car_render_clearstream_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $notice = '';
    $notice_class = 'notice notice-success';

    if (!empty($_POST['car_clearstream_action'])) {
        check_admin_referer('car_clearstream_reminders');

        $action = sanitize_text_field(wp_unslash($_POST['car_clearstream_action']));
        $test_phone = '';
        if (!empty($_POST['car_test_phone'])) {
            $test_phone = sanitize_text_field(wp_unslash($_POST['car_test_phone']));
        }

        if ($action === 'test_single') {
            if (empty($test_phone)) {
                $current = wp_get_current_user();
                $test_phone = get_user_meta($current->ID, 'mobile_phone', true);
            }
            if (empty($test_phone)) {
                $notice_class = 'notice notice-error';
                $notice = __('No test phone number provided (and your user profile has no Mobile Phone set).', 'church-attendance-reports');
            } else {
                $result = car_clearstream_send_sms($test_phone, __('Test message: ClearStream SMS is configured correctly for Church Attendance Reports.', 'church-attendance-reports'));
                if ($result['ok']) {
                    $notice = sprintf(__('Test message sent to %s', 'church-attendance-reports'), esc_html($test_phone));
                } else {
                    $notice_class = 'notice notice-error';
                    $notice = sprintf(__('Failed to send test message to %s: %s', 'church-attendance-reports'), esc_html($test_phone), esc_html($result['error']));
                }
            }
        }

        if ($action === 'run_now') {
            $report = car_send_clearstream_reminders([
                'return_report' => true,
            ]);
            $notice = sprintf(
                __('Ran reminder job. Churches processed: %d. Messages attempted: %d. Errors: %d.', 'church-attendance-reports'),
                (int)($report['churches'] ?? 0),
                (int)($report['attempted'] ?? 0),
                (int)($report['errors'] ?? 0)
            );
            if (!empty($report['errors'])) {
                $notice_class = 'notice notice-warning';
            }
        }
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Attendance Reminders', 'church-attendance-reports') . '</h1>';
    echo '<p>' . esc_html__('Use these tools to test ClearStream SMS reminders and to run the reminder job on-demand.', 'church-attendance-reports') . '</p>';

    if (!empty($notice)) {
        echo '<div class="' . esc_attr($notice_class) . '"><p>' . esc_html($notice) . '</p></div>';
    }

    echo '<h2>' . esc_html__('Send a single test SMS', 'church-attendance-reports') . '</h2>';
    echo '<form method="post">';
    wp_nonce_field('car_clearstream_reminders');
    echo '<input type="hidden" name="car_clearstream_action" value="test_single" />';
    echo '<p><label for="car_test_phone">' . esc_html__('Test phone number (optional). If blank, uses your profile Mobile Phone.', 'church-attendance-reports') . '</label></p>';
    echo '<p><input type="text" class="regular-text" id="car_test_phone" name="car_test_phone" placeholder="+1XXXXXXXXXX" /></p>';
    submit_button(__('Send Test SMS', 'church-attendance-reports'));
    echo '</form>';

    echo '<hr />';
    echo '<h2>' . esc_html__('Run reminder job now', 'church-attendance-reports') . '</h2>';
    echo '<form method="post">';
    wp_nonce_field('car_clearstream_reminders');
    echo '<input type="hidden" name="car_clearstream_action" value="run_now" />';
    submit_button(__('Run Now', 'church-attendance-reports'), 'secondary');
    echo '</form>';

    echo '</div>';
}

/**
 * Low-level SMS sender with basic error handling.
 */
function car_clearstream_send_sms($phone, $body) {
    $api_key = defined('CAR_CLEARSTREAM_API_KEY') ? CAR_CLEARSTREAM_API_KEY : '';
    $opt_key = get_option('car_clearstream_api_key', '');
    if (!empty($opt_key)) {
        $api_key = $opt_key;
    }
    if (empty($api_key)) {
        return ['ok' => false, 'error' => 'Missing API key'];
    }

    $payload = [
        'to'                 => $phone,
        'text_body'          => $body,
        'use_default_header' => true,
    ];

    $args = [
        'headers' => [
            'X-Api-Key' => $api_key,
        ],
        'body'    => $payload,
        'timeout' => 15,
    ];

    $resp = wp_remote_post('https://api.getclearstream.com/v1/texts', $args);
    if (is_wp_error($resp)) {
        return ['ok' => false, 'error' => $resp->get_error_message()];
    }
    $code = (int) wp_remote_retrieve_response_code($resp);
    if ($code < 200 || $code >= 300) {
        $msg = wp_remote_retrieve_body($resp);
        $msg = is_string($msg) ? trim($msg) : '';
        return ['ok' => false, 'error' => 'HTTP ' . $code . ($msg ? (': ' . $msg) : '')];
    }
    return ['ok' => true];
}

/**
 * Send reminder texts via ClearStream to church reporters and admins.
 *
 * Queries all church taxonomy terms and determines the latest attendance
 * report date for each. If no report exists or the most recent report is
 * older than 30 days, it marks the church as overdue. A weekly message is
 * sent to each church reporter. If there is no reporter, the church admin
 * receives the reminder. If the church is overdue, both the reporter and
 * admin receive the message.
 */
function car_send_clearstream_reminders($opts = []) {
    $opts = is_array($opts) ? $opts : [];
    $return_report = !empty($opts['return_report']);

    $report = [
        'churches'   => 0,
        'attempted'  => 0,
        'errors'     => 0,
        'error_list' => [],
    ];

    // Determine the API key. Priority: constant, then option.
    $api_key = defined('CAR_CLEARSTREAM_API_KEY') ? CAR_CLEARSTREAM_API_KEY : '';
    $opt_key = get_option('car_clearstream_api_key', '');
    if (!empty($opt_key)) {
        $api_key = $opt_key;
    }
    if (empty($api_key)) {
        return $return_report ? $report : null;
    }

    // Direct link to the reporting page (shortcode page).
    // Uses site_url() so it remains correct if the domain changes.
    $report_url = trailingslashit(site_url('/report-attendance/'));

    // Fetch all churches (taxonomy terms). We include empty terms so that
    // churches without reports are also processed.
    $churches = get_terms([
        'taxonomy'   => 'church',
        'hide_empty' => false,
    ]);
    if (is_wp_error($churches) || empty($churches)) {
        return $return_report ? $report : null;
    }

    $threshold_ts = strtotime('-30 days');

    foreach ($churches as $church) {
        $report['churches']++;
        $church_id = $church->term_id;

        // Determine the last attendance date for this church.
        $report_query = new WP_Query([
            'post_type'      => 'attendance_report',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'attendance_date',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'   => 'church',
                    'value' => $church_id,
                ],
            ],
        ]);
        $last_date = null;
        if ($report_query->have_posts()) {
            $post_id  = $report_query->posts[0]->ID;
            $last_date = get_post_meta($post_id, 'attendance_date', true);
        }
        wp_reset_postdata();

        // Determine whether the church is overdue (no report or older than threshold).
        $overdue = true;
        if ($last_date) {
            $last_ts = strtotime($last_date);
            if ($last_ts !== false && $last_ts >= $threshold_ts) {
                $overdue = false;
            }
        }

        // Retrieve reporter(s) for this church.
        $reporters = get_users([
            'role'       => 'church_reporter',
            'meta_key'   => 'assigned_church',
            'meta_value' => $church_id,
            'fields'     => ['ID', 'display_name', 'user_login'],
        ]);

        // Retrieve admin(s) for this church.
        $admins = get_users([
            'role'       => 'church_admin',
            'meta_key'   => 'assigned_church',
            'meta_value' => $church_id,
            'fields'     => ['ID', 'display_name', 'user_login'],
        ]);

        // Build recipient list. Use phone numbers as keys to avoid duplicates.
        $recipients = [];

        if (!empty($reporters)) {
            foreach ($reporters as $user) {
                $phone = get_user_meta($user->ID, 'mobile_phone', true);
                if (!empty($phone)) {
                    $recipients[$phone] = [
                        'user' => $user,
                        'type' => 'reporter',
                    ];
                }
            }
        } else {
            // If there are no reporters, fall back to admins.
            foreach ($admins as $user) {
                $phone = get_user_meta($user->ID, 'mobile_phone', true);
                if (!empty($phone)) {
                    $recipients[$phone] = [
                        'user' => $user,
                        'type' => 'admin',
                    ];
                }
            }
        }

        // If overdue, also notify all admins, even if reporters exist.
        if ($overdue) {
            foreach ($admins as $user) {
                $phone = get_user_meta($user->ID, 'mobile_phone', true);
                if (!empty($phone) && !isset($recipients[$phone])) {
                    $recipients[$phone] = [
                        'user' => $user,
                        'type' => 'admin',
                    ];
                }
            }
        }

        // Skip if no recipients.
        if (empty($recipients)) {
            continue;
        }

        // Prepare and send message to each recipient.
        foreach ($recipients as $phone => $info) {
            $body = $overdue
                ? __('Reminder: Your church has not submitted an attendance report in over 30 days. Please update your attendance at your earliest convenience.', 'church-attendance-reports')
                : __('Weekly reminder: Please remember to submit your church attendance report.', 'church-attendance-reports');

            // Include a direct link to the reporting page.
            $body .= "\n" . sprintf(
                __('Report here: %s', 'church-attendance-reports'),
                $report_url
            );

            $payload = [
                'to'                 => $phone,
                'text_body'          => $body,
                'use_default_header' => true,
            ];

            $report['attempted']++;
            $result = car_clearstream_send_sms($phone, $body);
            if (!$result['ok']) {
                $report['errors']++;
                $report['error_list'][] = [
                    'church' => $church->name,
                    'to'     => $phone,
                    'error'  => $result['error'],
                ];
            }
        }
    }

    return $return_report ? $report : null;
}