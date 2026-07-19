# Real Estate Project

## Core Rule
- **ALWAYS** read this `CLAUDE.md` file in full before performing any task in this project
- **ALWAYS verify state before reporting it** — check git log/CI status/live server state directly rather than assuming or restating what the user said back to them. Don't say "confirmed merged" or "that's fixed" without actually running the check first.

## Project Overview
Laravel 12 real estate application.

## Tech Stack
- **Framework**: Laravel 12 (PHP)
- **Database**: SQLite (default, can be changed)
- **Frontend**: Blade templates

## Common Commands
```bash
php artisan serve          # Start dev server
php artisan migrate        # Run migrations
php artisan make:model     # Create model
php artisan make:controller # Create controller
php artisan make:migration # Create migration
```

## Design Reference
- **Template**: Evohus — Symfony 8 Real-Estate Admin & Dashboard Template
- **Preview**: https://preview.themeforest.net/item/evohus-symfony-8-realestate-admin-dashboard-template/full_screen_preview/62580194
- All UI must follow Evohus's visual style: clean sidebar navigation, Bootstrap 5 components, dark/light mode support, dashboard cards, charts, tables, and modals
- The Evohus HTML/CSS/JS structure should be used as the base and adapted into Laravel Blade templates

## Frontend Design Rules
- **ALWAYS** use the `frontend-design` skill when creating or modifying any UI component, page, layout, or view — **no exceptions, including PDF templates, receipts, invoices, and other print/export documents**
- This applies to all Blade templates, partials, PDF views (DomPDF), and any frontend assets
- The skill must be invoked before writing any HTML/CSS to ensure production-grade, distinctive design quality
- Do not write generic or plain UI — every interface element must go through the `frontend-design` skill
- Invoking the skill is not enough on its own — actually follow its process (brainstorm → critique → build → critique again) and produce genuinely polished output (correct spacing/rhythm, considered typography, clean alignment), not just a quick pass that technically checks the box. If a deliverable still looks rough or unfinished, redo it properly rather than defending the first attempt.

## Validation & Filtering Rules
- **ALWAYS** implement validation on both frontend (immediate user feedback) and backend (Laravel Form Requests) for every CRUD operation
- Backend validation must use dedicated `FormRequest` classes — never validate inline in controllers
- **ALL filtering, searching, and sorting must be done server-side** — the backend must return already-filtered data, never send the full dataset to the frontend and filter there
- Filters must be passed as query parameters to the backend (e.g. `GET /customers?age=18&status=active`) and applied in the query before returning the response
- Use Laravel query scopes or dedicated filter classes to keep controller logic clean
- Frontend filter UI (dropdowns, inputs, date pickers) must trigger a new backend request — never manipulate an already-loaded dataset on the client side
- Pagination must always be applied server-side alongside filters

## Input Validation Rules
- **ALWAYS** validate every field at both the frontend and backend — no field may be saved without explicit validation rules
- Use the correct rule for each data type: `integer` for numeric IDs and counts, `decimal` for monetary and area values, `date` for date fields, `string` with `max:` for text fields, `in:` for enum-like fields with a fixed set of allowed values
- Required vs optional must be intentional: `property_name`, `property_code`, and `unit_name` are always required; all other fields must be explicitly marked `nullable` in both the migration and the Form Request
- Numeric fields must enforce realistic bounds (e.g. `min:0` for counts, areas, and prices — negative values are not allowed)
- String fields must have a `max:` cap matching the column length defined in the migration — never leave string length unconstrained
- Enum fields (e.g. `unit_type`, `unit_condition`, `type_of_ownership`) must use an `in:` rule with the exact allowed values — free-text entry into these fields is not permitted
- Date fields must use the `date` rule and, where applicable, `after_or_equal:` / `before_or_equal:` constraints (e.g. installation dates should not be in the future)
- Frontend validation must mirror backend rules: use HTML5 attributes (`required`, `min`, `max`, `maxlength`, `pattern`) and, where needed, JS validation to give immediate feedback before form submission
- Validation error messages must be specific and field-level — never show a generic "something went wrong" message
- Form Requests must return a `422` JSON response for API calls and redirect back with errors for Blade forms — do not swallow validation errors silently

## Git Branching Rules
- **ALWAYS** create a new git branch before starting any feature or task — never work directly on `main` or `development`
- Branch names must be descriptive and kebab-case, e.g. `feature/tenant-crud`, `feature/lease-contracts-import`, `fix/floor-migration`
- One branch per feature — do not mix unrelated changes on the same branch
- `development` is the integration branch: when a feature is complete and tested, it is merged via PR into `development` first, not `main`
- `main` is production — only merged into via a separate PR from `development`, once everything on `development` has been checked. Merging into `main` triggers the production deploy (see below), so nothing lands there directly from a feature branch
- A staging LXC container (192.168.0.50, cloned from production, hostname `Realstate-Stage`) deploys automatically from `development` via `.github/workflows/deploy-staging.yml`, mirroring `deploy.yml` but targeting its own self-hosted runner (`staging-runner`, label `staging`) so it never conflicts with production's runner job queue. As of 2026-07-19 it's reachable at `http://192.168.0.50` (no subdomain assigned yet) and still holds a copy of production's database (not yet reset to empty)

