# Reusable CI/CD Setup Prompt

A portable version of this repo's pipeline, written so it can be pasted into a
fresh project. Fill in the `<<< >>>` placeholders before using it.

---

## Part 1 — Facts to fill in first

Answer these before pasting the prompt; the agent can't guess them and wrong
guesses here are what cause broken deploys.

| Placeholder | What it means | This repo's value (example) |
|---|---|---|
| `<<<APP_STACK>>>` | Framework + language version actually running in prod | Laravel 12 / PHP 8.3 |
| `<<<TEST_CMD>>>` | Full test command | `php artisan test` |
| `<<<BUILD_CMD>>>` | Frontend build command | `npm run build` |
| `<<<PROD_HOST>>>` | Production server IP/hostname | 192.168.0.48 |
| `<<<STAGING_HOST>>>` | Staging server IP/hostname | 192.168.0.50 |
| `<<<DEPLOY_PATH>>>` | Where the web server serves from | `/var/www/realstate` |
| `<<<DEPLOY_USER>>>` | User the CI runner acts as | `actionsrunner` |
| `<<<WEB_USER>>>` | User the web/app process runs as | `www-data` |
| `<<<RELOAD_CMD>>>` | Command to reload the app process | `systemctl reload php8.3-fpm` |
| `<<<PERSISTENT_PATHS>>>` | Paths that must survive a deploy | `.env`, `database/database.sqlite`, `storage/` |
| `<<<NETWORK>>>` | Are servers reachable from GitHub's cloud runners? | No — private LAN, needs self-hosted runners |

---

## Part 2 — The prompt

> Set up a complete CI/CD pipeline for this project using GitHub Actions.
>
> **Stack:** `<<<APP_STACK>>>`. Tests run with `<<<TEST_CMD>>>`. Frontend builds
> with `<<<BUILD_CMD>>>`.
>
> **Infrastructure:** Production is `<<<PROD_HOST>>>`, staging is
> `<<<STAGING_HOST>>>`, both deploying to `<<<DEPLOY_PATH>>>`. The CI runner acts
> as `<<<DEPLOY_USER>>>`; the web process runs as `<<<WEB_USER>>>`. Reload the app
> with `<<<RELOAD_CMD>>>`. Network reachability: `<<<NETWORK>>>`.
>
> ### Branching model
>
> Three tiers: `feature/*` → `development` → `main`.
> - `development` is the integration branch; all feature work merges there first.
> - `main` is production; merging into it triggers the production deploy.
> - PRs into `main` must come from `development`, except `chore/*` and `fix/*`
>   (infra changes and hotfixes may go straight to main).
>
> ### Build four workflows
>
> **1. `ci.yml` — tests**
> - Triggers: push to `'**'` (every branch) and PRs into `main` + `development`.
> - Runs on GitHub's cloud runners (`ubuntu-latest`).
> - Steps: checkout → set up language runtime **pinned to the exact version
>   production runs** → cache dependencies → install deps → prepare a throwaway
>   test database → run migrations → set up Node + install + `<<<BUILD_CMD>>>` →
>   `<<<TEST_CMD>>>`.
> - Also add dependency vulnerability scanning (`composer audit`, `npm audit`, or
>   the equivalent for this stack) as its own step.
>
> **2. `deploy-staging.yml` — staging deploy**
> - Trigger: `workflow_run` on the `CI` workflow, `branches: [development]`,
>   `types: [completed]`.
> - Gate the job with
>   `if: github.event_name == 'workflow_dispatch' || github.event.workflow_run.conclusion == 'success'`
>   so a failing CI run can never deploy.
> - Add a `workflow_dispatch: {}` trigger too, for manual runs.
> - `runs-on: [self-hosted, staging]` — see the runner-label warning below.
>
> **3. `deploy.yml` — production deploy**
> - Identical to staging, but `branches: [main]` and
>   `runs-on: [self-hosted, production]`.
>
> **4. `enforce-pr-source.yml` — branch-source gate**
> - Trigger: `pull_request` into `main`, types
>   `[opened, synchronize, reopened, edited]`.
> - Pass if `github.head_ref` is `development`, or matches `chore/*` / `fix/*`.
>   Otherwise fail with a clear error telling the author to retarget at
>   `development`.
> - Read the branch name from an `env:` var, not inline `${{ }}` interpolation
>   inside the shell script, so a hostile branch name can't inject shell code.
>
> ### Deploy job steps (both deploy workflows)
>
> 1. Checkout the target branch explicitly (`ref: main` / `ref: development`).
> 2. Install dependencies with production flags (no dev dependencies, optimized
>    autoloader / equivalent).
> 3. Run `<<<BUILD_CMD>>>`.
> 4. **Sync to the deploy path** — build in the runner's own disposable checkout
>    directory, then `rsync -a --delete` into `<<<DEPLOY_PATH>>>`, with an
>    `--exclude` for every one of `<<<PERSISTENT_PATHS>>>`.
> 5. Run migrations non-destructively (never a `fresh`/`reset`/`seed` variant) and
>    warm any config/route/view caches.
> 6. Fix ownership/permissions on the writable paths (see below).
> 7. `<<<RELOAD_CMD>>>`.
>
> ### Non-obvious requirements — do not skip these
>
> - **Pin runner labels.** If multiple self-hosted runners exist they all carry
>   the default `self-hosted` label, so `runs-on: [self-hosted]` can route a
>   production deploy onto the staging box. Give each runner a distinct label
>   (`production`, `staging`) and require it.
> - **Build in a disposable dir, then rsync.** Never serve the web root directly
>   from the runner's checkout directory — that couples "where the app lives" to
>   the runner's workspace lifecycle.
> - **Protect persistent state.** `--delete` keeps the deploy path in sync with
>   the repo, but excluded paths must be protected from deletion too, so env
>   config, the database, and uploads/logs are never touched by a deploy.
> - **Least-privilege sudo.** The deploy user needs passwordless sudo for exactly
>   two things: the reload command, and a `chown -R` limited to the specific
>   writable paths. One rule per file under `/etc/sudoers.d/`, mode `0440`. No
>   blanket sudo.
> - **Reclaim ownership before chmod.** The web process writes into the writable
>   paths between deploys, so those files end up owned by `<<<WEB_USER>>>`, and
>   `chmod` on a file you don't own fails even with group-write access. `chown`
>   back to `<<<DEPLOY_USER>>>` first — that makes the step idempotent regardless
>   of what the web user wrote in between.
> - **Version parity.** CI must test on the same language version production runs.
>   A pipeline that tests on 8.2 and deploys to 8.3 is not testing production.
> - **`workflow_dispatch` bypasses the CI gate** by design (it's the escape
>   hatch). Document that in a comment so nobody assumes manual deploys are
>   tested.
>
> ### Deliverables
>
> Write all four workflow files with explanatory comments covering *why* each
> non-obvious choice was made (especially the runner labels, the rsync excludes,
> and the chown-before-chmod ordering). Then document in the project's
> `CLAUDE.md` / README: the branching rules, the server setup, and the exact
> sudoers rules the deploy user needs.
>
> Finally, tell me which repository settings I need to configure by hand in the
> GitHub UI, since you can't set those: branch protection on `main`, and which
> checks to mark as required.

