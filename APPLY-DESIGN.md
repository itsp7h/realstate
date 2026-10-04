# APPLY-DESIGN.md — paste this to your AI

Attach three things every time: this file, `DESKTOP-UI.md`, and the reference mock
`reference-mock.html` — self-contained, opens in any browser, every screen reachable.

If your AI can't read the mock, `DESKTOP-UI.md` is sufficient on its own: §4 carries the
literal shell sizes and the full navigation model, §3 the colour values, §2 the type scale.
The mock is the visual check, not the only source of truth. Say so in the prompt if you
can't attach it, and drop reference item 1.

---

## The prompt

> You are refactoring the desktop UI of my Laravel + Blade RealEstate Management Suite.
>
> **Reference material (read all three before writing any code):**
> 1. `Property Suite Desktop.dc.html` — the approved visual reference. Every colour, size,
>    radius, spacing value and component treatment you need is in its inline styles. Lift
>    values from it literally. It is the target; the current pages are the input.
> 2. `DESKTOP-UI.md` — the written rules: tokens, type scale, five page archetypes,
>    component rules, accessibility, and the things you must not do.
> 3. My existing `app-core.css` and the page's current Blade file.
>
> **Task:** refactor `<PAGE NAME>` to match the reference.
>
> **Rules:**
> - Pick exactly one archetype from `DESKTOP-UI.md` §5 and say which one you picked and why,
>   before you write code.
> - Reuse existing classes in `app-core.css` first. If a value in the reference has no token,
>   add the token to `app-core.css` `:root` **and** the `[data-theme="dark"]` block, then use
>   `var(--token)`. Never hardcode a hex in Blade.
> - Keep every existing route, controller call, form name, validation rule, `@can` check and
>   Blade variable exactly as it is. This is a presentation-layer change only. If a change
>   requires touching a controller or query, stop and tell me instead of doing it.
> - Do not touch the mobile `.pm-*` layer or anything inside `@media (max-width:768px)`.
> - Page-specific CSS goes in that page's `@push('styles')`, namespaced with a page prefix.
> - No new JS dependency. Chart.js only, for charts.
> - Ship the page's empty state, loading state and dark-theme pass in the same change.
>
> **Deliver:** the full updated Blade file, any `app-core.css` additions as a separate diff,
> and the ticked acceptance checklist from the end of `DESKTOP-UI.md`.
> If anything in the reference is ambiguous for this page, ask me before guessing.

---

## Page → archetype → reference region

Point the AI at the exact part of the mock to copy. Open the mock and navigate to the
listed screen to see it.

| Your page | Archetype | Look at, in the mock |
|---|---|---|
| Dashboard | A · Dashboard | **Dashboard** — KPI strip, chart card, navy collection card, "Needs you today", property grid |
| Buildings index | B/master-detail | **Buildings** — 362px list pane + identity card + ledger table |
| Building detail | C · Detail | **Buildings** right pane — identity card, tabs, ledger, Details + Activity |
| Units index | D · Bulk grid | **Units — bulk edit** — dirty bar, dotted editable cells, gold dirty rows |
| Floors | D · Bulk grid | **Units** grid, same treatment |
| Unit detail | C · Detail | **Buildings** right pane |
| Tenants | B/master-detail | **Tenants** |
| Contracts, Renewals | B/master-detail | **Lease contracts** |
| Invoices | B/master-detail | **Invoices** — status pills, right-aligned amounts |
| Payments, Expenses | B/master-detail | **Payments** / **Expenses** |
| EWA meters | D · Bulk grid | **Units** grid |
| Maintenance | B/master-detail | **Maintenance requests** — priority + stage pills |
| Reports | E · Library | **Report library** — 3-up cards, PDF / Excel / Run |
| Revenue analysis | A · Dashboard | **Dashboard** chart card only |
| Smart import | E · Wizard | **Smart import** — step rail, dropzone, column mapping panel |
| Users, Audit log | B · Index | **Users & roles** — avatar + role + status table |
| Create / edit forms | E · Form | single column, max-width 680px, inside a card |

---

## Shell first, pages second

Before any page work, land the chrome once in the layout file — the mock's top three bars,
rail, sidebar and palette. Ask for it as its own change:

> Refactor `resources/views/layouts/admin.blade.php` to the reference shell, **appearance
> only**. Build it from the size table and the rail-groups table in `DESKTOP-UI.md` §4 —
> those numbers are final and override the current CSS wherever they disagree. Deliver: the
> 29px menu bar row with the sync chip in its synced state, the 54px navy icon rail, the
> 212px contextual sidebar driven by the active rail group, the 44px toolbar, and the 54px
> page header. Include the command-palette partial with ⌘K to open and Esc to dismiss.
>
> Out of scope this change: the offline mutation queue, and any shortcut that needs real
> record state. Render the sync chip static and leave the rest of the shortcut layer for
> later. Commit or stash the uncommitted work first — this rewrites the layout.

Then work the order in `DESKTOP-UI.md` §10.

---

## Two things to check on every page you get back

1. Toggle dark mode. If anything disappears or goes muddy, the AI hardcoded a colour.
2. Resize to 760px. If the desktop layout is still showing, it broke the mobile boundary.
