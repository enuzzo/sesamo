# Admin UI fidelity ledger

**Concept:** `admin-settings-concept.png` (Image Gen, 1536 × 1024)  
**Implementation review:** local code-native QA harness using the production CSS/admin JS in the Codex in-app browser.  
**Viewports:** 1536 × 1024, 600 px responsive metric check, and 320 × 900.

| Comparison point | Concept evidence | Render evidence | Result |
| --- | --- | --- | --- |
| Header hierarchy | Mark, Sesamo H1, terse descriptor, armed status rail | Same hierarchy and copy; status remains separate at narrow widths | Matched; removed concept-only disarm button because clearing presets is the actual workflow. |
| Sequence anatomy | Open table with active checkbox, name/origin, wrapping keycaps | Production rows use the same three-column anatomy and explicit checked tint | Matched; native checkbox retained for accessibility. |
| Palette and surfaces | White/cool-neutral canvas, deep ink, emerald state, small brass safety cue | Exact token family implemented without decorative gradients or glow | Matched. |
| Typography and density | Native/system type, compact but readable control chrome | 12 px minimum metadata, 13–15 px helper/body, 40 px fields, 58 px rows | Matched and slightly relaxed for accessibility. |
| Destination workflow | Full URL field, bounded pause field, safety warning, primary save | Same order and labels with stronger same-origin explanation | Matched with security copy intentionally strengthened. |
| Responsive behavior | Clean single-column continuation implied | 320 px screenshot has no horizontal overflow; rows reflow to checkbox + identity + wrapping keycaps; actions stack | Matched. |
| Progressive state | Armed/off state must be obvious | Clear → 0 checked, “Detection off,” no-script note; Select all → active count and armed state | Verified interactively. |
| Above-the-fold copy | Sesamo; “Secret sequence in. Hidden page out.”; detector state; sequence section | No unapproved marketing eyebrow, badge, fake metric, or extra navigation | Clean diff. |

## Intentional deviations

- The generated concept visually approximated the WordPress admin shell; the implementation keeps WordPress’s actual shell outside plugin ownership.
- The concept showed a “Disarm detector” button. The implementation uses the clearer `Clear` bulk action and immediate off-state explanation, avoiding two competing ways to change the same data.
- Safety copy explicitly says the URL is not access control and requires real authentication. Security clarity outranks verbatim concept copy.
- The browser harness is not accepted as WordPress integration evidence. Real current/minimum WordPress screenshots remain a `1.0.0` gate in `docs/COMPATIBILITY.md`.

No material fixable visual mismatch remained in the code-native surface after the desktop/mobile pass.
