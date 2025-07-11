<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Enqueue Chart.js
function car_enqueue_dashboard_scripts() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'car_enqueue_dashboard_scripts');

function car_render_church_dashboard() {
    if (!is_user_logged_in()) return '<p>You must be logged in to view this report.</p>';

    $user = wp_get_current_user();
    $roles = $user->roles;
    $is_admin = in_array('church_admin', $roles);
    $is_editable = $is_admin;
    $church_id = get_user_meta($user->ID, 'assigned_church', true);
    if (!$church_id) return '<p>No church assigned to your account.</p>';

    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    $meta_query = [[
        'key' => 'church_id',
        'value' => $church_id,
        'compare' => '='
    ]];

    if ($start_date && $end_date) {
        $meta_query[] = [
            'key' => 'report_date',
            'value' => [$start_date, $end_date],
            'compare' => 'BETWEEN',
            'type' => 'DATE'
        ];
    }

    $args = [
        'post_type' => 'attendance_report',
        'posts_per_page' => -1,
        'meta_query' => $meta_query,
        'orderby' => 'meta_value',
        'meta_key' => 'report_date',
        'order' => 'ASC'
    ];

    $query = new WP_Query($args);
    ob_start();
    ?>
    <form method="get">
        <label>Start Date: <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>"></label>
        <label>End Date: <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>"></label>
        <input type="submit" value="Filter">
    </form>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="car_export_csv">
        <input type="hidden" name="church_id" value="<?php echo esc_attr($church_id); ?>">
        <input type="submit" value="Export to CSV">
    </form>

    <table class="church-dashboard-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>In-Person</th>
                <th>Online</th>
                <th>Discipleship</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $labels = $in_person = $online = $discipleship = [];

        while ($query->have_posts()): $query->the_post();
            $post_id = get_the_ID();
            $submitted_by = get_post_meta($post_id, 'submitted_by', true);
            $submitted_at = get_post_meta($post_id, 'submitted_at', true);
            $report_date = get_post_meta($post_id, 'report_date', true);
            $labels[] = $report_date;
            $in_person[] = get_post_meta($post_id, 'in_person', true);
            $online[] = get_post_meta($post_id, 'online', true);
            $discipleship[] = get_post_meta($post_id, 'discipleship', true);
            ?>
            <tr data-report-id="<?php echo esc_attr($post_id); ?>">
                <td><?php echo esc_html($report_date); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="in_person"><?php echo esc_html($in_person[count($in_person)-1]); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="online"><?php echo esc_html($online[count($online)-1]); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="discipleship"><?php echo esc_html($discipleship[count($discipleship)-1]); ?></td>
                <td><?php echo esc_html($submitted_by); ?></td>
                <td><?php echo esc_html($submitted_at); ?></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>

    <?php if ($is_editable): ?>
    <script>
    document.querySelectorAll('.church-dashboard-table td.editable').forEach(cell => {
        cell.addEventListener('blur', function () {
            const row = cell.closest('tr');
            const reportId = row.dataset.reportId;
            const field = cell.dataset.field;
            const value = cell.innerText;

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'car_update_report_field',
                    report_id: reportId,
                    field: field,
                    value: value
                })
            }).then(res => res.json()).then(data => {
                if (!data.success) {
                    alert('Error saving: ' + data.message);
                }
            });
        });
    });
    </script>
    <?php endif; ?>

    <h3>Attendance Graphs</h3>
    <button onclick="toggleChartType()">Toggle Chart Type</button>
    <canvas id="attendanceChart" height="100"></canvas>
    <script>
        const chartData = {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'In-Person',
                    data: <?php echo json_encode($in_person); ?>,
                    borderWidth: 2,
                    fill: false
                },
                {
                    label: 'Online',
                    data: <?php echo json_encode($online); ?>,
                    borderWidth: 2,
                    fill: false
                },
                {
                    label: 'Discipleship',
                    data: <?php echo json_encode($discipleship); ?>,
                    borderWidth: 2,
                    fill: false
                }
            ]
        };
        let chartType = 'line';
        let attendanceChart = new Chart(document.getElementById('attendanceChart'), {
            type: chartType,
            data: chartData,
            options: { responsive: true, maintainAspectRatio: false }
        });

        function toggleChartType() {
            chartType = chartType === 'line' ? 'bar' : 'line';
            attendanceChart.destroy();
            attendanceChart = new Chart(document.getElementById('attendanceChart'), {
                type: chartType,
                data: chartData,
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    </script>

    <style>
        .church-dashboard-table td[contenteditable="true"] {
            background: #f9f9f9;
            cursor: text;
        }
        #attendanceChart {
            max-height: 400px;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('church_dashboard_reports', 'car_render_church_dashboard');

function car_update_report_field_ajax() {
    if (!current_user_can('church_admin')) wp_send_json_error(['message' => 'Unauthorized']);

    $post_id = intval($_POST['report_id']);
    $field = sanitize_text_field($_POST['field']);
    $value = sanitize_text_field($_POST['value']);

    $allowed_fields = ['in_person', 'online', 'discipleship'];
    if (!in_array($field, $allowed_fields)) wp_send_json_error(['message' => 'Invalid field']);

    update_post_meta($post_id, $field, $value);
    wp_send_json_success();
}
add_action('wp_ajax_car_update_report_field', 'car_update_report_field_ajax');

function car_export_csv_handler() {
    if (!is_user_logged_in()) wp_die('Not authorized');
    $user = wp_get_current_user();
    $church_id = sanitize_text_field($_POST['church_id']);
    if (!$church_id || get_user_meta($user->ID, 'assigned_church', true) != $church_id) {
        wp_die('Invalid church ID');
    }

    $args = [
        'post_type' => 'attendance_report',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => 'church_id',
                'value' => $church_id,
                'compare' => '='
            ]
        ]
    ];
    $query = new WP_Query($args);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=church_attendance.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'In-Person', 'Online', 'Discipleship', 'Submitted By', 'Submitted At']);

    while ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();
        fputcsv($output, [
            get_post_meta($post_id, 'report_date', true),
            get_post_meta($post_id, 'in_person', true),
            get_post_meta($post_id, 'online', true),
            get_post_meta($post_id, 'discipleship', true),
            get_post_meta($post_id, 'submitted_by', true),
            get_post_meta($post_id, 'submitted_at', true),
        ]);
    }

    fclose($output);
    wp_die();
}
add_action('admin_post_car_export_csv', 'car_export_csv_handler');
