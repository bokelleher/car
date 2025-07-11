<?php
global $wpdb;
$table = $wpdb->prefix . 'car_churches';
$churches = $wpdb->get_results("SELECT * FROM $table");
?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Name</th>
            <th>Slug</th>
            <th>Pastor</th>
            <th>City</th>
            <th>Website</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($churches as $church): ?>
        <tr>
            <td><?= esc_html($church->name) ?></td>
            <td><?= esc_html($church->slug) ?></td>
            <td><?= esc_html($church->pastor) ?></td>
            <td><?= esc_html($church->city) ?></td>
            <td><a href="<?= esc_url($church->website) ?>" target="_blank"><?= esc_html($church->website) ?></a></td>
            <td><a href="#" class="car-edit-church" data-id="<?= esc_attr($church->id) ?>">Edit</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
