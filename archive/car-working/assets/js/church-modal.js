document.addEventListener('DOMContentLoaded', function () {
    const openBtn = document.getElementById('openChurchModal');
    const modal = document.getElementById('churchModal');
    const closeBtn = document.querySelector('.close-modal');

    openBtn.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    document.getElementById('churchModalForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams([...formData, ['action', 'add_church']])
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Church added successfully!');
                location.reload();
            } else {
                alert('Error adding church.');
            }
        });
    });
});
