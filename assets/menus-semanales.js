document.addEventListener("click", function (event) {
  const toggle = event.target.closest(".pms-accordion__toggle");
  if (!toggle) return;

  const accordion = toggle.closest(".pms-accordion");
  const contentId = toggle.getAttribute("aria-controls");
  const content = contentId ? document.getElementById(contentId) : null;
  if (!accordion || !content) return;

  const shouldOpen = toggle.getAttribute("aria-expanded") !== "true";
  accordion.querySelectorAll(".pms-accordion__toggle").forEach((button) => {
    const targetId = button.getAttribute("aria-controls");
    const target = targetId ? document.getElementById(targetId) : null;
    button.setAttribute("aria-expanded", "false");
    if (target) target.hidden = true;
  });

  if (shouldOpen) {
    toggle.setAttribute("aria-expanded", "true");
    content.hidden = false;
  }
});
