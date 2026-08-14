=== Sesamo — Secret Key Sequences ===
Contributors: netmilkstudio
Tags: easter egg, konami code, secret page, keyboard sequence, redirect
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unlock a hidden WordPress page with the Konami Code, IDDQD, or your own key combination. Easter eggs, done properly.

== Description ==

Sesamo listens for built-in or custom keyboard combinations on public pages and routes each successful match to its own page on the same WordPress site.

Enable any of ten built-in presets or create up to twenty custom combinations. Each active combination has its own destination. Sesamo ignores typing fields, content-editable regions, repeated keys, IME composition, and modified shortcuts. Disable every combination and no frontend script is loaded.

**Important:** Sesamo is an easter egg, not authentication. The destination URL is visible to the browser and may be discovered. Sensitive content must enforce its own access control.

No cookies. No analytics. No remote requests. No custom database tables. No third-party runtime dependencies.

== Installation ==

1. Upload `sesamo.zip` from Plugins > Add New > Upload Plugin.
2. Activate Sesamo.
3. Open Settings > Sesamo.
4. Enable presets or record custom combinations, assign same-site destinations, and save.

== Frequently Asked Questions ==

= Can multiple sequences be active? =

Yes. Built-in presets and up to twenty custom combinations can be active, each with its own destination.

= How do I create my own combination? =

Use Add combination, give it a name, record 2–64 keys or enter whitespace-separated `KeyboardEvent.key` values, and choose a same-site destination. Conflicting prefixes, suffixes, and duplicates are saved disabled so routing stays deterministic.

= Can I use an external destination? =

No. Sesamo restricts navigation to the current WordPress origin. This keeps an easter-egg plugin from becoming an external redirect surface.

= Is the destination secret or protected? =

No. Visitors can inspect browser configuration or traffic and discover it. Apply real authorization to sensitive content.

= Can I disable detection without deactivating the plugin? =

Yes. Clear every sequence and save. Sesamo then loads nothing on public pages.

= Does it run while someone is completing a form? =

No. Input, textarea, select, content-editable, repeated, composing, and modified key events are ignored.

== Changelog ==

= 0.2.0 =

* Added up to twenty custom key combinations with accessible recording and text entry.
* Added one same-origin destination per built-in or custom combination.
* Added schema-2 migration, bounded token validation, stable custom IDs, and rollback documentation.
* Rejects duplicate, prefix, and suffix collisions so every active route is deterministic.

= 0.1.0 =

* Initial hardened Sesamo bootstrap by Netmilk Studio sagl.
* Ten selectable historical presets with a bounded dependency-free matcher.
* Same-origin destination enforcement on save, read, and navigation.
* Polished accessible settings interface with armed/off states.
* Reproducible version-checked release ZIP and SHA-256 checksum.
