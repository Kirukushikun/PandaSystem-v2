# UI Scaffold Checklist — mockup → Livewire (no database)

Goal: port `project-overview/panda-ui-concept.html` into organized Livewire components with
real routes and shared Blade components. **Pure UI — hardcoded sample data, no models, no
migrations.** Folder structure follows `DEVELOPMENT_PLAN.md` §2; routes follow §4.

Rule of the road: one step per session-ish, each step ends navigable in the browser.
Sample data stays identical to the mockup (K. Reyes, M. Dela Cruz, S. Lim…) so screens can
be compared 1:1.

---

## Step 0 — Shell (layout, theme, navigation) ✅
- [x] Install `livewire/livewire`
- [x] Port design tokens + full mockup CSS → `resources/css/app.css`
      (mockup CSS is authoritative; Tailwind import removed so preflight doesn't fight it)
- [x] Port shell JS → `resources/js/app.js` (theme toggle w/ `localStorage['panda-theme']`,
      notification panel open/close, kebab menus, toast helper — all via document-level
      delegation so it survives `wire:navigate`)
- [x] `resources/views/layouts/app.blade.php` — sidebar (route links + active state),
      floating theme toggle + notification bell/panel, toast container, FOUC guard script
- [x] Placeholder full-page components for every sidebar item (see routes below)
- [x] `routes/web.php` — all module routes → Livewire components, `/` redirects to `/requests`

Routes stubbed in step 0:
| Route | Component |
|---|---|
| `/requests` | `Requestor\Index` |
| `/division` | `DivisionHead\Queue` |
| `/preparation` | `HrPreparation\Queue` |
| `/hr-approval` | `HrApprover\Queue` |
| `/final-approval` | `FinalApprover\Queue` |
| `/admin/users` | `Admin\Users` |
| `/admin/employees` | `Admin\Employees` |
| `/maintenance` | `Maintenance\Index` |
| `/help/glossary` | static Blade view |

## Step 1 — Shared UI components (`resources/views/components/`) ✅
Pure-presentation pieces as anonymous Blade components (they hold no state yet, so Blade —
not Livewire — is the right tool; promote to Livewire later only if they need behavior):
- [x] `x-status-pill` — `status` key (mirrors planned PanStatus enum) or `tone` + slot label
- [x] `x-tag-dot` (purple=Manila, blue=Tarlac, gray=untagged)
- [x] `x-stage-tracker` — `:stages` array + `current` (label, index, or `*` = all done)
- [x] `x-stat` (wrap a row in plain `<div class="stats">`)
- [x] `x-search-bar` + `x-chip` (wrap in plain `<div class="bar">`)
- [x] `x-kebab` + `x-kebab.item` (open/close JS is global in app.js)
- [x] `x-modal` shell — open via `data-modal-open="{id}"`, close via `data-close`/backdrop/Esc
      (type-to-confirm variant comes with the Maintenance step)
- [x] `x-row-actions` td enforcing View (ghost) · one primary verb · kebab
- [x] Temporary component gallery on `/help/glossary` for visual QA (delete in step 8)
- [x] *(added during step 3)* `x-pan.request-details` + `x-pan.prepared-details` — the two
      Show-view body blocks shared by every role (`sect` prop adds the section header;
      `:rows` mirrors the planned action_reference JSON shape). Role Show pages only own
      their wrapper: crumb, intro copy, pill, tracker, footer verbs, modals.

## Step 2 — Requestor module ✅
- [x] `Requestor\Index` — stats, search/chips, table w/ the mockup's 5 sample rows (`/requests`)
- [x] `Requestor\Form` — new/edit draft form; Save/Submit show scaffold toasts (`/requests/create`)
- [x] `Requestor\Show` — read-only view + stage tracker; heading ref comes from the route,
      body is the static A. Santos sample (`/requests/{pan}`)
- Note: mockup subtabs deliberately dropped — the three screens are separate routes per CLAUDE.md.
  Edit/Resubmit row buttons point at `/requests/create` until per-PAN edit exists.

## Step 3 — Division Head module ✅
- [x] `DivisionHead\Queue` — stats, Manila info note, 5 sample rows w/ Approve/Confirm verbs (`/division`)
- [x] `DivisionHead\Show` — ONE view, both render states via `$hasPreparedPan`
      (scaffold: hardcoded true for PAN-2026-00339/00311; real build: `pan_forms` exists) (`/division/{pan}`)
- [x] Return + Dispute modals (x-modal) reachable from BOTH the queue kebabs and the Show
      footers — every transition has a visible control (v1 lesson)

## Step 4 — HR Preparation module (the deep one — may need two sessions)
- [ ] `HrPreparation\Queue` — tagging UI, lock states (`/preparation`)
- [ ] `HrPreparation\PrepareForm` — employment details + Action Reference editor
      (fixed rows, dynamic allowance rows, previous-PAN "See more") (`/preparation/{pan}/edit`)
- [ ] `HrPreparation\Show` (`/preparation/{pan}`)
- [ ] `HrPreparation\Employees` (`/employees`) + Update PAN modal
- [ ] `HrPreparation\EmployeeHistory` (`/employees/{employee}/pans`)

## Step 5 — Approver modules
- [ ] `HrApprover\Queue` + `Show` (`/hr-approval`, `/hr-approval/{pan}`)
- [ ] `FinalApprover\Queue` — incl. bulk-selection UI (`/final-approval`)
- [ ] `FinalApprover\Show` (`/final-approval/{pan}`)

## Step 6 — Admin module
- [ ] `Admin\Users` (`/admin/users`)
- [ ] `Admin\UserAccess` (`/admin/users/{user}`)
- [ ] `Admin\Employees` + Add/Edit Employee modal (`/admin/employees`)

## Step 7 — Maintenance module
- [ ] Route split: `/maintenance/{logs,reference,backups,danger}`
- [ ] `Maintenance\AccessLogs` + `AuditTrail` tables
- [ ] `Maintenance\ReferenceValues`
- [ ] `Maintenance\Backups`
- [ ] `Maintenance\DangerZone` — mode radio-cards → preview count → type-the-exact-count modal

## Step 8 — Help & auth shell
- [ ] Glossary page content (status legend reusing shared components) (`/help/glossary`)
- [ ] Login page from `panda-login-concept.html` (static, no real auth)
- [ ] Notification bell panel as its own `Shared\NotificationBell` Livewire component

## Step 9 — Polish pass
- [ ] `wire:navigate` on all sidebar/table links; verify theme + kebab JS survives navigation
- [ ] Empty states for every table
- [ ] Print view placeholder route (`/pan/{pan}/print`)
- [ ] Cross-check every screen against the mockup side-by-side

---

*Deliberately deferred to the real build (not UI scaffold work): policies/gates, enums,
state machine, migrations, seeders, file uploads, notifications backend.*
