document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("hamburgerBtn");
    const menu = document.getElementById("menuMovil");

    if (btn && menu) {
        btn.addEventListener("click", () => {
            menu.classList.toggle("activo");
        });
    }
});
