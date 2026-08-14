# Sesamo product specification

## Purpose

Sesamo lets a WordPress administrator associate built-in or custom keyboard combinations with same-site destinations. It is for hidden pages, playful launches, campaign easter eggs, and internal experiences. It is never an authentication or authorization mechanism.

## Roles

- **Administrator:** chooses presets, creates custom combinations, assigns destinations, and sets the timeout.
- **Visitor:** enters a sequence on a public frontend page and is redirected.
- **Developer:** observes or cancels a match through a browser event.

## Settings

The plugin adds **Settings → Sesamo** for users with `manage_options` and provides:

1. a multiple-choice list from the canonical preset registry, with one destination per preset;
2. up to twenty custom combinations, each with a name, enabled state, 2–64 key tokens, stable ID, and destination;
3. an accessible key recorder plus editable whitespace-separated `KeyboardEvent.key` input;
4. same-origin HTTP(S) destination enforcement for every route;
5. duplicate, prefix, and suffix collision detection;
6. a maximum inter-key pause from 250 to 5,000 milliseconds;
7. Settings API nonce and capability protection;
8. validation on both save and read;
9. explicit armed/off state and an access-control warning.

No complete enabled combination means detection is disabled and no frontend script is enqueued.

## Detection

- Single-character comparison is case-insensitive; named `KeyboardEvent.key` values retain their names.
- A pause longer than the configured value resets partial input.
- Enabled built-in and custom combinations share one bounded rolling buffer.
- Custom input accepts printable single-code-point keys plus a small allowlist of named navigation/editing keys; modifier-only and unknown keys are rejected.
- The browser configuration is capped at thirty routes: ten built-ins plus twenty custom combinations.
- A match resets the buffer; only one navigation starts per page lifecycle.
- Events from input, textarea, select, content-editable, inherited content-editable, editable ARIA roles, focused closed-shadow hosts, and `[data-sesamo-ignore]` contexts are ignored.
- Repeated, composing, default-prevented, Ctrl, Alt, and Command-modified events are ignored.
- Runtime configuration is shape-checked and capped before matching.

## Redirect and integration

Before navigation, dispatch the cancelable event `sesamo:matched` with:

```json
{
  "combination": { "id": "konami", "label": "Konami Code", "source": "preset" },
  "preset": { "id": "konami", "label": "Konami Code" },
  "destinationUrl": "https://example.com/secret/"
}
```

The `preset` projection and deprecated pre-release event `konami-code-activator:matched` remain during the 0.x migration window. Custom combinations expose their stable ID through both projections. Cancellation of either event cancels navigation.

PHP and JavaScript independently require the destination to use HTTP(S) and match the current WordPress origin. Navigation uses a temporary anchor with `noreferrer noopener` and a `no-referrer` policy.

## Stored data

One option named `netmilk_sesamo_settings`:

```json
{
  "schema_version": 2,
  "enabled_presets": ["konami", "iddqd"],
  "preset_destinations": {
    "konami": "https://example.com/konami/",
    "iddqd": "https://example.com/godmode/"
  },
  "custom_combinations": [
    {
      "id": "custom_91de3b2d31f96c0a",
      "enabled": true,
      "name": "Open the vault",
      "sequence": ["s", "e", "s", "a", "m", "o"],
      "destination_url": "https://example.com/vault/"
    }
  ],
  "max_pause": 1500,
  "destination_url": "https://example.com/konami/"
}
```

`destination_url` is a temporary 0.1 rollback bridge and is not used by the 0.2 frontend. Activation migrates the pre-release `konami_code_activator_settings` option when present. Schema-1 settings are upgraded on activation or the next admin request by copying the former shared destination to every enabled preset. Uninstall deletes both option names. No custom tables or personal data are created.

## Compatibility and acceptance

- PHP 7.4+ and WordPress 6.3+; primary CI on PHP 8.3; current compatibility target WordPress 7.0.
- No runtime dependencies, cookies, tracking, external requests, or remote code.
- Translatable strings, context-appropriate escaping, keyboard access, reduced-motion support, responsive settings at 600 px and below.
- Release ZIP contains runtime files only and must pass version, syntax, unit/smoke, manifest, and checksum verification.

`1.0.0` is reserved until all acceptance criteria in [docs/COMPATIBILITY.md](docs/COMPATIBILITY.md) are verified in real WordPress installations.
