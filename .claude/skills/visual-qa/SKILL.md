---
name: visual-qa
description: Final visual and responsive audit after implementation — verifies the shipped result against the master design in an actual browser, not just by reading code.
---

# Visual QA

You run last. `ui-audit` looks *before* a change to decide what needs fixing;
this skill looks *after* a change (or a batch of changes) to confirm it
actually landed correctly, in a real browser, in both themes, at every
supported width — against the master pattern `ui-design-system` defines.
Reading the diff is not QA. Opening the page is.

## Do not claim QA you didn't do

This is the one rule everything else here supports: **do not report
"responsive QA passed" or "verified in dark mode" unless you actually opened
the rendered page and looked.** Static analysis (grep, the guard-rail tests)
proves the code follows the rules; it does not prove the page looks right.
Both are required, and they are not substitutes for each other.

## Harness

The committed harness lives at `qa-harness/` and runs against a **copy** of
the SQLite database — it must never touch the real project database or
production/staging (CLAUDE.md: never touch the database without asking, in
the moment, every time).

```bash
cd /var/www/realstate
SP=/tmp/qa && mkdir -p $SP/shots

# 1. copy the DB and add a QA user TO THE COPY ONLY — never the real database
cp database/database.sqlite $SP/qa.sqlite
DB_DATABASE=$SP/qa.sqlite php artisan tinker --execute='
  $u = App\Models\User::firstOrNew(["email" => "qa-visual@example.com"]);
  $u->name="QA Visual"; $u->role="admin"; $u->password=bcrypt("qa-visual-pass"); $u->save();'

# 2. serve the copy — php artisan serve does NOT forward DB_DATABASE to its
#    child process, so use the built-in server with the router directly
cp qa-harness/router.php $SP/router.php
DB_DATABASE=$SP/qa.sqlite APP_ENV=local APP_DEBUG=true APP_URL=http://127.0.0.1:8199 \
  php -S 127.0.0.1:8199 -t /var/www/realstate/public $SP/router.php &

# 3. run the sweeps
cp qa-harness/*.mjs $SP/
sed -i "s#from 'playwright'#from '/var/www/realstate/node_modules/playwright/index.mjs'#" $SP/*.mjs
SP=$SP SHOTS=375,1440 THEME=light node $SP/responsive-qa.mjs   # → findings-light.json (overflow)
SP=$SP THEME=dark  node $SP/contrast-qa.mjs                    # → contrast-dark.json
SP=$SP URLS=/dashboard,/invoices W=375 FULL=1 OUT=$SP/shots THEME=dark node $SP/screenshot.mjs
```

Widths swept: `320, 375, 390, 430, 768, 1024, 1440`. Two known harness traps:
composite semi-transparent backgrounds over the real parent element, not
white; and skip elements whose ancestor is painted with a gradient
(`backgroundColor` reports `transparent` there and produces a false finding).

## Checklist per page (from `DESKTOP-UI.md`'s Acceptance checklist)

- [ ] Exactly one archetype from `ui-design-system`'s five, used correctly
- [ ] Zero raw hex outside the semantic-hex table
- [ ] Verified in **both** light and dark theme, in the browser
- [ ] Mobile layer (`.pm-*`/`.m-*` or the generic fallback) untouched and
      still correct ≤768px
- [ ] Page header + exactly one primary action; gold/accent used once
- [ ] Empty, loading, and error states all present and look intentional
- [ ] Keyboard: tab order sane, focus always visible, no `outline:none`
      without a replacement
- [ ] Icon-only buttons have `title` + `aria-label`
- [ ] Numbers: Outfit 700, right-aligned, `BHD`-prefixed, zero renders as
      `BHD 0` — never blank or a dash
- [ ] No new CSS outside that page's namespaced `@push('styles')`
- [ ] No new dependency

## Workflow

1. Confirm no other session is mid-edit before running the harness or
   drawing conclusions: `git status`, and
   `find resources public/css tests -newermt '-30 minutes'`. If something's
   actively changing underneath you, the QA result is stale before you
   finish it.
2. Run `php artisan test`, full suite — not just the UI guard-rails. Note the
   pass count.
3. Run the responsive sweep and the contrast sweep from the harness above, at
   both light and dark theme.
4. Open the actual screenshots the harness produces. Look for "unfinished,"
   not just "measurably wrong" — cramped spacing, misaligned columns, a badge
   whose tone doesn't match its meaning, a KPI row landing at a different y
   than its siblings.
5. Compare the page against its nearest sibling of the same archetype (e.g.
   every Index page's filter bar + table should read as the same product).
   A page that's individually "fine" but visibly different from its peers is
   still a finding.
6. Write the report. It must include: pages inspected, widths tested, themes
   checked, issues found vs. fixed vs. remaining (with a reason for anything
   left as a documented exception), test count, and the before/after metrics
   table (page-local CSS lines, raw hex count, hardcoded font-size count,
   distinct breakpoints, third-party-framework pages, contrast failures,
   overflow findings) — the same shape `HANDOFF.md` already tracks.

## Handoff

Anything this skill finds goes back to `ui-audit`'s findings format and
through `ui-refactoring`/`component-standardization`/`responsive-design`
again — visual QA does not fix, it verifies and reports, the same discipline
`ui-audit` holds at the front of the pipeline.
