(function (root, factory) {
  "use strict";

  const api = factory();

  if (typeof module === "object" && module.exports) {
    module.exports = api;
  }

  if (root && root.document) {
    root.Sesamo = api;
    api.boot(root.NETMILK_SESAMO_CONFIG || {}, root);
  }
})(typeof window !== "undefined" ? window : globalThis, function () {
  "use strict";

  function normalizeKey(key) {
    return typeof key === "string" && key.length === 1 ? key.toLowerCase() : key;
  }

  function normalizePresets(presets) {
    if (!Array.isArray(presets)) return [];

    return presets
      .filter(
        (preset) =>
          preset &&
          typeof preset.id === "string" &&
          preset.id.length <= 64 &&
          Array.isArray(preset.sequence) &&
          preset.sequence.length > 0 &&
          preset.sequence.length <= 64 &&
          preset.sequence.every((key) => typeof key === "string" && key.length > 0 && key.length <= 32),
      )
      .map((preset) => ({
        id: preset.id,
        label: typeof preset.label === "string" ? preset.label : preset.id,
        sequence: preset.sequence.map(normalizeKey),
      }));
  }

  function normalizeDestination(destinationUrl, root) {
    if (
      typeof destinationUrl !== "string" ||
      !root ||
      !root.URL ||
      !root.location ||
      typeof root.location.href !== "string"
    ) {
      return "";
    }

    try {
      const destination = new root.URL(destinationUrl, root.location.href);
      const current = new root.URL(root.location.href);
      if (
        !["http:", "https:"].includes(destination.protocol) ||
        destination.username !== "" ||
        destination.password !== "" ||
        destination.origin !== current.origin
      ) {
        return "";
      }
      return destination.href;
    } catch (_error) {
      return "";
    }
  }

  function createMatcher(presets, maxPause) {
    const available = normalizePresets(presets);
    const pause = Math.max(250, Math.min(5000, Number(maxPause) || 1500));
    const maxLength = available.reduce((length, preset) => Math.max(length, preset.sequence.length), 0);
    let buffer = [];
    let lastKeyAt = null;

    function reset() {
      buffer = [];
      lastKeyAt = null;
    }

    function push(key, timestamp) {
      if (maxLength === 0 || typeof key !== "string") return null;

      const now = Number.isFinite(timestamp) ? timestamp : Date.now();
      const token = normalizeKey(key);

      if (lastKeyAt !== null && now - lastKeyAt > pause) {
        buffer = [];
      }

      lastKeyAt = now;
      buffer.push(token);

      if (buffer.length > maxLength) {
        buffer = buffer.slice(-maxLength);
      }

      for (const preset of available) {
        if (preset.sequence.length > buffer.length) continue;

        const offset = buffer.length - preset.sequence.length;
        const matches = preset.sequence.every((expected, index) => buffer[offset + index] === expected);
        if (matches) {
          reset();
          return preset;
        }
      }

      return null;
    }

    return { push, reset };
  }

  function isTypingContext(target) {
    if (!target) return false;
    const tagName = typeof target.tagName === "string" ? target.tagName.toUpperCase() : "";
    const role = typeof target.getAttribute === "function" ? target.getAttribute("role") : "";
    if (
      target.isContentEditable ||
      ["INPUT", "TEXTAREA", "SELECT"].includes(tagName) ||
      ["textbox", "searchbox", "combobox"].includes(role) ||
      (typeof target.matches === "function" && target.matches("[data-sesamo-ignore]"))
    ) {
      return true;
    }
    return typeof target.closest === "function" && Boolean(target.closest("[contenteditable]:not([contenteditable='false'])"));
  }

  function isTypingEvent(event) {
    if (!event) return false;
    const path = typeof event.composedPath === "function" ? event.composedPath() : [event.target];
    if (path.some(isTypingContext)) return true;

    const target = event.target;
    const document = target && target.ownerDocument;
    const activeElement = document && document.activeElement;
    const activeTag = activeElement && typeof activeElement.tagName === "string" ? activeElement.tagName.toUpperCase() : "";

    // A closed ShadowRoot hides its focused input and retargets both the event
    // and document.activeElement to the host. Fail closed for that focused host
    // (and other focused interactive elements), but not for inert wrappers.
    return Boolean(activeElement && activeElement === target && !["BODY", "HTML"].includes(activeTag));
  }

  function navigate(destinationUrl, root) {
    const document = root.document;
    if (!document || !document.body) {
      root.location.assign(destinationUrl);
      return;
    }

    const link = document.createElement("a");
    link.href = destinationUrl;
    link.rel = "noreferrer noopener";
    link.referrerPolicy = "no-referrer";
    link.hidden = true;
    document.body.append(link);
    link.click();
    link.remove();
  }

  function dispatchMatch(root, type, matched, destinationUrl) {
    const event = new root.CustomEvent(type, {
      cancelable: true,
      detail: {
        preset: { id: matched.id, label: matched.label },
        destinationUrl,
      },
    });
    return root.dispatchEvent(event);
  }

  function boot(config, root) {
    if (!root || !root.addEventListener || root.__NETMILK_SESAMO_BOOTED__) return null;

    const presets = normalizePresets(config.presets);
    const destinationUrl = normalizeDestination(config.destinationUrl, root);
    if (presets.length === 0 || destinationUrl === "") return null;

    root.__NETMILK_SESAMO_BOOTED__ = true;
    const matcher = createMatcher(presets, config.maxPause);
    let redirecting = false;

    const onKeyDown = (event) => {
      if (
        redirecting ||
        event.defaultPrevented ||
        event.repeat ||
        event.isComposing ||
        event.metaKey ||
        event.ctrlKey ||
        event.altKey ||
        isTypingEvent(event)
      ) {
        return;
      }

      const matched = matcher.push(event.key, root.performance ? root.performance.now() : Date.now());
      if (!matched) return;

      const allowed = dispatchMatch(root, "sesamo:matched", matched, destinationUrl);
      const legacyAllowed = dispatchMatch(root, "konami-code-activator:matched", matched, destinationUrl);
      if (!allowed || !legacyAllowed) return;

      redirecting = true;
      navigate(destinationUrl, root);
    };

    root.addEventListener("keydown", onKeyDown, { passive: true });

    return function teardown() {
      root.removeEventListener("keydown", onKeyDown);
      root.__NETMILK_SESAMO_BOOTED__ = false;
      matcher.reset();
    };
  }

  return {
    boot,
    createMatcher,
    isTypingContext,
    isTypingEvent,
    normalizeDestination,
    normalizeKey,
    normalizePresets,
  };
});
