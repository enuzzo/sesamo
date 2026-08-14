# Instructions for future Codex work

Read `SPECIFICATION.md`, `ARCHITECTURE.md`, `SECURITY.md`, `docs/GOTCHAS.md`, and the nearest task-specific document before modifying code.

## Project invariants

- Keep Sesamo generic; do not add client-specific behavior.
- Treat the feature as an easter egg, never authentication or authorization.
- Keep destinations same-origin HTTP(S) unless a new threat review explicitly changes the product boundary.
- Preserve the option migration and public browser-event alias unless a documented major migration removes them.
- Define built-in presets only in `Presets::all()`.
- Normalize options on every read and save; the database is untrusted.
- Never enqueue the frontend detector when no complete built-in or custom combination is enabled.
- Do not add third-party runtime dependencies, telemetry, cookies, tracking, remote code, or external requests.
- Keep UI copy, comments, tests, and public documentation in English.
- Brand all plugin metadata as `Netmilk Studio sagl`.
- Preserve PHP 7.4 syntax until the compatibility floor changes in a documented major release.

## Required verification

Run before handoff:

```bash
npm test
./scripts/build-release.sh
sesamo_version="$(node -p "require('./package.json').version")"
unzip -t "build/sesamo-v${sesamo_version}.zip"
(cd build && shasum -a 256 -c "sesamo-v${sesamo_version}.zip.sha256")
```

Settings changes require current WordPress desktop, 600 px, and 320 px/200% zoom checks. Detector changes require Node tests. Option changes require migration and rollback documentation. Custom-combination changes require count, token, collision, destination, and malformed-data tests. Release candidates also require Plugin Check and the matrix in `docs/COMPATIBILITY.md`.

## Release discipline

- Synchronize plugin header, runtime constant, `package.json`, `readme.txt`, and `CHANGELOG.md`.
- Build from a clean worktree, inspect the exact ZIP manifest, and publish the checksum.
- Name release archives `sesamo-vMAJOR.MINOR.PATCH.zip`; never publish an anonymous `sesamo.zip`.
- Tag `vMAJOR.MINOR.PATCH`; never rewrite a published tag.
- Attach the tested ZIP and checksum to the GitHub release and use the identical tree for WordPress.org SVN.
- If `gh auth status` reports invalid credentials inside the sandbox while the user expects an authenticated session, rerun the check with escalated permissions before treating authentication as unavailable.
