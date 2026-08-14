# Admin UI fidelity ledger

**Concept:** `admin-settings-custom-combinations-concept.png` (Image Gen, complete desktop settings surface)

**Implementation review:** local code-native QA harness using the production CSS/admin JS in the Codex in-app browser.

**Viewports:** 1536 × 1024, 600 px responsive metric check, and 320 × 900.

**Evidence:** `admin-settings-custom-combinations-render.png` and `admin-settings-custom-combinations-mobile-render.png`.

| Comparison point | Concept evidence | Render evidence | Result |
| --- | --- | --- | --- |
| Header hierarchy | Mark, Sesamo H1, terse descriptor, armed status rail | Same hierarchy and copy; status remains separate at narrow widths | Matched; removed concept-only disarm button because clearing presets is the actual workflow. |
| Built-in anatomy | Open table with active checkbox, name/origin, wrapping keycaps, and destination | Production rows preserve the four-column desktop hierarchy and checked tint | Matched; native checkbox retained for accessibility. |
| Custom workflow | One open editing rail with enable, name, keycaps, recorder, destination, and remove | Production rail adds an editable token field and one blank server-rendered draft | Matched; the text input and draft are intentional progressive-enhancement controls. |
| Palette and surfaces | White/cool-neutral canvas, deep ink, emerald state, small brass safety cue | Exact token family implemented without decorative gradients or glow | Matched. |
| Typography and density | Native/system type, compact but readable control chrome | 12 px minimum metadata, 13–15 px helper/body, 36 px route fields, 58 px built-in rows | Matched and slightly relaxed for native control usability. |
| Route workflow | One destination per built-in/custom route, bounded pause, safety warning, primary save | Same hierarchy with relative-path hints and stronger same-origin explanation | Matched with security copy intentionally strengthened. |
| Responsive behavior | Clean single-column continuation implied | 320 px screenshot has no horizontal overflow; rows reflow to checkbox + identity + wrapping keycaps; actions stack | Matched. |
| Progressive state | Armed/off state and recording must be obvious | Recorder captured `v a u l t`, Escape stopped it, Clear disabled the incomplete route, and Add focused the existing blank draft | Verified interactively. |
| Above-the-fold copy | Sesamo; “Secret sequence in. Hidden page out.”; detector state; sequence section | No unapproved marketing eyebrow, badge, fake metric, or extra navigation | Clean diff. |

## Intentional deviations

- The generated concept visually approximated the WordPress admin shell; the implementation keeps WordPress’s actual shell outside plugin ownership.
- The concept showed a “Disarm detector” button. The implementation uses the built-in `Clear` action plus per-custom enable controls, avoiding a header action that appears to save immediately but only mutates an unsaved form.
- The production surface is taller than the generated concept because native URL fields and the no-JavaScript custom draft preserve practical touch, zoom, and recovery behaviour.
- Safety copy explicitly says the URL is not access control and requires real authentication. Security clarity outranks verbatim concept copy.
- The browser harness is not accepted as WordPress integration evidence. Real current/minimum WordPress screenshots remain a `1.0.0` gate in `docs/COMPATIBILITY.md`.

No material fixable visual mismatch remained in the code-native surface after the desktop/mobile pass. The concept and latest desktop render were both inspected directly with `view_image`; the implementation was faithfully verified against the accepted visual system.
