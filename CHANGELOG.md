# Changelog

All notable changes follow [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and Semantic Versioning.

## Unreleased

### Planned

- WordPress integration, accessibility, RTL, and mobile-admin verification.
- Official Plugin Check and WordPress.org readme validation.

## 0.1.0 — 2026-08-13

### Added

- Ten historical sequence presets and a bounded dependency-free matcher.
- Same-origin redirect destination and configurable 250–5,000 ms timeout.
- Cancelable `sesamo:matched` event plus a documented pre-release compatibility alias.
- Accessible, responsive WordPress settings UI and generated Sesamo artwork.
- Security assessment, gotchas, compatibility, architecture, and release documentation.
- Version synchronization checks, CI matrix, release workflow, ZIP manifest allowlist, and SHA-256 artifact.

### Security

- Normalize untrusted option data on both read and save.
- Allowlist preset IDs and reject malformed nested option values.
- Enforce same-origin HTTP(S) destinations in PHP and JavaScript.
- Ignore typing, content-editable, repeated, composing, default-prevented, and modified key events.
- Fail closed for closed-shadow custom-element hosts and credential-bearing canonical home URLs.
- Pin GitHub Actions to immutable commits and use least-privilege workflow permissions.
