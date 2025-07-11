jQuery(document).ready(function($){
    $('<button class="button button-primary">Add New Church</button>')
        .prependTo('.wrap h1')
        .on('click', function() {
            $('#car-church-modal').show();
        });

    $('#car-add-church-form').on('submit', function(e) {
        e.preventDefault();
        alert('This form would submit via AJAX or be extended further.');
        $('#car-church-modal').hide();
    });
});
