<?php
// Modal form markup injected via admin_footer
add_action('admin_footer-edit-tags.php', function () {
    if ($_GET['taxonomy'] !== 'church') return;
    ?>
    <div id="churchModal" class="church-modal-overlay" style="display:none;">
        <div class="church-modal">
            <h2>Add New Church</h2>
            <form id="churchModalForm">
                <label>Name: <input type="text" name="name" required></label>
                <label>Slug: <input type="text" name="slug"></label>
                <label>Pastor Name: <input type="text" name="pastor_name"></label>
                <label>Pastor Email: <input type="email" name="pastor_email"></label>
                <label>Phone Number: <input type="text" name="phone_number"></label>
                <label>Website: <input type="text" name="website"></label>
                <label>Address: <textarea name="address"></textarea></label>
                <button type="submit" class="button button-primary">Add Church</button>
                <button type="button" class="button close-modal">Cancel</button>
            </form>
        </div>
    </div>
    <button id="openChurchModal" class="button button-secondary" style="margin-top:15px;">Add New Church</button>
    <?php
});
