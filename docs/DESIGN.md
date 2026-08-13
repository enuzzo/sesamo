# Admin design specification

The accepted concept is [`design/admin-settings-concept.png`](design/admin-settings-concept.png).

## Direction

Unmistakably Sesamo, unmistakably WordPress: native controls and system typography; true white surfaces; deep ink text; emerald armed state; brass only for the safety boundary; sequence anatomy expressed as wrapping keycaps. No custom dashboard shell, fake metrics, bento grid, external font, or animation required to understand state.

## Tokens

| Role | Value |
| --- | --- |
| Ink | `#101c2c` |
| Text | `#1d2327` |
| Muted | `#50575e` |
| Border | `#dcdcde` |
| Emerald | `#16845b` |
| Emerald dark | `#0d6645` |
| Emerald soft | `#edf8f3` |
| Brass | `#d9a441` |
| Focus | WordPress `#2271b1` |

The implementation keeps native checkbox semantics, field labels/help associations, explicit armed/off text, a field group legend, wrapping keycaps, logical border direction, reduced-motion behavior, and one-column mobile reflow.
