# CLAUDE.md — PANDA v2

Workflow system for PANs (Personnel Action Notices) — HR requests that move through a fixed
approval chain: Requestor → Division Head → HR Preparation → DH Confirmation → HR Approver →
Final Approver → Served → Filed. Full behavior spec in `System_overview.md`; build sequence in
`DEVELOPMENT_PLAN.md`.

## Tech stack
- **Target:** Laravel 12 + Livewire 3, MySQL (Laragon on Windows), Vite, Pest, `database` queue.
- **Packages planned:** `spatie/laravel-backup`, `maatwebsite/excel`. Deliberately NOT using
  spatie/laravel-permission (roles are 8 fixed booleans on `users` + Gates/Policies).
- Auth comes from the **external company system** (via `ExternalAuthService`); PANDA never stores
  passwords, only the local profile + permissions.

## Current state
- **Built:** static UI mockup only — `panda-ui-concept.html` + `panda-ui-concept.css/.js`
  (split out by hand from the original single file), `panda-login-concept.html`,
  `DEVELOPMENT_PLAN.md`. No Laravel app exists yet.
- The mockup is the **UI contract**: its "tabs" marked with `<!-- mockup only -->` comments are
  separate routes in the real app (e.g. View Request = `/pan/{id}`). Sample data in it is the
  intended seeder data.
- An older PAN print replica (`#pan-print`) was removed from the HTML by an editor undo; the
  real print layout source of truth is the user's Blade file (`print-view.blade.php`, shared in
  conversation) — 3 copies (Employee / 201 Filing / Payroll), Courier, green borders, 4 signatories.

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
- The user edits mockup files by hand between sessions (CSS/JS were externalized, print view
  vanished) — **check current file state before editing; don't assume prior structure.**
