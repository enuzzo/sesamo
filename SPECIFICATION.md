# Sesamo product specification

## Purpose

Sesamo lets a WordPress administrator associate culturally recognisable video-game keyboard sequences with one same-site destination. It is for hidden pages, playful launches, campaign easter eggs, and internal experiences. It is never an authentication or authorization mechanism.

## Roles

- **Administrator:** chooses presets, destination, and timeout.
- **Visitor:** enters a sequence on a public frontend page and is redirected.
- **Developer:** observes or cancels a match through a browser event.

## Settings

The plugin adds **Settings → Sesamo** for users with `manage_options` and provides:

1. a multiple-choice list from the canonical preset registry;
2. one same-origin HTTP(S) destination URL;
3. a maximum inter-key pause from 250 to 5,000 milliseconds;
4. Settings API nonce and capability protection;
5. validation on both save and read;
6. explicit armed/off state and an access-control warning.

No selected presets means detection is disabled and no frontend script is enqueued.

## Detection

- Single-character comparison is case-insensitive; named `KeyboardEvent.key` values retain their names.
- A pause longer than the configured value resets partial input.
- Enabled presets share one bounded rolling buffer.
- A match resets the buffer; only one navigation starts per page lifecycle.
- Events from input, textarea, select, content-editable, inherited content-editable, editable ARIA roles, focused closed-shadow hosts, and `[data-sesamo-ignore]` contexts are ignored.
- Repeated, composing, default-prevented, Ctrl, Alt, and Command-modified events are ignored.
- Runtime configuration is shape-checked and capped before matching.

## Redirect and integration

Before navigation, dispatch the cancelable event `sesamo:matched` with:

```json
{
  "preset": { "id": "konami", "label": "Konami Code" },
  "destinationUrl": "https://example.com/secret/"
}
```

The deprecated pre-release event `konami-code-activator:matched` is also dispatched during the 0.x migration window. Cancellation of either event cancels navigation.

PHP and JavaScript independently require the destination to use HTTP(S) and match the current WordPress origin. Navigation uses a temporary anchor with `noreferrer noopener` and a `no-referrer` policy.

## Stored data

One option named `netmilk_sesamo_settings`:

```json
{
  "enabled_presets": ["konami", "iddqd"],
  "destination_url": "https://example.com/iddqd/",
  "max_pause": 1500
}
```

Activation migrates the pre-release `konami_code_activator_settings` option when present. Uninstall deletes both names. No custom tables or personal data are created.

## Compatibility and acceptance

- PHP 7.4+ and WordPress 6.3+; primary CI on PHP 8.3; current compatibility target WordPress 7.0.
- No runtime dependencies, cookies, tracking, external requests, or remote code.
- Translatable strings, context-appropriate escaping, keyboard access, reduced-motion support, responsive settings at 600 px and below.
- Release ZIP contains runtime files only and must pass version, syntax, unit/smoke, manifest, and checksum verification.

`1.0.0` is reserved until all acceptance criteria in [docs/COMPATIBILITY.md](docs/COMPATIBILITY.md) are verified in real WordPress installations.
