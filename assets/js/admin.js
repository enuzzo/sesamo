(function () {
  "use strict";

  const root = document.querySelector(".sesamo-wrap");
  if (!root) return;

  const status = root.querySelector("[data-sesamo-status]");
  const statusTitle = root.querySelector("[data-sesamo-status-title]");
  const statusCount = root.querySelector("[data-sesamo-status-count]");
  const customList = root.querySelector("[data-sesamo-custom-list]");
  const customTemplate = root.querySelector("[data-sesamo-custom-template]");
  const addButton = root.querySelector("[data-sesamo-add]");
  const customCount = root.querySelector("[data-sesamo-custom-count]");
  const maxCustom = Number(customList ? customList.dataset.max : 20) || 20;
  let nextIndex = customList ? customList.querySelectorAll("[data-sesamo-custom]").length : 0;
  let activeRecorder = null;
  const form = root.querySelector("form");
  const homeOrigin = form ? form.dataset.sesamoHome || "" : "";
  const homeHost = form ? form.dataset.sesamoHost || "" : "";
  const messages = {
    other_site: form ? form.dataset.msgOtherSite : "",
    other_scheme: form ? form.dataset.msgOtherScheme : "",
    invalid: form ? form.dataset.msgInvalid : "",
    empty: form ? form.dataset.msgEmpty : "",
  };

  const namedKeys = new Map(
    [
      "ArrowUp",
      "ArrowDown",
      "ArrowLeft",
      "ArrowRight",
      "Enter",
      "Escape",
      "Space",
      "Tab",
      "Backspace",
      "Delete",
      "Home",
      "End",
      "PageUp",
      "PageDown",
      "Insert",
    ].map((key) => [key.toLowerCase(), key]),
  );

  const displayLabels = {
    ArrowUp: "↑",
    ArrowDown: "↓",
    ArrowLeft: "←",
    ArrowRight: "→",
    Enter: "↵",
    Escape: "Esc",
    Backspace: "⌫",
    Delete: "Del",
    PageUp: "PgUp",
    PageDown: "PgDn",
  };

  function normalizeToken(token) {
    if (token === " ") return "Space";
    if (typeof token !== "string") return "";
    const trimmed = token.trim();
    if (trimmed === "") return "";
    const named = namedKeys.get(trimmed.toLowerCase());
    if (named) return named;
    return Array.from(trimmed).length === 1 && !/[\p{C}\p{Z}]/u.test(trimmed) ? trimmed.toLowerCase() : "";
  }

  function readTokens(input) {
    const raw = input.value.trim();
    if (raw === "") return [];
    const tokens = raw.split(/\s+/u).map(normalizeToken);
    return tokens.length <= 64 && tokens.every(Boolean) ? tokens : [];
  }

  function renderPreview(row) {
    const input = row.querySelector("[data-sesamo-sequence]");
    const preview = row.querySelector("[data-sesamo-preview]");
    if (!input || !preview) return;

    const tokens = readTokens(input);
    preview.replaceChildren();
    tokens.forEach((token) => {
      const keycap = document.createElement("kbd");
      keycap.setAttribute("aria-hidden", "true");
      keycap.textContent = displayLabels[token] || (/^[a-z]$/.test(token) ? token.toUpperCase() : token);
      preview.append(keycap);
    });
  }

  // Mirrors Settings::destination_problem(): "" when usable, otherwise why not.
  function destinationProblem(value) {
    const raw = value.trim();
    if (raw === "") return "empty";
    if (raw.length > 2048) return "invalid";
    if (raw.startsWith("/") && !raw.startsWith("//")) return "";
    let url;
    try {
      url = new URL(raw.startsWith("//") ? `https:${raw}` : raw);
    } catch {
      return "invalid";
    }
    if (!["http:", "https:"].includes(url.protocol) || url.username || url.password || url.hostname === "") return "invalid";
    if (homeOrigin && url.origin === homeOrigin) return "";
    const homeHostname = homeHost.replace(/:\d+$/, "");
    return url.hostname === homeHostname ? "other_scheme" : "other_site";
  }

  function suggestedPath(value, fallback) {
    try {
      const raw = value.trim();
      const path = new URL(raw.startsWith("//") ? `https:${raw}` : raw).pathname;
      if (path !== "/" && /^\/[A-Za-z0-9._~%/-]{1,200}$/.test(path)) return path;
    } catch {
      // Not a URL: fall back to the route's own path.
    }
    return fallback;
  }

  function fillMessage(template, value, suggestion) {
    const shown = Array.from(value.trim()).slice(0, 120).join("");
    const args = [shown, homeHost, suggestion];
    return (template || "").replace(/%(\d)\$s/g, (match, index) => args[Number(index) - 1] ?? match);
  }

  // final: true on leaving the field or toggling the route; false while typing,
  // when only cross-site addresses (complete enough to judge) raise a hint.
  function checkDestination(input, final) {
    const route = input.closest("[data-sesamo-route]");
    const hint = route ? route.querySelector("[data-sesamo-destination-hint]") : null;
    if (!hint) return;
    const enabled = route.querySelector("[data-sesamo-enabled]");
    let problem = destinationProblem(input.value);
    if (problem === "empty" && !(enabled && enabled.checked)) problem = "";
    if (!final && problem !== "" && problem !== "other_site" && problem !== "other_scheme") {
      if (hint.hidden) return;
    }
    if (problem === "") {
      hint.hidden = true;
      hint.textContent = "";
      input.classList.remove("is-invalid");
      input.removeAttribute("aria-invalid");
      return;
    }
    hint.textContent = fillMessage(messages[problem], input.value, suggestedPath(input.value, input.dataset.sesamoFallback || "/secret/"));
    hint.hidden = false;
    input.classList.add("is-invalid");
    input.setAttribute("aria-invalid", "true");
  }

  function routeIsComplete(route) {
    const enabled = route.querySelector("[data-sesamo-enabled]");
    const destination = route.querySelector("[data-sesamo-destination]");
    if (!enabled || !enabled.checked || !destination || destinationProblem(destination.value) !== "") return false;
    if (!route.matches("[data-sesamo-custom]")) return true;
    const name = route.querySelector("[data-sesamo-name]");
    const sequence = route.querySelector("[data-sesamo-sequence]");
    return Boolean(name && name.value.trim() !== "" && sequence && readTokens(sequence).length >= 2);
  }

  function updateStatus() {
    const count = Array.from(root.querySelectorAll("[data-sesamo-route]")).filter(routeIsComplete).length;
    const off = count === 0;
    if (status) {
      status.classList.toggle("is-off", off);
      status.classList.toggle("is-armed", !off);
      const icon = status.querySelector(".sesamo-status__icon");
      if (icon) icon.textContent = off ? "○" : "✓";
    }
    if (statusTitle && status) statusTitle.textContent = off ? status.dataset.titleOff : status.dataset.titleArmed;
    if (statusCount) {
      statusCount.textContent = off
        ? statusCount.dataset.off
        : count === 1
          ? statusCount.dataset.singular
          : statusCount.dataset.plural.replace("%d", String(count));
    }

    const rows = customList ? Array.from(customList.querySelectorAll("[data-sesamo-custom]")) : [];
    const used = rows.filter((row) => {
      const name = row.querySelector("[data-sesamo-name]");
      const sequence = row.querySelector("[data-sesamo-sequence]");
      const destination = row.querySelector("[data-sesamo-destination]");
      return [name, sequence, destination].some((input) => input && input.value.trim() !== "");
    }).length;
    if (addButton) addButton.disabled = rows.length >= maxCustom;
    if (customCount) customCount.textContent = `${used} of ${maxCustom} custom combinations used.`;
  }

  function stopRecording() {
    if (!activeRecorder) return;
    const button = activeRecorder.querySelector("[data-sesamo-record]");
    activeRecorder.classList.remove("is-recording");
    if (button) {
      button.textContent = button.dataset.labelRecord;
      button.setAttribute("aria-pressed", "false");
    }
    activeRecorder = null;
  }

  function startRecording(row) {
    if (activeRecorder === row) {
      stopRecording();
      return;
    }
    stopRecording();
    activeRecorder = row;
    const input = row.querySelector("[data-sesamo-sequence]");
    const button = row.querySelector("[data-sesamo-record]");
    if (input) input.value = "";
    renderPreview(row);
    row.classList.add("is-recording");
    if (button) {
      button.textContent = button.dataset.labelStop;
      button.setAttribute("aria-pressed", "true");
      button.focus();
    }
    updateStatus();
  }

  function onRecorderKeydown(event) {
    if (!activeRecorder) return;
    if (event.key === "Escape") {
      event.preventDefault();
      stopRecording();
      return;
    }
    if (event.metaKey || event.ctrlKey || event.altKey || ["Shift", "Control", "Alt", "Meta", "Dead", "Unidentified"].includes(event.key)) {
      return;
    }

    const token = normalizeToken(event.key);
    if (!token) return;
    event.preventDefault();
    const input = activeRecorder.querySelector("[data-sesamo-sequence]");
    if (!input) return;
    const tokens = readTokens(input);
    if (tokens.length >= 64) {
      stopRecording();
      return;
    }
    tokens.push(token);
    input.value = tokens.join(" ");
    renderPreview(activeRecorder);
    updateStatus();
    if (tokens.length >= 64) stopRecording();
  }

  function bindRow(row) {
    const input = row.querySelector("[data-sesamo-sequence]");
    const record = row.querySelector("[data-sesamo-record]");
    const clear = row.querySelector("[data-sesamo-clear-sequence]");
    const remove = row.querySelector("[data-sesamo-remove]");

    if (input) {
      input.addEventListener("input", () => {
        renderPreview(row);
        updateStatus();
      });
    }
    if (record) record.addEventListener("click", () => startRecording(row));
    if (clear) {
      clear.addEventListener("click", () => {
        if (activeRecorder === row) stopRecording();
        if (input) input.value = "";
        renderPreview(row);
        updateStatus();
        if (input) input.focus();
      });
    }
    if (remove) {
      remove.addEventListener("click", () => {
        if (activeRecorder === row) stopRecording();
        const nextFocus = addButton;
        row.remove();
        updateStatus();
        if (nextFocus) nextFocus.focus();
      });
    }
  }

  function addRow() {
    if (!customList || !customTemplate) return;
    const blank = Array.from(customList.querySelectorAll("[data-sesamo-custom]")).find((row) =>
      Array.from(row.querySelectorAll("[data-sesamo-name], [data-sesamo-sequence], [data-sesamo-destination]")).every(
        (input) => input.value.trim() === "",
      ),
    );
    if (blank) {
      const blankName = blank.querySelector("[data-sesamo-name]");
      if (blankName) blankName.focus();
      return;
    }
    if (customList.querySelectorAll("[data-sesamo-custom]").length >= maxCustom) return;
    const wrapper = document.createElement("div");
    wrapper.innerHTML = customTemplate.innerHTML.replaceAll("__INDEX__", String(nextIndex));
    nextIndex += 1;
    const row = wrapper.firstElementChild;
    if (!row) return;
    customList.append(row);
    bindRow(row);
    updateStatus();
    const name = row.querySelector("[data-sesamo-name]");
    if (name) name.focus();
  }

  root.querySelectorAll("[data-sesamo-custom]").forEach(bindRow);
  root.addEventListener("change", (event) => {
    if (event.target.matches("[data-sesamo-destination]")) checkDestination(event.target, true);
    if (event.target.matches("[data-sesamo-enabled]")) {
      const route = event.target.closest("[data-sesamo-route]");
      const destination = route ? route.querySelector("[data-sesamo-destination]") : null;
      if (destination) checkDestination(destination, true);
    }
    updateStatus();
  });
  root.addEventListener("input", (event) => {
    if (event.target.matches("[data-sesamo-destination]")) checkDestination(event.target, false);
    if (event.target.matches("[data-sesamo-name], [data-sesamo-destination]")) updateStatus();
  });
  document.addEventListener("keydown", onRecorderKeydown, true);
  if (form) form.addEventListener("submit", stopRecording);

  const presetCheckboxes = Array.from(root.querySelectorAll(".sesamo-preset [data-sesamo-enabled]"));
  const setAllPresets = (checked) => {
    presetCheckboxes.forEach((checkbox) => {
      checkbox.checked = checked;
      // Clearing removes "add a destination" hints; selecting all stays quiet.
      const route = checkbox.closest("[data-sesamo-route]");
      const destination = route ? route.querySelector("[data-sesamo-destination]") : null;
      if (destination && !checked) checkDestination(destination, true);
    });
    updateStatus();
    if (presetCheckboxes[0]) presetCheckboxes[0].focus();
  };

  const selectAll = root.querySelector("[data-sesamo-select-all]");
  const clearPresets = root.querySelector("[data-sesamo-clear-presets]");
  if (selectAll) selectAll.addEventListener("click", () => setAllPresets(true));
  if (clearPresets) clearPresets.addEventListener("click", () => setAllPresets(false));
  if (addButton) addButton.addEventListener("click", addRow);

  updateStatus();
})();
