const test = require("node:test");
const assert = require("node:assert/strict");
const {
  boot,
  createMatcher,
  isTypingContext,
  isTypingEvent,
	normalizeCombinations,
  normalizeDestination,
  normalizeKey,
  normalizePresets,
	sequencesConflict,
} = require("../assets/js/sesamo.js");

const presets = [
  {
    id: "konami",
    label: "Konami Code",
    sequence: ["ArrowUp", "ArrowUp", "ArrowDown", "ArrowDown", "ArrowLeft", "ArrowRight", "ArrowLeft", "ArrowRight", "b", "a"],
  },
  { id: "iddqd", label: "IDDQD", sequence: ["i", "d", "d", "q", "d"] },
];

const historicalPresets = [
  presets[0],
  presets[1],
  { id: "idkfa", label: "IDKFA", sequence: [..."idkfa"] },
  { id: "xyzzy", label: "XYZZY", sequence: [..."xyzzy"] },
  { id: "justin_bailey", label: "JUSTIN BAILEY", sequence: [..."justinbailey"] },
  { id: "rosebud", label: "ROSEBUD", sequence: [..."rosebud"] },
  { id: "motherlode", label: "MOTHERLODE", sequence: [..."motherlode"] },
  { id: "power_overwhelming", label: "POWER OVERWHELMING", sequence: [..."poweroverwhelming"] },
  { id: "there_is_no_cow_level", label: "THERE IS NO COW LEVEL", sequence: [..."thereisnocowlevel"] },
  { id: "hesoyam", label: "HESOYAM", sequence: [..."hesoyam"] },
];

function createRoot({ cancelType = "" } = {}) {
  const listeners = new Map();
  const events = [];
  const links = [];
  let now = 0;
  const root = {
    URL,
    location: { href: "https://example.test/page/", assign: (url) => links.push(url) },
    performance: { now: () => (now += 100) },
    CustomEvent: class {
      constructor(type, options) {
        this.type = type;
        this.detail = options.detail;
        this.cancelable = options.cancelable;
      }
    },
    document: {
      body: { append: (link) => links.push(link.href) },
      createElement: () => ({ click() {}, remove() {} }),
    },
    addEventListener: (type, callback) => listeners.set(type, callback),
    removeEventListener: (type) => listeners.delete(type),
    dispatchEvent: (event) => {
      events.push(event);
      return event.type !== cancelType;
    },
  };
  return { root, listeners, events, links };
}

test("normalizes letter keys without changing named keys", () => {
  assert.equal(normalizeKey("B"), "b");
  assert.equal(normalizeKey("ArrowUp"), "ArrowUp");
	assert.equal(normalizeKey(" "), "Space");
});

test("filters malformed and oversized presets", () => {
  const oversized = { id: "long", sequence: Array(65).fill("x") };
	assert.deepEqual(normalizePresets([null, {}, oversized, presets[1]]), [
	  { ...presets[1], source: "preset", destinationUrl: "" },
	]);
});

test("rejects ambiguous prefix and suffix combinations", () => {
	assert.equal(sequencesConflict([..."id"], [..."iddqd"]), true);
	assert.equal(sequencesConflict([..."dqd"], [..."iddqd"]), true);
	assert.equal(sequencesConflict([..."sesamo"], [..."iddqd"]), false);
	assert.deepEqual(
	  normalizePresets([
		{ id: "long", sequence: [..."iddqd"] },
		{ id: "prefix", sequence: [..."id"] },
		{ id: "suffix", sequence: [..."dqd"] },
	  ]).map(({ id }) => id),
	  ["long"],
	);
});

test("normalizes bounded per-combination destinations independently", () => {
	const { root } = createRoot();
	const combinations = normalizeCombinations(
	  {
		combinations: [
		  { ...presets[1], source: "preset", destinationUrl: "/godmode/" },
		  { id: "custom_vault", label: "Open the vault", source: "custom", sequence: [..."sesamo"], destinationUrl: "/vault/" },
		  { id: "bad", sequence: [..."bad"], destinationUrl: "https://evil.example/" },
		],
	  },
	  root,
	);

	assert.deepEqual(
	  combinations.map(({ id, source, destinationUrl }) => ({ id, source, destinationUrl })),
	  [
		{ id: "iddqd", source: "preset", destinationUrl: "https://example.test/godmode/" },
		{ id: "custom_vault", source: "custom", destinationUrl: "https://example.test/vault/" },
	  ],
	);
});

