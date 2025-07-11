<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

// Enqueue Chart.js
function car_enqueue_church_dashboard_assets() {
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
}
add_action('wp_enqueue_scripts', 'car_enqueue_church_dashboard_assets');

// Shortcode
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
        'orderby' => 'meta_value',
        'meta_key' => 'report_date',
        'order' => 'ASC',
        'meta_query' => [
            [
                'key' => 'church_id',
                'value' => $church_id,
                'compare' => '=',
            ],
        ],
    ];
    $query = new WP_Query($args);

    $labels = [];
    $data_in_person = [];
    $data_online = [];
    $data_discipleship = [];
    $data_acl = [];

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
            $report_date = get_post_meta($post_id, 'report_date', true);
            $in_person = get_post_meta($post_id, 'in_person', true);
            $online = get_post_meta($post_id, 'online', true);
            $discipleship = get_post_meta($post_id, 'discipleship', true);
            $acl = get_post_meta($post_id, 'acl', true);
            $submitted_by = get_post_meta($post_id, 'submitted_by', true);
            $submitted_at = get_post_meta($post_id, 'submitted_at', true);

            $labels[] = $report_date;
            $data_in_person[] = (int)$in_person;
            $data_online[] = (int)$online;
            $data_discipleship[] = (int)$discipleship;
            $data_acl[] = (int)$acl;
        ?>
            <tr data-report-id="<?php echo esc_attr($post_id); ?>">
                <td><?php echo esc_html($report_date); ?></td>
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
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [
                    {
                        label: 'In Person',
                        data: <?php echo json_encode($data_in_person); ?>,
                        borderColor: 'blue',
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: 'Online',
                        data: <?php echo json_encode($data_online); ?>,
                        borderColor: 'green',
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: 'Discipleship',
                        data: <?php echo json_encode($data_discipleship); ?>,
                        borderColor: 'orange',
                        borderWidth: 2,
                        fill: false
                    },
                    {
                        label: 'ACL',
                        data: <?php echo json_encode($data_acl); ?>,
                        borderColor: 'red',
                        borderWidth: 2,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    });
    </script>
    
    // Chart.js injection
    <canvas id="attendanceChart" width="400" height="200"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById("attendanceChart").getContext("2d");

        const labels = <?php echo json_encode($labels); ?>;
        const inPersonData = <?php echo json_encode($in_person_data); ?>;
        const onlineData = <?php echo json_encode($online_data); ?>;
        const discipleshipData = <?php echo json_encode($discipleship_data); ?>;
        const aclData = <?php echo json_encode($acl_data); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'In Person',
                        data: inPersonData,
                        borderColor: 'blue',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'Online',
                        data: onlineData,
                        borderColor: 'green',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'Discipleship',
                        data: discipleshipData,
                        borderColor: 'purple',
                        fill: false,
                        tension: 0.1
                    },
                    {
                        label: 'ACL',
                        data: aclData,
                        borderColor: 'orange',
                        fill: false,
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10
                        }
                    }
                }
            }
        });
    });
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('church_dashboard_reports', 'car_render_church_dashboard');
