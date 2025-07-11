
document.addEventListener("DOMContentLoaded", function () {
    const openBtn = document.getElementById("open-church-modal");
    const modal = document.getElementById("church-modal");
    const closeBtn = document.querySelector(".church-modal-close");

    if (openBtn && modal && closeBtn) {
        openBtn.addEventListener("click", () => modal.style.display = "block");
        closeBtn.addEventListener("click", () => modal.style.display = "none");

        window.addEventListener("click", function (event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    }
});
