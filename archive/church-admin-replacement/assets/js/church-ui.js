jQuery(document).ready(function($) {
    $('#car-add-church-btn').on('click', function() {
        $('#car-church-modal').show();
    });

    $('#car-church-form').on('submit', function(e) {
        e.preventDefault();
        $.post(ajaxurl, $(this).serialize() + '&action=car_save_church', function(response) {
            if (response.success) {
                location.reload();
            }
        });
    });
});
