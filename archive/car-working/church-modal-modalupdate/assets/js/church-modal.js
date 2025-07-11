
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("churchModalOverlay");
    document.getElementById("openChurchModal").addEventListener("click", () => {
        modal.style.display = "block";
    });
    document.getElementById("closeChurchModal").addEventListener("click", () => {
        modal.style.display = "none";
    });
});
