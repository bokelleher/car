document.addEventListener('DOMContentLoaded', function () {
    const modalBtn = document.getElementById('car-open-modal');
    const modal = document.getElementById('car-church-modal');
    const closeBtn = document.querySelector('.car-close-modal');
    const form = document.getElementById('car-church-modal-form');
    const spinner = document.getElementById('car-modal-spinner');
    const submitBtn = document.getElementById('car-modal-submit');

    modalBtn?.addEventListener('click', () => modal.style.display = 'block');
    closeBtn?.addEventListener('click', () => modal.style.display = 'none');

    window.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    form?.addEventListener('submit', function (e) {
        e.preventDefault();
        spinner.style.display = 'inline-block';
        submitBtn.disabled = true;

        const formData = new FormData(form);
        formData.append('action', 'car_add_church_term');
        formData.append('_wpnonce', carChurchModal.nonce);

        fetch(ajaxurl, {
            method: 'POST',
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            spinner.style.display = 'none';
            submitBtn.disabled = false;
            if (data.success) {
                alert('Church added successfully.');
                window.location.reload();
            } else {
                alert(data.data || 'Something went wrong.');
            }
        });
    });
});
