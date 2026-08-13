(function () {
  "use strict";

  const root = document.querySelector(".sesamo-wrap");
  if (!root) return;

  const checkboxes = Array.from(root.querySelectorAll(".sesamo-preset input[type='checkbox']"));
  const status = root.querySelector("[data-sesamo-status]");
  const statusTitle = root.querySelector("[data-sesamo-status-title]");
  const statusCount = root.querySelector("[data-sesamo-status-count]");
  const offNote = root.querySelector("[data-sesamo-off-note]");

  function update() {
    const count = checkboxes.filter((checkbox) => checkbox.checked).length;
    const off = count === 0;
    status.classList.toggle("is-off", off);
    status.classList.toggle("is-armed", !off);
    status.querySelector(".sesamo-status__icon").textContent = off ? "○" : "✓";
    statusTitle.textContent = off ? status.dataset.titleOff : status.dataset.titleArmed;
    statusCount.textContent = off
      ? statusCount.dataset.off
      : count === 1
        ? statusCount.dataset.singular
        : statusCount.dataset.plural.replace("%d", String(count));
    offNote.hidden = !off;
  }

  function setAll(checked) {
    checkboxes.forEach((checkbox) => {
      checkbox.checked = checked;
    });
    update();
    if (checkboxes[0]) checkboxes[0].focus();
  }

  checkboxes.forEach((checkbox) => checkbox.addEventListener("change", update));
  root.querySelector("[data-sesamo-select-all]").addEventListener("click", () => setAll(true));
  root.querySelector("[data-sesamo-clear]").addEventListener("click", () => setAll(false));
})();
