# CLAUDE.md — PANDA v2

Workflow system for PANs (Personnel Action Notices) — HR requests that move through a fixed
approval chain: Requestor → Division Head → HR Preparation → DH Confirmation → HR Approver →
Final Approver → Served → Filed. Full behavior spec in `system-overview.md`; build sequence in
`development-plan.md`.

## Tech stack
- **Actual:** Laravel 13 + Livewire 4.3 (plan said 12/3 — composer won; classic class-based
  components in `app/Livewire` work fine), MySQL (Laragon on Windows), Vite, Pest, `database` queue.
- **Packages planned:** `spatie/laravel-backup`, `maatwebsite/excel`. Deliberately NOT using
  spatie/laravel-permission (roles are 8 fixed booleans on `users` + Gates/Policies).
- Auth comes from the **external company system** (via `ExternalAuthService`); PANDA never stores
  passwords, only the local profile + permissions.

## Current state
- **REAL BUILD COMPLETE end-to-end** (branch `ui-scaffolds`, committed per-phase as
  "feat: real-build Step N - …", 179 Pest tests green). Every module runs on live data:
  Requestor, Division Head, HR Preparation (tagging/carry-over/Action Reference editor),
  DH Confirmation, HR + Final approval (bulk, Regularization auto-Regular), serve/file,
  print (3-copy layout, local assets), notifications (status handoffs + `panda:expiry-reminders`,
  live in-app bell via Laravel Reverb — see Dev-environment gotchas; OS-level push (FCM) was
  deliberately skipped, in-app only),
  Admin (accounts/access/roster), Maintenance (logs, reference values, mysqldump backups via
  `panda:backup`, danger-zone purges).
- **Master data finalized**: farms BDL/BFC/BRD/PFC/RH; 11 departments (Accounting, Audit,
  Feedmill, General Services, Human Resources, IT and Security Services, Poultry, Purchasing,
  Sales & Marketing, Swine, Treasury). **One role per seeded account**: kreyes=Requestor,
  jbautista=Division Head (heads Poultry+Feedmill), tnavarro=HR Preparer, mdelacruz=HR Head,
  caguirre=DH Head, rocampo=HR Approver, vsalazar=Final Approver, admin_it=Admin. `db:seed`
  seeds master data only (empty PAN slate); demo PANs via `db:seed --class=PanSeeder`.
- **AUTH_FAKE dev mode** (local .env): login page shows a one-click dev-accounts panel; any
  password signs in a seeded email. Hard-disabled in production. Post-login landing is
  role-aware (`User::landingRoute()`).
- `wire:confirm` everywhere is intercepted globally in app.js and rendered as the styled
  `#confirm-modal` in the app layout — never edit individual buttons for confirm styling.
- Scaffold-era docs and mockups all live in `project-overview/` (kebab-case filenames):
  `ui-scaffold-checklist.md`, `development-plan.md`, `development-playbook.md`, `asana-plan.md`,
  `system-overview.md`, `panda-ui-concept.{html,css,js}`, `panda-login-concept.html`.
- **Remaining / deferred:** spreadsheet import-export on the Employee Directory
  (maatwebsite/excel not installed — buttons toast "planned"); fill AUTH_API_*/TURNSTILE_*
  in .env + recreate users with external-API-matching ids before go-live; scheduler
  (`php artisan schedule:work` or Task Scheduler) needed for nightly backups + expiry reminders.

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
- **Real-time bell (Reverb) needs its own running process** — `php artisan reverb:start`,
  separate from `php artisan serve`/`npm run dev`. Without it the bell just silently misses live
  updates (no error) until the next full page load. `BROADCAST_CONNECTION=reverb` in `.env`.
- `Broadcast::channel()` (in `routes/channels.php`) binds to whichever broadcaster instance is
  active at boot — never swap `config('broadcasting.default')` at runtime expecting existing
  channel registrations to carry over; they won't, and every channel auth silently 403s. Test
  channel-authorization rules by invoking the registered callback directly, not through
  `/broadcasting/auth`, if the driver needs to differ from `phpunit.xml`'s pinned one.
