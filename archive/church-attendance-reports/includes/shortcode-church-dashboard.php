<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;

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
            $post_id = get_the_ID(); ?>
            <tr data-report-id="<?php echo esc_attr($post_id); ?>">
                <td><?php echo esc_html(get_post_meta($post_id, 'report_date', true)); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="in_person"><?php echo esc_html(get_post_meta($post_id, 'in_person', true)); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="online"><?php echo esc_html(get_post_meta($post_id, 'online', true)); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="discipleship"><?php echo esc_html(get_post_meta($post_id, 'discipleship', true)); ?></td>
                <td contenteditable="<?php echo $is_editable ? 'true' : 'false'; ?>" class="editable" data-field="acl"><?php echo esc_html(get_post_meta($post_id, 'acl', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($post_id, 'submitted_by', true)); ?></td>
                <td><?php echo esc_html(get_post_meta($post_id, 'submitted_at', true)); ?></td>
            </tr>
        <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>
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
