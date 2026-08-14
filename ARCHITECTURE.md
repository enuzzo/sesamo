# Architecture

## Runtime flow

```text
untrusted WordPress option
    ↓ Settings::normalize()
schema 2 + preset registry + bounded custom combinations
    ↓ Settings::public_combinations()
JSON config before dependency-free detector
    ↓ browser validation + rolling matcher
cancelable sesamo:matched event
    ↓ route-specific no-referrer same-origin navigation
```

## Components

- `sesamo.php` — metadata, constants, activation, hooks, and action link.
- `includes/class-presets.php` — canonical built-in preset registry.
- `includes/class-combinations.php` — custom limits, token normalization, and prefix/suffix collision rules.
- `includes/class-settings.php` — schema migration, defaults, normalization, route projection, Settings API, and admin UI.
- `includes/class-frontend.php` — conditional config and detector enqueue.
- `assets/js/sesamo.js` — UMD-style matcher, runtime validation, event, and navigation.
- `assets/js/admin.js` — progressive status, custom row management, and accessible key recording; saved rows and the blank server-rendered draft remain editable without it.
- `assets/css/admin.css` — isolated responsive admin design system.
- `uninstall.php` — deletion of current and pre-release options only.

Preset settings store IDs rather than sequence copies. Custom settings store only normalized `KeyboardEvent.key` tokens, never code or callbacks. Ten built-ins plus twenty custom combinations cap route count; 64 keys cap sequence length and matcher memory. No WordPress route, REST endpoint, scheduled task, network request, database query, filesystem write, cookie, or personal-data processor exists.

## Trust boundaries

1. **Administrator → Settings API:** nonce and `manage_options`, followed by structural validation.
2. **Options table → PHP:** always untrusted; schema, types, custom tokens, collisions, destinations, and bounds are normalized on every read.
3. **PHP → browser:** minimal JSON encoded by WordPress, then shape-checked again.
4. **Keyboard → matcher:** ignored in typing/editing and modified-event contexts; buffer stays bounded.
5. **Match → navigation:** route-owned same-origin HTTP(S) destination, cancelable integration boundary, one navigation, no referrer.

## Extension boundaries

Add built-in presets only in `Presets::all()` and add detector coverage. Custom combinations must remain data only: no scripts, callbacks, remote feeds, or unbounded token types. Any increase to the 20-route or 64-key caps requires a performance and option-size review.

Observe `sesamo:matched`; do not fork the detector for animations. The pre-release alias is deprecated and will be removed only in a documented major release.

## Design system

The admin uses native WordPress controls and system typography. Sesamo identity comes from the emerald/ink/brass tokens, keycap sequence anatomy, and armed/off status—not a replacement admin shell. The accepted visual reference is [docs/design/admin-settings-concept.png](docs/design/admin-settings-concept.png).
