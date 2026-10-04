# Accessibility — WCAG 2.1 AA

AA is the floor, and it is part of "done", not a follow-up ticket. The token
system already does most of the work; the usual failures here are markup ones.

## What app-core already gives you (§1.4)

`.sr-only` (visually hidden, screen-reader available) · `.skip-link` (first
focusable element, jumps to main) · `--focus-ring` / `--focus-outline` applied
via `:focus-visible` on every control · tone `-fg` values chosen to clear 4.5:1
on their own `-bg` · a `prefers-reduced-motion` block.

Use them. Do not write `outline: none` anywhere, ever, without an equally
visible replacement.

## Contrast

- Body text, labels, table cells: `--text-primary` / `--text-secondary` on
  `--card-bg` or `--page-bg`. Both pass AA in both themes.
- `--text-muted` is for supporting text at `--fs-sm` and above — never for a
  value the user has to read exactly, and never on a tinted surface.
- Status text always uses the tone's `-fg` on that tone's `-bg`.
  **`--accent` (#E8B86D) is not a text color on light surfaces** — it fails at
  ~1.7:1. Use `--tone-accent-fg` (#8A6318).
- Text on a gold fill is `--on-accent` (navy), never white.
- Non-text UI (borders of inputs, icon-only buttons, focus indicators) needs
  3:1 against its neighbour.
- Verify new pairs, in both themes, before shipping.

## Semantics

- One `<h1>` per page (`.page-header-title`); headings descend without skipping.
- `<table>` with a real `<thead>` and `<th>` — never divs pretending to be a
  grid. Add `scope="col"` on header cells.
- Landmarks: the layout provides `header`/`nav`/`main`; page content goes inside
  `main`, so don't add a second one.
- Buttons that act are `<button>`; things that navigate are `<a href>`. A
  `<div onclick>` is a bug.
- The clickable-row pattern is a convenience, not the accessible path: each row
  must also contain a real focusable link to the record (the `.cell-title` is
  the natural place). Keyboard users navigate by that link.

## Forms

- Every control has a `<label for>`; placeholder is never the label.
- Required fields: `required` attribute **and** a visible `*` (`.required`).
- Errors: `aria-invalid="true"` on the field, message in `.field-error` with an
  `id`, referenced by `aria-describedby`. Help text joins the same
  `aria-describedby` list.
- Group related radios/checkboxes in a `<fieldset>` with a `<legend>`.
- On submit failure, move focus to the first invalid field and summarise the
  count in an `aria-live="polite"` region.

## Modals

- `role="dialog" aria-modal="true"` plus `aria-labelledby` pointing at
  `.modal-header-title`.
- Focus moves in on open, is trapped while open, returns to the trigger on close.
- `Esc` closes. Background content is `inert` (or `aria-hidden`) while open.
- The mobile bottom sheet is the same dialog — it needs the same behaviour.

## Dynamic content

- Toasts, save confirmations, async filter results: `aria-live="polite"`.
  Errors: `aria-live="assertive"` or `role="alert"`.
- A loading button keeps its accessible name and adds `aria-busy="true"`.
- Never convey state by color alone — pair every badge/tone with text or an icon
  that has a label.

## Motion

Everything animated must survive `@media (prefers-reduced-motion: reduce)`:
view transitions off, parallax off, ripple off, transitions reduced to opacity
or nothing. No auto-playing looping animation longer than 5s.

## Quick audit pass

1. Tab through the whole page — is focus always visible, and in DOM order?
2. Can you reach and trigger every action without a mouse (row → record, row
   actions, filters, modal open/close, pagination)?
3. Zoom to 200% — does anything overlap or get cut off?
4. Toggle dark mode and re-check contrast on the pieces you just added.
5. Turn on reduced motion — does anything still fly?
6. Read the page with images/icons off — do labels still make sense?