## Form & Template Configuration Rules
- **EVERY** CRUD module built must have a corresponding entry in the **Forms Management** tab at `/form-configs?tab=forms`
  - This lets the user control which fields are visible in the add/edit form for that module
  - Follow the exact same pattern as the existing Building Form and Unit Form config cards
- **EVERY** module that supports import/export must have a corresponding entry in the **Template Management** tab at `/form-configs?tab=templates`
  - This gives the user download buttons (XLSX + CSV) and an import modal for that module
  - Follow the exact same pattern as the existing Building Template, Unit Template, and Lease Contracts Template cards
- These entries must be added as part of the feature — never ship a CRUD module without its Form Config card

## Testing Rules
- **ALWAYS** write test cases for every feature, controller, model, and service created
- Tests must be written before marking any task as complete
- Run tests after writing them using `php artisan test` to verify they pass
- Cover both happy path and edge cases
- Use Laravel's built-in PHPUnit test suite (`tests/Feature` for HTTP/integration tests, `tests/Unit` for isolated logic)
- Fix any failing tests before moving on to the next task

## Index Page Row Click Rule
- **EVERY** index/listing page must make each table row clickable — clicking anywhere on a row navigates to that record's show/view page
- Implement this with a `data-href` attribute on the `<tr>` and a global JS handler (or inline `onclick`), styled with `cursor: pointer` on the row
- The dedicated action buttons (edit, delete) in the Actions column must still work independently — use `e.stopPropagation()` on those cells/buttons so they do not trigger the row click
- This applies to all CRUD modules: buildings, floors, units, tenants, lease contracts, maintenance requests, and any future modules

## Server Console / Remote Terminal Rules
- The Proxmox LXC container's console (used for deploy/server setup) hard-wraps long pasted lines into real newlines — this breaks heredocs (`<<'EOF'`, since the closing `EOF` gets indented/mangled) and any long single-line command (gets split mid-command)
- When writing multi-line file content to the server through this console, **always** use a sequence of short, single-line commands instead: `sudo touch <file>` followed by one `echo '<line>' | sudo tee -a <file>` per line
- Never use heredocs or long one-liner commands (e.g. long `printf '...\n...\n...'`) for this console — they have repeatedly failed and corrupted files/left the shell stuck
- Keep each individual command well under the console's wrap width so nothing gets split mid-command

## Production Server Setup (192.168.0.48)
- App is deployed at `/var/www/realstate` (not the GitHub Actions runner's own `_work/` checkout dir — that's a disposable build area only)
- Deploy user is `actionsrunner`; php-fpm runs as `www-data`, added to the `actionsrunner` group for shared read/write access
- Required passwordless sudo rules for `actionsrunner` (each its own file under `/etc/sudoers.d/`, mode `0440`):
  - `systemctl reload php8.3-fpm` — so Deploy can reload php-fpm without a password prompt
  - `chown -R actionsrunner:actionsrunner /var/www/realstate/storage /var/www/realstate/bootstrap/cache /var/www/realstate/database` — reclaims ownership of paths php-fpm (www-data) writes into between deploys (logs, sqlite sessions), since `chmod` on a file you don't own fails even with group-write access
- If a deploy ever fails at the permissions-fix step with `Operation not permitted`, it's this exact issue recurring — check the sudoers rule above is still in place before debugging further

## Staging Server Setup (192.168.0.50)
- Cloned from the production LXC on 2026-07-19 (hostname `Realstate-Stage`), then de-provisioned: re-registered GitHub Actions runner (`staging-runner`, label `staging`, was previously a duplicate of production's runner identity and conflicted with it), fresh `APP_KEY`, `APP_ENV=staging`, `APP_URL=http://192.168.0.50`, `APP_DEBUG=true`, nginx `server_name` corrected from the cloned `192.168.0.48` to `192.168.0.50`
- Same `/var/www/realstate` deploy path, same sudoers rules, same PHP 8.3 / Node 20 versions as production (all carried over from the clone and verified working)
- Still holds a copy of production's database as of 2026-07-19 (not yet reset to empty) — check with the user before assuming it's safe to treat as disposable test data
- No subdomain assigned yet — reachable only by IP until DNS/subdomain work happens (planned, not yet started)

## Notes
- App key is already generated
- Default database is SQLite at `database/database.sqlite`