---

## Part 3 — Manual steps the agent can't do

1. **Register the self-hosted runners** on each server, with distinct labels
   (`production` / `staging`). Separate registrations, not a cloned identity — a
   duplicate runner identity causes the two to fight over the same job queue.
2. **Branch protection ruleset on `main`** — require the `CI` and
   `Enforce PR Source Branch` checks to pass before merge.
3. **Provision the sudoers rules** on each server.
4. **Create `.env` on each server by hand**, once, at the deploy path. It's
   rsync-excluded, so CI never manages it. Staging needs its own distinct app
   key and its own environment/URL values — never a copy of production's.

---

## Why this shape

**CI on every branch, deploy only from two.** Fast feedback everywhere; deploys
stay narrow. The deploy workflows don't re-run tests — they consume CI's verdict
via `workflow_run`, so tests run once per commit rather than once per pipeline.

**Staging mirrors production exactly** — same deploy path, same permissions
model, same runtime versions — so it's a real rehearsal. Anything that differs
should be deliberate and written down.

**The branch-source gate is automation, not documentation.** "PRs into main come
from development" is the kind of rule that quietly stops holding the moment
someone unfamiliar with it opens a PR. As a required status check it holds
without anyone needing to remember it.

**The `chown`-then-`chmod` ordering and the runner-label pinning are both
lessons from real failures**, not theory. The permissions one surfaces as
`Operation not permitted` mid-deploy; the label one silently deployed the wrong
branch to the wrong server. Both are cheap to prevent and annoying to diagnose.

## Known gaps in this design

Carry these forward as deliberate follow-ups rather than assuming they're
handled:

- **No rollback path.** Deploy is a one-way rsync; recovering means re-merging a
  revert and waiting for a full CI + deploy cycle. A release-directory +
  symlink-swap scheme fixes this properly.
- **No database backup before `migrate --force`.** Worth adding as a deploy step
  for anything with real data.
- **No static analysis or secret scanning.** Tests and dependency audits only.
- **No smoke test after deploy.** Nothing verifies the site actually responds
  before the job reports success.
