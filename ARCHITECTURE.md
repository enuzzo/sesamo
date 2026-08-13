# Architecture

## Runtime flow

```text
untrusted WordPress option
    ↓ Settings::normalize()
allowlisted preset IDs + same-origin URL + bounded timeout
    ↓ Presets::public_config()
JSON config before dependency-free detector
    ↓ browser validation + rolling matcher
cancelable sesamo:matched event
    ↓ no-referrer same-origin navigation
```

## Components

- `sesamo.php` — metadata, constants, activation, hooks, and action link.
- `includes/class-presets.php` — canonical preset registry and public projection.
- `includes/class-settings.php` — migration, defaults, normalization, Settings API, and admin UI.
- `includes/class-frontend.php` — conditional config and detector enqueue.
- `assets/js/sesamo.js` — UMD-style matcher, runtime validation, event, and navigation.
- `assets/js/admin.js` — progressive status and bulk preset controls; the form works without it.
- `assets/css/admin.css` — isolated responsive admin design system.
- `uninstall.php` — deletion of current and pre-release options only.

Settings store IDs rather than sequence copies. The longest enabled sequence caps matcher memory. No route, REST endpoint, scheduled task, network request, database query, filesystem write, cookie, or personal-data processor exists.

## Trust boundaries

1. **Administrator → Settings API:** nonce and `manage_options`, followed by structural validation.
2. **Options table → PHP:** always untrusted; normalized on every read.
3. **PHP → browser:** minimal JSON encoded by WordPress, then shape-checked again.
4. **Keyboard → matcher:** ignored in typing/editing and modified-event contexts; buffer stays bounded.
5. **Match → navigation:** cancelable integration boundary, same-origin HTTP(S), one navigation, no referrer.

## Extension boundaries

Add built-in presets only in `Presets::all()` and add detector coverage. Custom user-authored sequences are intentionally out of scope for 0.1: they require bounded token capture, schema migration, accessibility work, collision handling, and dedicated threat analysis.

Observe `sesamo:matched`; do not fork the detector for animations. The pre-release alias is deprecated and will be removed only in a documented major release.

## Design system

The admin uses native WordPress controls and system typography. Sesamo identity comes from the emerald/ink/brass tokens, keycap sequence anatomy, and armed/off status—not a replacement admin shell. The accepted visual reference is [docs/design/admin-settings-concept.png](docs/design/admin-settings-concept.png).