test("accepts only same-origin HTTP(S) destinations", () => {
  const { root } = createRoot();
  assert.equal(normalizeDestination("/hidden/", root), "https://example.test/hidden/");
  assert.equal(normalizeDestination("https://evil.example/", root), "");
  assert.equal(normalizeDestination("javascript:alert(1)", root), "");
  assert.equal(normalizeDestination("https://user:password@example.test/hidden/", root), "");
	assert.equal(normalizeDestination(`https://example.test/${"x".repeat(3000)}`, root), "");
});

test("matches every historical preset", () => {
  historicalPresets.forEach((preset) => {
    const matcher = createMatcher([preset], 1500);
    let match = null;
    preset.sequence.forEach((key, index) => {
      match = matcher.push(key, index * 50);
    });
    assert.equal(match.id, preset.id);
  });
});

test("resets a partial sequence after the configured pause", () => {
  const matcher = createMatcher(presets, 500);
  matcher.push("i", 0);
  matcher.push("d", 100);
  matcher.push("d", 200);
  matcher.push("q", 1000);
  assert.equal(matcher.push("d", 1100), null);
});

test("ignores direct and inherited typing contexts", () => {
  assert.equal(isTypingContext({ tagName: "INPUT" }), true);
  assert.equal(isTypingContext({ tagName: "DIV", closest: () => ({}) }), true);
  assert.equal(isTypingContext({ tagName: "BODY", closest: () => null }), false);
  assert.equal(isTypingEvent({ target: { tagName: "X-EDITOR" }, composedPath: () => [{ tagName: "INPUT" }] }), true);
  assert.equal(isTypingContext({ tagName: "DIV", getAttribute: () => "textbox" }), true);

  const closedHost = { tagName: "DIV" };
  closedHost.ownerDocument = { activeElement: closedHost };
  assert.equal(isTypingEvent({ target: closedHost, composedPath: () => [closedHost] }), true);

  const inertCustomHost = { tagName: "APP-SHELL", ownerDocument: { activeElement: { tagName: "BODY" } } };
  assert.equal(isTypingEvent({ target: { tagName: "BODY" }, composedPath: () => [{ tagName: "BODY" }, inertCustomHost] }), false);
});

test("boots, emits both events, redirects once, and tears down", () => {
  const { root, listeners, events, links } = createRoot();
  const teardown = boot({ presets: [presets[1]], destinationUrl: "/hidden/", maxPause: 1500 }, root);
  const keydown = listeners.get("keydown");
  for (const key of "iddqd") keydown({ key, target: { tagName: "BODY" } });
  for (const key of "iddqd") keydown({ key, target: { tagName: "BODY" } });

  assert.deepEqual(events.map((event) => event.type), ["sesamo:matched", "konami-code-activator:matched"]);
	assert.deepEqual(events[0].detail.combination, { id: "iddqd", label: "IDDQD", source: "preset" });
  assert.deepEqual(links, ["https://example.test/hidden/"]);
  teardown();
  assert.equal(listeners.has("keydown"), false);
  assert.equal(root.__NETMILK_SESAMO_BOOTED__, false);
});

test("routes a custom combination to its own destination", () => {
	const { root, listeners, events, links } = createRoot();
	boot(
	  {
		combinations: [
		  { id: "iddqd", label: "IDDQD", source: "preset", sequence: [..."iddqd"], destinationUrl: "/godmode/" },
		  { id: "custom_vault", label: "Open the vault", source: "custom", sequence: [..."sesamo"], destinationUrl: "/vault/" },
		],
		maxPause: 1500,
	  },
	  root,
	);
	for (const key of "sesamo") listeners.get("keydown")({ key, target: { tagName: "BODY" } });

	assert.deepEqual(links, ["https://example.test/vault/"]);
	assert.deepEqual(events[0].detail.combination, {
	  id: "custom_vault",
	  label: "Open the vault",
	  source: "custom",
	});
	assert.equal(events[0].detail.destinationUrl, "https://example.test/vault/");
});

test("a listener can cancel navigation through either event", () => {
  const { root, listeners, links } = createRoot({ cancelType: "sesamo:matched" });
  boot({ presets: [presets[1]], destinationUrl: "/hidden/" }, root);
  for (const key of "iddqd") listeners.get("keydown")({ key, target: { tagName: "BODY" } });
  assert.deepEqual(links, []);
});

test("default-prevented, modified, composing, and repeated keys are ignored", () => {
  const { root, listeners, events } = createRoot();
  boot({ presets: [{ id: "x", sequence: ["x"] }], destinationUrl: "/hidden/" }, root);
  const keydown = listeners.get("keydown");
  keydown({ key: "x", target: {}, defaultPrevented: true });
  keydown({ key: "x", target: {}, ctrlKey: true });
  keydown({ key: "x", target: {}, isComposing: true });
  keydown({ key: "x", target: {}, repeat: true });
  assert.deepEqual(events, []);
});
