# UI Engineering Skill System

Seven skills under `.claude/skills/` that operate as one pipeline for keeping this
Laravel + Blade admin looking and behaving like one professional SaaS product,
instead of 40 pages that each drifted independently. This file is the map;
each `SKILL.md` is the detailed rulebook for its stage.

## Why this exists on top of what's already here

This app already went through a real design-system refactor on the current
branch. Before touching anything, read these — they are the master pattern,
not background reading:

- `CLAUDE.md` — project-wide rules (never touch the database or production
  without asking, git branching, testing, Form Config obligations, row-click
  rule).
- `public/css/app-core.css` — the actual implementation. Tokens, cascade
  contract, every shared component, with its markup contract documented
  inline.
- `public/css/app-mobile.css` — the mobile app layer.
- `DESKTOP-UI.md` — the written design spec (type, colour, five page
  archetypes, component rules, accessibility, a "do not" list).
- `DASHBOARD-SPEC.md` — supersedes `DESKTOP-UI.md` §4/§5A for the shell and
  dashboard.
- `APPLY-DESIGN.md` — the page→archetype mapping and the prompt pattern the
  refactor itself was driven by.
- `HANDOFF.md` — current state: 574 tests passing, three guard-rail test
  suites, a Playwright QA harness (`qa-harness/`), a live punch list, and
  measured before/after metrics.
- `.claude/skills/frontend-expert/` — an existing Claude Code skill with the
  same authority, plus deeper reference docs (`references/*.md`) for tokens,
  components, Blade patterns, responsive/mobile, accessibility, and Playwright
  testing. `.claude/skills/` doesn't replace it — `ui-design-system` treats it as
  a lookup catalog and the others cite it for detail.

**`app-core.css` is always the tiebreaker.** The spec docs (`DESKTOP-UI.md`,
`DASHBOARD-SPEC.md`) were written against a reference mock and use some
class names (`.dash-card`, `.data-hero`) that were never literally carried
into the code — the shipped names are `.table-card`, `.stats-grid`
`.stat-card`, `.filter-bar`, etc. Use the specs for the visual language, the
CSS file for the literal selector.

## The seven skills

| Skill | Runs when | Produces |
|---|---|---|
| `ui-design-system` | Referenced constantly, by every other skill | The definition of "correct" — tokens, archetypes, component rules |
| `ui-audit` | Before any page is touched | A written findings list, categorized, with file:line |
| `responsive-design` | Whenever layout must work at mobile/tablet/desktop | Verified-in-browser fixes at 320/375/390/430/768/1024/1440 |
| `component-standardization` | Whenever a pattern repeats across pages | One shared component instead of N duplicates |
| `laravel-blade-ui` | On every single edit, as a constraint | Confirmation that a change is presentation-only |
| `ui-refactoring` | After an audit identifies mess | Deleted duplication, no `!important`, no inline styles |
| `visual-qa` | After implementation, before calling it done | A report: what was checked, what's fixed, what remains |

`laravel-blade-ui` isn't a pipeline stage — it's a constraint every other
skill operates under on every edit. The rest run roughly in this order.

## The 12-step workflow

1. **Inspect the project.** Read the ground-truth docs above and the routes/
   views actually in `resources/views/`. Don't start from assumption.
2. **Inspect the existing design system.** Confirm what `app-core.css`
   actually implements — grep it, don't trust a spec doc's class names.
3. **Find the master reference.** Identify the strongest existing page/
   component of each archetype (`ui-design-system`) — the one every sibling
   page should look like.
4. **Run a UI audit** (`ui-audit`) — findings list before any edit.
5. **Confirm/extend the shared design system** (`ui-design-system`) — only
   add a token or component if the audit shows 2+ pages need it.
6. **Refactor duplicated UI/CSS** (`ui-refactoring`) — clean, don't rewrite.
7. **Standardize shared components** (`component-standardization`).
8. **Apply the standard across the application** — incrementally, one page
   or module at a time, each edit constrained by `laravel-blade-ui`.
9. **Fix responsive/mobile layouts** (`responsive-design`).
10. **Run visual QA** (`visual-qa`) — in an actual browser, both themes, all
    widths.
11. **Fix remaining inconsistencies** found in step 10, then re-run the
    relevant earlier step for whatever changed.
12. **Provide a final report** — pages inspected, issues found vs. fixed vs.
    left as documented exceptions, test results, before/after metrics.

## Non-negotiables across all seven skills

- Never rewrite the application. Never convert Blade to React/Vue/any SPA.
- Never change backend/business logic, database structure, or routes as a
  side effect of a UI change. If a UI fix seems to require one, stop and ask.
- Never remove functionality while "cleaning up" UI.
- Never invent a page-specific design when an existing pattern already
  covers the need.
- Never add a dependency to solve a styling problem.
- Don't blindly mass-edit. Understand the architecture and the audit
  findings first; change incrementally; run `php artisan test` after each
  meaningful step; don't stop after fixing one page — the goal is
  application-wide consistency.

## A note on concurrency

`HANDOFF.md` §7 records that this repo has previously had two sessions
editing UI at the same time and silently overwriting each other's work.
Before starting a multi-file pass: check `git status` and
`find resources public/css tests -newermt '-30 minutes'`. If something is
actively changing, coordinate with the user before proceeding, or work in an
isolated worktree.
