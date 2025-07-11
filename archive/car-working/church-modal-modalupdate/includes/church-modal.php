
<?php
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'edit-tags.php' || $_GET['taxonomy'] !== 'church') return;
    wp_enqueue_style('church-modal-css', plugin_dir_url(__FILE__) . '../assets/css/church-modal.css');
    wp_enqueue_script('church-modal-js', plugin_dir_url(__FILE__) . '../assets/js/church-modal.js', [], false, true);
});

add_action('church_management_top', function () {
    echo '<button id="openChurchModal" class="button button-primary" style="float:right; margin-bottom: 10px;">Add New Church</button>';
});

add_action('admin_footer', function () {
    $screen = get_current_screen();
    if ($screen->id !== 'edit-church') return;
    ?>
    <div id="churchModalOverlay">
        <div id="churchModal">
            <h2>Add New Church</h2>
            <form method="post" action="<?php echo admin_url('edit-tags.php?taxonomy=church&post_type=attendance_report'); ?>">
                <label for="tag-name">Name</label>
                <input type="text" name="name" id="tag-name" required>
                <label for="slug">Slug</label>
                <input type="text" name="slug" id="slug">
                <label for="description">Description</label>
                <textarea name="description" id="description"></textarea>
                <label for="pastor_name">Pastor Name</label>
                <input type="text" name="pastor_name" id="pastor_name">
                <label for="city">City</label>
                <input type="text" name="city" id="city">
                <label for="website">Website</label>
                <input type="url" name="website" id="website">
                <button type="submit" class="button button-primary">Add Church</button>
                <button type="button" id="closeChurchModal" class="button">Cancel</button>
            </form>
        </div>
    </div>
    <?php
});
