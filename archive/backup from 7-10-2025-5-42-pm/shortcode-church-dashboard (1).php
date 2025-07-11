<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

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
    ob_start();
    ?>
    <canvas id="attendanceChart" width="400" height="150" style="max-width:100%;"></canvas>

    <table class="church-dashboard-table" style="width:100%; margin-top:20px;">
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
        <?php
        $labels = [];
        $in_person = [];
        $online = [];
        $discipleship = [];
        $acl = [];

        while ($query->have_posts()): $query->the_post();
            $post_id = get_the_ID();
            $date = get_post_meta($post_id, 'report_date', true);
            $in = intval(get_post_meta($post_id, 'in_person', true));
            $on = intval(get_post_meta($post_id, 'online', true));
            $dis = intval(get_post_meta($post_id, 'discipleship', true));
            $ac = intval(get_post_meta($post_id, 'acl', true));
            $labels[] = $date;
            $in_person[] = $in;
            $online[] = $on;
            $discipleship[] = $dis;
            $acl[] = $ac;
        ?>
            <tr data-report-id="<?php echo esc_attr($post_id); ?>">
                <td><?php echo esc_html($date); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="in_person"><?php echo $in; ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="online"><?php echo $on; ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="discipleship"><?php echo $dis; ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="acl"><?php echo $ac; ?></td>
                <td><?php echo esc_html(get_post_meta($post_id, 'submitted_by', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($post_id, 'submitted_at', true)); ?></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>

    <script>
    const labels = <?php echo json_encode($labels); ?>;
    const data = {
        labels: labels,
        datasets: [
            {
                label: 'In Person',
                data: <?php echo json_encode($in_person); ?>,
                borderWidth: 2,
                borderColor: 'blue',
                fill: false
            },
            {
                label: 'Online',
                data: <?php echo json_encode($online); ?>,
                borderWidth: 2,
                borderColor: 'green',
                fill: false
            },
            {
                label: 'Discipleship',
                data: <?php echo json_encode($discipleship); ?>,
                borderWidth: 2,
                borderColor: 'orange',
                fill: false
            },
            {
                label: 'ACL',
                data: <?php echo json_encode($acl); ?>,
                borderWidth: 2,
                borderColor: 'red',
                fill: false
            }
        ]
    };

    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: 100
                }
            }
        }
    };

    new Chart(document.getElementById('attendanceChart'), config);

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
    <?php
    return ob_get_clean();
}
add_shortcode('church_dashboard_reports', 'car_render_church_dashboard');

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