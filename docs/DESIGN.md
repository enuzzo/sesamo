# Admin design specification

The current accepted concept is [`design/admin-settings-custom-combinations-concept.png`](design/admin-settings-custom-combinations-concept.png). The original bootstrap reference remains at [`design/admin-settings-concept.png`](design/admin-settings-concept.png).

## Direction

Unmistakably Sesamo, unmistakably WordPress: native controls and system typography; true white surfaces; deep ink text; emerald armed state; brass only for the safety boundary; sequence anatomy expressed as wrapping keycaps. Built-ins stay table-like; custom combinations use an open editing rail with no nested card grid. No custom dashboard shell, fake metrics, bento grid, external font, or animation is required to understand state.

The brand mascot is the kawaii sesame-seed wizard in [`design/sesamo-mascot-icon-master.png`](design/sesamo-mascot-icon-master.png): emerald hood, four illuminated `↑ ↓ B A` keycaps, and a half-open storybook gate. The mascot remains the focal point at 64 px; the gate is context, not the logo wearing a door costume.

The settings header shows the installed plugin version beside the Sesamo title and versions the mascot URL with the same runtime constant, preventing stale artwork after plugin updates.

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

The implementation keeps native checkbox semantics, field labels/help associations, explicit armed/off text, field group legends, wrapping keycaps, logical border direction, reduced-motion behavior, and one-column mobile reflow. Recording is an enhancement over a real text input: Escape stops recording, Clear is explicit, preview updates are announced, and saved custom rows remain editable without JavaScript.
