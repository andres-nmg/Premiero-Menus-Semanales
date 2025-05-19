document.addEventListener("DOMContentLoaded", function () {
    const toggles = document.querySelectorAll(".ms-accordion-toggle");
    const items = document.querySelectorAll(".ms-accordion-item");

    items.forEach(item => {
        const btn = item.querySelector(".ms-accordion-toggle");
        const content = item.querySelector(".ms-accordion-content");

        btn.addEventListener("click", () => {
            const isOpen = btn.getAttribute("aria-expanded") === "true";

            // Cierra todos los items
            items.forEach(i => {
                i.querySelector(".ms-accordion-toggle").setAttribute("aria-expanded", "false");
                i.querySelector(".ms-accordion-content").hidden = true;
            });

            // Abre el clicado si estaba cerrado
            if (!isOpen) {
                btn.setAttribute("aria-expanded", "true");
                content.hidden = false;
            }
        });
    });
});
