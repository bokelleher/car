<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Enqueue ChartJS and dashboard-charts.js only when shortcode is used
function car_enqueue_church_dashboard_assets() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script(
        'car-dashboard-charts',
        plugin_dir_url(__FILE__) . '../assets/js/dashboard-charts.js',
        ['chartjs'],
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'car_enqueue_church_dashboard_assets');

// Church Dashboard Shortcode
function car_render_church_dashboard() {
    if (!is_user_logged_in()) return '<p>You must be logged in to view this report.</p>';

    $user = wp_get_current_user();
    $roles = $user->roles;
    $is_admin = in_array('church_admin', $roles);
    $is_editable = $is_admin;
    $church_id = get_user_meta($user->ID, 'assigned_church', true);
    if (!$church_id) return '<p>No church assigned to your account.</p>';

    $args = [
        'post_type' => 'attendance_report',
        'posts_per_page' => -1,
        'meta_query' => [[
            'key' => 'church_id',
            'value' => $church_id,
            'compare' => '='
        ]],
        'orderby' => 'meta_value',
        'meta_key' => 'report_date',
        'order' => 'ASC'
    ];

    $query = new WP_Query($args);
    $report_data = [];
    ob_start();
    ?>
    <table class="church-dashboard-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>In-Person</th>
                <th>Online</th>
                <th>Discipleship</th>
                <th>ACL</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($query->have_posts()): $query->the_post();
            $post_id = get_the_ID();
            $date = get_post_meta($post_id, 'report_date', true);
            $in_person = get_post_meta($post_id, 'in_person', true);
            $online = get_post_meta($post_id, 'online', true);
            $discipleship = get_post_meta($post_id, 'discipleship', true);
            $acl = get_post_meta($post_id, 'acl', true);
            $submitted_by = get_post_meta($post_id, 'submitted_by', true);
            $submitted_at = get_post_meta($post_id, 'submitted_at', true);

            $report_data[] = compact('date', 'in_person', 'online', 'discipleship', 'acl');
        ?>
            <tr data-report-id="<?php echo esc_attr($post_id); ?>">
                <td><?php echo esc_html($date); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="in_person"><?php echo esc_html($in_person); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="online"><?php echo esc_html($online); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="discipleship"><?php echo esc_html($discipleship); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="acl"><?php echo esc_html($acl); ?></td>
                <td><?php echo esc_html($submitted_by); ?></td>
                <td><?php echo esc_html($submitted_at); ?></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>

    <canvas id="attendanceChart" width="800" height="400"></canvas>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const tableData = <?php echo json_encode($report_data); ?>;
        const labels = tableData.map(row => row.date);
        const datasets = [
            {
                label: 'In-Person Attendance',
                data: tableData.map(row => parseInt(row.in_person) || 0),
                borderWidth: 2
            },
            {
                label: 'Online Attendance',
                data: tableData.map(row => parseInt(row.online) || 0),
                borderWidth: 2
            },
            {
                label: 'Discipleship',
                data: tableData.map(row => parseInt(row.discipleship) || 0),
                borderWidth: 2
            },
            {
                label: 'ACL',
                data: tableData.map(row => parseInt(row.acl) || 0),
                borderWidth: 2
            }
        ];

        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Inline editing
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
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('church_dashboard_reports', 'car_render_church_dashboard');

// AJAX handler for inline saving
function car_update_report_field_ajax() {
    if (!current_user_can('church_admin')) wp_send_json_error(['message' => 'Unauthorized']);

    $post_id = intval($_POST['report_id']);
    $field = sanitize_text_field($_POST['field']);
    $value = sanitize_text_field($_POST['value']);

    $allowed_fields = ['in_person', 'online', 'discipleship', 'acl'];
    if (!in_array($field, $allowed_fields)) wp_send_json_error(['message' => 'Invalid field']);

    update_post_meta($post_id, $field, $value);
    wp_send_json_success();
}
add_action('wp_ajax_car_update_report_field', 'car_update_report_field_ajax');
