# ADR 0002: Custom combinations and route-owned destinations

**Status:** accepted
**Date:** 2026-08-14

## Decision

Schema 2 gives every built-in or custom combination its own same-origin destination. Custom combinations are stored in the existing option as data-only records containing a stable bounded ID, enabled flag, sanitized name, normalized `KeyboardEvent.key` token array, and destination URL.

The plugin accepts at most twenty custom combinations and 2–64 keys per custom sequence. Printable single-code-point keys and a fixed named-key allowlist are supported. Arbitrary callbacks, JavaScript, remote feeds, modifier-only keys, and cross-origin destinations remain prohibited.

Active combinations may not duplicate, prefix, or suffix one another. Built-ins have precedence; a later conflicting custom combination remains stored but is disabled with an administrator warning. The browser independently caps and deconflicts configuration before matching.

## Migration

Schema-1 and prototype options are normalized by copying the former shared destination to each enabled preset. Migration runs on activation and on the next administrator request after an upgrade. Reads remain safe before persistence because normalization always understands both shapes.

The schema retains `enabled_presets` and one `destination_url` rollback bridge through the 0.x line. A 0.1 downgrade ignores custom routes and collapses preset routes to that bridge, so an exact rollback requires restoring the pre-upgrade option backup.

## Consequences

The frontend event gains `detail.combination`; `detail.preset` and the deprecated prototype event remain compatibility projections. Matcher memory and browser configuration stay bounded. Increasing route or key caps, allowing external URLs, or attaching executable behavior requires a new threat review and ADR.
