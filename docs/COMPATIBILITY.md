# Compatibility and acceptance matrix

## Declared floor

| Surface | Declared | CI or local evidence | Release gate |
| --- | --- | --- | --- |
| WordPress | 6.3+ | PHP API smoke only | Real WP 6.3 and current stable |
| Tested up to | 7.0 | Pending real admin/frontend run | WordPress 7.0.2 |
| PHP | 7.4+ | Syntax/smoke locally; CI matrix configured | 7.4, 8.3, 8.5 |
| Node | 18+ (tooling only) | Node unit tests | 18 and current LTS |
| Browsers | modern evergreen | Pure matcher tests | current Chrome, Firefox, Safari |

The `Tested up to: 7.0` line is a target for the bootstrap and must not be used for a public WordPress.org submission until the real-environment row is complete.

## UI gate before 1.0

- current WordPress admin at desktop width;
- 600 px acceptance viewport;
- 320 CSS px and 200% zoom;
- expanded and collapsed admin navigation;
- keyboard-only setup and save;
- VoiceOver or NVDA smoke test;
- high contrast, reduced motion, and RTL;
- validation recovery for invalid/cross-origin URL and timeout bounds;
- armed/off state and no-script behavior;
- page-cache purge behavior documented for the target host.

## Runtime gate before 1.0

- activation, migration, update, deactivation, uninstall;
- malformed/tampered option read on PHP 7.4 and 8.x;
- multisite single-site activation behavior;
- frontend enqueue present only when armed;
- same-origin enforcement with ports, HTTP/HTTPS, query, fragment, credentials, and protocol-relative inputs;
- typing, nested content-editable, open/closed shadow-DOM composed paths, standard focused shadow hosts, `data-sesamo-ignore` on body-hosted closed shells, IME, modifiers, repeat, cancellation, teardown, and one-navigation behavior;
- Plugin Check clean or every exception documented.
