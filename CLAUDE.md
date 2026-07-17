# CLAUDE.md — PANDA v2

Workflow system for PANs (Personnel Action Notices) — HR requests that move through a fixed
approval chain: Requestor → Division Head → HR Preparation → DH Confirmation → HR Approver →
Final Approver → Served → Filed. Full behavior spec in `System_overview.md`; build sequence in
`DEVELOPMENT_PLAN.md`.

## Tech stack
- **Actual:** Laravel 13 + Livewire 4.3 (plan said 12/3 — composer won; classic class-based
  components in `app/Livewire` work fine), MySQL (Laragon on Windows), Vite, Pest, `database` queue.
- **Packages planned:** `spatie/laravel-backup`, `maatwebsite/excel`. Deliberately NOT using
  spatie/laravel-permission (roles are 8 fixed booleans on `users` + Gates/Policies).
- Auth comes from the **external company system** (via `ExternalAuthService`); PANDA never stores
  passwords, only the local profile + permissions.

## Current state
- **UI scaffold COMPLETE** (branch `ui-scaffolds`, committed per-step as "feat: Phase N - …"):
  the whole mockup ported to Livewire components with real routes — **hardcoded sample data,
  no database/models/policies yet**. Plan + what each step delivered: `UI_SCAFFOLD_CHECKLIST.md`
  (repo root). Mockups/planning docs live in `project-overview/`.
- Shared UI = anonymous Blade components in `resources/views/components/` (x-status-pill,
  x-tag-dot, x-stage-tracker, x-modal, x-pan.request-details, x-pan.prepared-details, …);
  only NotificationBell is a Livewire component. Livewire interactivity exists where state
  matters: prep-form tag/role sim, final-approver bulk selection, danger-zone type-to-confirm,
  user-access toggles, reference-values lists.
- The mockup remains the **UI contract**: its "tabs" marked with `<!-- mockup only -->` comments
  are separate routes (e.g. View Request = `/pan/{id}`). Its sample data is the intended seeder
  data — scaffold screens match it 1:1 for comparison.
- The real print layout now lives in the repo at `resources/views/pan-print.blade.php`
  (user-ported; 3 copies Employee / 201 Filing / Payroll, Courier, green borders, signatories,
  compact print modes). Served by `/pan/{pan}/print`. Still static sample data + CDN assets
  (Tailwind CDN, Font Awesome, Google Fonts) — wire real data and localize assets in the real build.
- **Next:** the real build per `DEVELOPMENT_PLAN.md` — start with the `PanStatus` enum +
  `PanWorkflow` state machine (tested), then migrations/seeders, then wire modules to data.

## Key architecture decisions
- **One `PanStatus` enum + `PanWorkflow` state-machine service** owns every transition; build and
  test it before any workflow UI. The Glossary screen in the mockup is the definitive state list.
- **`action_reference` is a JSON column** on `pan_forms`: ordered `{field, from, to}` rows — six
  fixed fields (section, place, head, position, joblevel, basic), `leavecredits` only for
  Regularization, dynamic allowance rows after. The print Blade already consumes this shape.
- **`previous_pan_id`** self-FK chains PANs: powers From-value carry-over, the employment-status
  lock, and the "Pre-generated from PAN-…" link.
- **Manila/Tarlac confidentiality** is enforced in `PanRequestPolicy` on every entry point
  (list, show, attachment download, print) — never by hiding buttons.
- PANs can also **originate at HR** ("Update PAN" from the Employees tab, `origin='hr'`), skipping
  the Requestor/Division Head stages — common for Wage Orders.
- Attachments/e-signs on a **private disk** behind policy-gated download routes.

## Domain naming (settled in conversation — differs from the overview doc)
- Routine confidentiality tag = **"Tarlac"**; confidential = **"Manila"**.
- "Confidentiality Approver" was renamed **"DH Head"** (pairs with "HR Head"): cross-department,
  sees ONLY Manila PANs in their Division Head queue, no regular ones.

## UI conventions (mockup-established, keep in Laravel port)
- Row-action grammar in every table: **View (ghost) · one filled primary verb · ⋯ kebab** for
  destructive/secondary actions. No disabled buttons — the status pill explains inaction.
  Exception: print icon stays visible in HR Prep tables.
- Design tokens as CSS custom properties; light/dark via `prefers-color-scheme` +
  `data-theme` override (toggle persists to `localStorage['panda-theme']`). Accent green
  `#1F5E42` / dark `#5CA67F`. `[hidden]{display:none!important}` reset is load-bearing.
- Destructive maintenance actions: mode radio-cards → Preview Count → **type-the-exact-count**
  confirm modal → queued job + toast (structure mirrors the user's data-wipe.html).
- Status pills, tag dots (purple=Manila, blue=Tarlac, gray=untagged), and stage tracker are shared
  components; the Glossary reuses them as a legend.

## Gotchas / v1 lessons (do not repeat)
- v1 gated some admin screens by the **wrong permission** — authorize by policy on every
  route/action, never by sidebar visibility.
- v1's confidential PANs were openable by **direct link**; the disabled button was the only guard.
- v1 had a return/reject transition with **no button in the UI** — every transition needs a
  reachable control.
- Employee deletion must be blocked while an ongoing PAN exists (submitted, not yet
  filed/withdrawn/voided — drafts don't block); enforce in policy + query, not just UI.
- Regularization final-approval **auto-finalizes status to "Regular"**, overriding earlier values.
- The user edits files by hand between sessions (CSS/JS were externalized, print view moved
  around) — **check current file state before editing; don't assume prior structure.**

## Dev-environment gotchas (Windows/Laragon)
- Stray `php artisan serve` processes cause compiled-view rename "Access is denied" 500s —
  kill strays, then `php artisan view:clear`.
- Always `php artisan route:clear` after adding routes — the cache goes stale silently.
- Don't batch-edit Blade files with PowerShell 5.1 `Get-Content`/`Set-Content` — BOM-less UTF-8
  gets read as ANSI and em dashes/₱/· turn to mojibake. Use proper editor tooling.
