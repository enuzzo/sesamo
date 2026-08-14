# Security policy

## Supported version

Security fixes are applied to the latest released minor of the current major. During `0.x`, only the newest release is supported.

## Report privately

Please do not publish suspected vulnerabilities in a public issue. Use GitHub’s **Security → Report a vulnerability** flow for this repository. Include the affected version, impact, reproduction steps, and any proposed fix. Netmilk Studio sagl will acknowledge a complete report within seven calendar days and coordinate disclosure after a fix is available.

## Boundaries

- Sesamo is an easter egg, never authentication or authorization.
- The destination URL is intentionally present in browser configuration and is discoverable.
- Sensitive destinations must enforce their own access control.
- Settings require `manage_options` and the WordPress Settings API nonce.
- Options are structurally normalized on write and read.
- Presets are allowlisted; custom combinations are data-only and bounded to twenty routes with 2–64 normalized keys; timeouts are bounded; every destination is same-origin HTTP(S).
- Duplicate, prefix, and suffix collisions are disabled so route selection is deterministic.
- The detector ignores typing/editing contexts and modified input.
- There is no telemetry, personal-data collection, remote request, runtime dependency, or custom table.

Reportable issues include capability or CSRF bypass, stored/reflected script injection, custom-token validation bypass, ambiguous route execution, cross-origin or unsafe URL handling, frontend execution while typing, unbounded resource use, privacy regressions, and dependency/release supply-chain compromise.

See [docs/SECURITY-ASSESSMENT.md](docs/SECURITY-ASSESSMENT.md) for the current threat model and residual risks.
