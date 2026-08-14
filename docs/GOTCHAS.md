# Gotchas

The short list future maintainers should read before making Sesamo “more flexible.”

## Product boundary

- A secret sequence hides discoverability in the UI; it does not protect content. The destination is visible in page source/devtools.
- Hidden pages may still appear in search, sitemaps, archives, feeds, logs, caches, analytics, referrers from later navigation, or WordPress REST responses.
- Sesamo deliberately requires a same-origin URL. Relaxing this turns an easter egg into an administrator-configured outbound redirect and needs a new threat review.
- Physical keyboards are the 0.1 input surface. Mobile/touch visitors need another path provided by the site.

## Keyboard behavior

- `KeyboardEvent.key` is layout-aware. Letter sequences follow what the browser reports, not physical scan codes.
- Forms and inherited content-editable regions are ignored so the plugin cannot navigate while someone is typing.
- Closed-shadow editors are detected through the browser’s focused-host retargeting, including standard-element hosts. Sites can also add `data-sesamo-ignore` to any interactive ancestor that must suppress detection.
- Browser APIs cannot distinguish a normal active `<body>` from a closed shadow editor hosted directly on `<body>`. Such applications must place `data-sesamo-ignore` on `<body>`; this is a documented platform limitation, not an authentication risk.
- Ctrl, Alt, Command, repeat, IME composition, and already-canceled events are ignored.
- Long phrase presets omit spaces. Adding a preset that depends on whitespace would conflict with normal page interaction.
- Active sequences cannot duplicate, prefix, or suffix one another. PHP disables the later custom route and browser normalization independently keeps the first safe route.
- The recorder reserves Escape to stop recording. Administrators can still enter the literal `Escape` token in the text field.
- Custom sequence text is whitespace-separated. Use the named token `Space` for the space bar.

## WordPress behavior

- The options table is not trusted. Do not replace `Settings::get()` normalization with raw `get_option()` data.
- `settings_fields()` provides the nonce, but capability checks remain mandatory at the page boundary.
- Text domain, folder, main file, WordPress.org slug, and translation catalog must remain `sesamo` if the slug is approved. WordPress.org slugs are immutable.
- WordPress.org directory artwork belongs in SVN `/assets`, represented here by `.wordpress-org/`; it must not enter the plugin ZIP.
- Updating CSS/JS without bumping the plugin version may leave stale caches because the version is the asset cache key.

## Caching and policy

- Full-page caches may retain inline configuration until purged after settings changes.
- Strict Content Security Policy can affect inline configuration. WordPress’s `wp_add_inline_script()` is the supported mechanism, but site policy still owns nonces/hashes.
- The destination can include a query or fragment but must keep scheme, host, and effective port equal to `home_url()`.
- A `home_url()` containing embedded userinfo fails closed: no detector is enqueued and no credentials enter public configuration.
- HTTP and HTTPS are different origins. A mixed-scheme destination is rejected even when the hostname matches.

## Release traps

- Update the PHP header, runtime constant, `package.json`, `readme.txt` stable tag, and `CHANGELOG.md` together.
- Git tags add `v`; WordPress versions and stable tags do not.
- Release archives keep the `v`: `sesamo-vMAJOR.MINOR.PATCH.zip` and `sesamo-vMAJOR.MINOR.PATCH.zip.sha256`.
- Build and inspect the exact ZIP that will be released. Never rebuild separately for GitHub and WordPress.org.
- Do not add runtime packages for a matcher that fits comfortably in one reviewed file.
- The compatibility event `konami-code-activator:matched` is deprecated but still cancelable. Removing it requires a documented major release.
