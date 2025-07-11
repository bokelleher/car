
<?php
add_action('admin_footer-edit-tags.php', function () {
    if ($_GET['taxonomy'] !== 'church') return;
    ?>
    <div id="church-modal" class="church-modal">
        <div class="church-modal-content">
            <span class="church-modal-close">&times;</span>
            <h2>Add New Church</h2>
            <form method="POST" action="<?php echo admin_url('term.php'); ?>">
                <input type="hidden" name="taxonomy" value="church" />
                <input type="hidden" name="post_type" value="attendance_report" />
                <table class="form-table">
                    <tr><th><label for="tag-name">Church Name</label></th><td><input name="tag-name" id="tag-name" type="text" class="regular-text" required></td></tr>
                    <tr><th><label for="pastor_name">Pastor Name</label></th><td><input name="pastor_name" type="text" class="regular-text"></td></tr>
                    <tr><th><label for="pastor_email">Pastor Email</label></th><td><input name="pastor_email" type="email" class="regular-text"></td></tr>
                    <tr><th><label for="phone_number">Phone Number</label></th><td><input name="phone_number" type="text" class="regular-text"></td></tr>
                    <tr><th><label for="website">Website</label></th><td><input name="website" type="text" class="regular-text"></td></tr>
                    <tr><th><label for="address">Address</label></th><td><textarea name="address" class="large-text"></textarea></td></tr>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="Add New Church"></p>
            </form>
        </div>
    </div>
    <script src="<?php echo plugin_dir_url(__FILE__) . '../assets/js/church-modal.js'; ?>"></script>
    <link rel="stylesheet" href="<?php echo plugin_dir_url(__FILE__) . '../assets/css/church-modal.css'; ?>">
    <button id="open-church-modal" class="button button-secondary" type="button">Add New Church</button>
    <?php
});
?>
