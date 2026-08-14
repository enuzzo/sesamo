# Sesamo

[![WordPress](https://img.shields.io/badge/WordPress-6.3%2B-21759B?style=for-the-badge&logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%E2%80%938.5-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![No tracking](https://img.shields.io/badge/Tracking-absolutely_not-16845B?style=for-the-badge)](#good-citizen-mode)
[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-D9A441?style=for-the-badge)](LICENSE)

**Unlock a hidden WordPress page with a secret key sequence. Konami Code, IDDQD, or your own combo. Easter eggs, done properly.**

Sesamo is a tiny WordPress plugin by **Netmilk Studio sagl**. Pick a legendary keyboard sequence or record your own, point each combination at a page on your site, and carry on pretending those pages do not exist. Visitors type the right incantation; Sesamo opens the right door.

*Why “Sesamo”?* Because “Open Sesame” has had excellent backwards compatibility since roughly the 10th century. Also because `↑ ↑ ↓ ↓ ← → ← → B A` is a terrible password and a magnificent doorbell.

![Sesamo icon](.wordpress-org/icon-256x256.png)

## What it does

- arms any combination of ten built-in classics or up to twenty custom sequences;
- routes every combination to its own same-origin WordPress destination;
- records custom keys accessibly or accepts explicit `KeyboardEvent.key` tokens;
- disables duplicate, prefix, and suffix collisions instead of guessing which door you meant;
- ignores inputs, textareas, selects, content-editable regions, repeated keys, IME composition, and modified shortcuts;
- resets a partial sequence after a configurable 250–5,000 ms pause;
- emits a cancelable `sesamo:matched` browser event before navigation;
- loads **zero frontend JavaScript** when no sequence is active;
- stores one schema-versioned, normalized option and deletes it on uninstall;
- makes no remote requests and brings no runtime dependencies to the party.

## The roster

| Sequence | Origin | Type this |
| --- | --- | --- |
| Konami Code | Gradius / Contra | `↑ ↑ ↓ ↓ ← → ← → B A` |
| IDDQD | DOOM | `IDDQD` |
| IDKFA | DOOM | `IDKFA` |
| XYZZY | Colossal Cave Adventure | `XYZZY` |
| JUSTIN BAILEY | Metroid | `JUSTINBAILEY` |
| ROSEBUD | The Sims | `ROSEBUD` |
| MOTHERLODE | The Sims 2 | `MOTHERLODE` |
| POWER OVERWHELMING | StarCraft | `POWEROVERWHELMING` |
| THERE IS NO COW LEVEL | StarCraft | `THEREISNOCOWLEVEL` |
| HESOYAM | GTA: San Andreas | `HESOYAM` |

Spaces in phrase-like codes are omitted. Sesamo is listening to keys, not writing a memoir.

## Good citizen mode

Sesamo is an **easter egg, not authentication**. The destination URL is shipped to the visitor’s browser and is therefore discoverable. If a page contains sensitive material, protect it with WordPress permissions, HTTP authentication, or another real access-control layer. Obscurity is delightful theatre; it is not a lock.

The plugin deliberately has no cookies, analytics, telemetry, external calls, database tables, remote preset feeds, or third-party frontend code. Destination URLs are restricted to the current site, normalized on save **and** read, and revalidated in the browser before navigation.

See [SECURITY.md](SECURITY.md) and the [security assessment](docs/SECURITY-ASSESSMENT.md) for the less whimsical version.

## Install

From a release ZIP:

1. open **Plugins → Add New → Upload Plugin**;
2. upload `sesamo.zip` and activate it;
3. open **Settings → Sesamo**;
4. choose or record combinations, assign their destinations, and set the timing;
5. save, then type like it is 1993.

For development, clone the repository as `wp-content/plugins/sesamo/`.

## Browser event

```js
window.addEventListener("sesamo:matched", (event) => {
  console.log(event.detail.combination.id, event.detail.destinationUrl);

  // event.preventDefault(); // keep the door closed
});
```

`event.detail.combination` identifies the matched built-in or custom route. The compatibility `event.detail.preset` projection and pre-release event name `konami-code-activator:matched` remain available through the 0.x line. Either event may cancel navigation.

## Under the tiny hood

```text
schema-versioned WordPress option
        ↓
built-in registry + bounded custom combinations
        ↓
per-route same-origin config + collision-safe bounded matcher
        ↓
cancelable event
        ↓
no-referrer navigation
```

There is no service, cron job, shortcode, REST route, cookie, or custom table. Sesamo has one job and has resisted the industry’s repeated attempts to give it Kubernetes.

## Development

Requirements: PHP 7.4+, Node.js 18+, and WordPress 6.3+ for integration testing.

```bash
npm test
./scripts/build-release.sh
unzip -l build/sesamo.zip
```

The release ZIP is allowlisted from runtime files, SHA-256 checksummed, and version-checked across the plugin header, runtime constant, `package.json`, WordPress stable tag, and changelog. Git tags use `vMAJOR.MINOR.PATCH`; the plugin metadata uses plain `MAJOR.MINOR.PATCH`.

Read [SPECIFICATION.md](SPECIFICATION.md), [ARCHITECTURE.md](ARCHITECTURE.md), [AGENTS.md](AGENTS.md), and [docs/GOTCHAS.md](docs/GOTCHAS.md) before changing public behaviour. Release mechanics live in [docs/RELEASING.md](docs/RELEASING.md).

## Status

`0.2.0` is the first feature-complete prerelease, not a victory parade. It has unit/smoke coverage and reproducible packaging; `1.0.0` remains reserved for acceptance-complete WordPress integration, accessibility, mobile-admin, and Plugin Check verification.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE). Copyright © 2026 Netmilk Studio sagl.
