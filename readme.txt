=== Sesamo — Secret Key Sequences ===
Tags: easter egg, konami code, secret page, keyboard sequence, redirect
Requires at least: 6.3
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Teach WordPress a secret knock: Konami Code, IDDQD, or your own combo opens a hidden page. Zero tracking. Maximum mischief.

== Description ==

Give WordPress a secret knock.

Sesamo is a tiny easter-egg engine for WordPress. Arm the Konami Code, IDDQD, or any of ten built-in classics—or record your own combination—and send each successful match to its own page on the same site.

Menus are civilised. Sometimes a door should open only after someone types the right incantation.

**What Sesamo does:**

* enables any mix of ten legendary cheat codes and up to twenty custom combinations;
* gives every combination its own same-site destination;
* includes an accessible recorder plus editable KeyboardEvent.key tokens;
* disables duplicate, prefix, and suffix collisions instead of guessing;
* ignores forms, editors, repeated keys, IME composition, and modified shortcuts;
* loads zero frontend JavaScript when nothing is armed.

**Important:** Sesamo is an easter egg, not authentication. The destination URL is visible to the browser and may be discovered. Sensitive content must enforce its own access control.

No account. No cookies. No analytics. No remote requests. No custom database tables. No third-party runtime dependencies. No tiny SaaS empire hiding inside your keyboard shortcut.

== Installation ==

1. Upload the downloaded `sesamo-vX.Y.Z.zip` from Plugins > Add New > Upload Plugin.
2. Activate Sesamo.
3. Open Settings > Sesamo.
4. Enable presets or record custom combinations, assign same-site destinations, and save.
5. Type like it is 1993.

== Frequently Asked Questions ==

= Can multiple sequences be active? =

Yes. Built-in presets and up to twenty custom combinations can be active, each with its own destination.

= How do I create my own combination? =

Use Add combination, give it a name, record 2–64 keys or enter whitespace-separated `KeyboardEvent.key` values, and choose a same-site destination. Conflicting prefixes, suffixes, and duplicates are saved disabled so routing stays deterministic.

= Can I use an external destination? =

No. The door stays in this building. Sesamo restricts navigation to the current WordPress origin so a playful plugin does not become an external redirect surface.

= Is the destination secret or protected? =

No. Hidden is not protected. Visitors can inspect browser configuration or traffic and discover it. Apply real authorization to sensitive content.

= Can I disable detection without deactivating the plugin? =

Yes. Clear every sequence and save. Sesamo then loads nothing on public pages—not even a dramatic farewell.

= Does it run while someone is completing a form? =

No. Sesamo has manners. Input, textarea, select, content-editable, repeated, composing, and modified key events are ignored.

== Screenshots ==

1. The desktop settings page: built-in cheat codes, per-route destinations, and detector state without dashboard cosplay.
2. The responsive settings page on a narrow screen. Same controls, less elbow room, zero horizontal scroll.

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
