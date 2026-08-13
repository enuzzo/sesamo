# Security assessment

**Scope:** Sesamo 0.1.0 bootstrap  
**Date:** 2026-08-13  
**Method:** source review, threat mapping, focused unit/smoke tests, and release-workflow inspection.

## Summary

Sesamo has a deliberately small security surface: one capability-protected Settings API option, one conditional frontend script, and one same-origin navigation. It processes no personal data and has no network, database-query, upload, REST, filesystem, or deserialization surface.

The defining invariant is simple: **Sesamo reveals an easter egg; it does not protect a resource.** That boundary appears in the admin UI, repository README, WordPress readme, specification, and security policy.

## Threat model

| Actor/input | Asset at risk | Boundary/control |
| --- | --- | --- |
| Unprivileged visitor keyboard input | page state and visitor work | ignore typing/editing/modifier contexts; bounded matcher; one navigation |
| Administrator form input | stored settings and visitors | `manage_options`, Settings API nonce, structural normalization |
| Tampered options table / plugin filters | frontend config and PHP availability | normalize every read; allowlist IDs; bounded integer; same-origin URL |
| Browser/runtime mutation | navigation target | shape caps and independent same-origin HTTP(S) validation |
| CI dependency compromise | repository and release integrity | immutable Action SHAs, least-privilege token, allowlisted build |
| Misunderstanding by site owner | confidential content | prominent “not access control” warnings and docs |

## Controls verified in source

- capability check and WordPress Settings API submission;
- context-appropriate escaping and `wp_json_encode()` output;
- option normalization on save and read, including malformed nested values;
- preset registry allowlist and bounded timeout;
- same-origin scheme/host/effective-port comparison, credential rejection, and browser revalidation;
- conditional frontend enqueue when enabled presets are empty;
- ignored typing/content-editable/default-prevented/repeat/composition/modifier events;
- cancelable primary and migration events; `noreferrer`, `noopener`, and `no-referrer` navigation;
- no external runtime dependencies, remote requests, cookies, telemetry, tables, or personal data;
- explicit uninstall scope;
- immutable CI Action commits and `contents: read` default permissions.

## Residual risks and decisions

| Risk | Disposition |
| --- | --- |
| Destination discovery | Accepted by design; never position as confidentiality. |
| Search/sitemap/cache discovery | Site-owner concern; documented in gotchas. |
| Accidental match outside known editors | Low; covered by context guards, with shadow-DOM composed-path testing still a 1.0 gate. |
| Closed shadow editor hosted directly on `<body>` | Browser retargeting is indistinguishable from normal body input. Integrations must set `data-sesamo-ignore` on `<body>`; residual impact is same-origin navigation/unsaved state, not privilege gain. |
| Administrator chooses a sensitive page | Warning cannot enforce content policy; real authorization remains mandatory. |
| Compromised WordPress administrator/database | Outside Sesamo’s privilege boundary; read validation limits accidental corruption and unsafe navigation. |
| PHP 7.4 is end-of-life | Compatibility floor, not hosting recommendation; PHP 8.3 is the primary target. |
| WordPress.org slug not yet approved | Use `sesamo` only if granted; otherwise rename folder/text domain together before submission. |

## Findings addressed during bootstrap

1. **Mutable CI Action tags (medium):** pinned to immutable commits; workflow permissions narrowed.
2. **Ambiguous access-control messaging (low):** warnings added to every distributed/admin surface.
3. **Untrusted option data normalized only on save (defense in depth):** all reads now normalize structure, IDs, URL, and timeout.
4. **Arbitrary external HTTP(S) destination (product risk):** reduced to same-origin destinations in PHP and JavaScript.
5. **Closed-shadow editing hosts (low):** focused-host retargeting and explicit ignore ancestors now suppress matching without disabling inert custom wrappers.
6. **Credential-bearing canonical home URL (low):** defaults fail closed and browser validation independently rejects URL userinfo.

## Remaining verification

This assessment does not claim dynamic WordPress penetration testing. Real WordPress integration, Plugin Check, assistive-technology testing, and cross-browser/shadow-DOM cases remain explicit blockers for `1.0.0`; see `COMPATIBILITY.md`.
