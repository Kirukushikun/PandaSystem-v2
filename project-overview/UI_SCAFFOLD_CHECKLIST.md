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

## Step 4 — HR Preparation module ✅
- [x] `HrPreparation\Queue` — tag-dot column, print icons (visible per convention), row verbs
      Open/Continue/Revise/Mark Served, tag-legend locknote, link to Employees lens (`/preparation`)
- [x] `HrPreparation\PrepareForm` — first REAL Livewire interactivity: tag/role simulation drives
      the lockable regions + note (4 outcomes), previous-PAN "See more" toggle, dynamic allowance
      rows (add/remove, 13 types). `.toin` green highlight is global JS. (`/preparation/{pan}/edit`)
- [x] `HrPreparation\Show` — always Request + PAN Details (no request-only variant for HR roles);
      warn note, Wage Order No. via new `wage-no` prop on x-pan.prepared-details (`/preparation/{pan}`)
- [x] `HrPreparation\Employees` + Update PAN modal (origin='hr' flow; Create & Prepare navigates
      to the prep form) (`/employees` — sidebar keeps HR Preparation active here)
- [x] `HrPreparation\EmployeeHistory` — S. Lim sample, chain of 4 PANs (`/employees/{employee}/pans`)
- [x] *(added)* `x-print-btn` shared component; print buttons toast until step 9's print route

## Step 5 — Approver modules ✅
- [x] `HrApprover\Queue` — 4 rows, "one step back" note, Return-to-HR-Preparer modal (`/hr-approval`)
- [x] `HrApprover\Show` — composes x-pan.* (new `ref-heading` prop for the "Action Reference —
      prepared changes" header) (`/hr-approval/{pan}`)
- [x] `FinalApprover\Queue` — LIVE bulk selection: checkbox state, select-all toggle,
      "Select all of type…" dropdown, Approve selected (toast + clear), Reject modal;
      auto-Regular note (`/final-approval`)
- [x] `FinalApprover\Show` — Leave Credits row (Regularization-only), `hr-approved-by` prop,
      inline auto-Regular note, Reject/Give Final Approval footer (`/final-approval/{pan}`)

## Step 6 — Admin module ✅
- [x] `Admin\Users` — stats, chips, 7 sample accounts w/ flag pills (HR Head / DH Head / Admin);
      View → per-user route (mockup subtab dropped per CLAUDE.md) (`/admin/users`)
- [x] `Admin\UserAccess` — LIVE permission switches (`$perms` array mirrors the 8 planned boolean
      columns, wire:click toggles), dept chips + profile pane, K. Reyes sample (`/admin/users/{user}`)
- [x] `Admin\Employees` + shared Add/Edit Employee modal — 6 roster rows, Remove blocked w/ reason
      while a PAN is ongoing (drafts don't block), ongoing-PAN pills (`/admin/employees`)

## Step 7 — Maintenance module ✅
- [x] Route split: `/maintenance/{logs,reference,backups,danger}` (+ `/maintenance` → logs redirect);
      `x-maintenance-tabs` renders the mockup subtabs as real route links
- [x] `Maintenance\Logs` — Access Log + Audit Trail panes on one screen, as in the mockup
- [x] `Maintenance\ReferenceValues` — LIVE lists: add appends a deletable 0-use value, × deletes,
      in-use values stay blocked with the reason
- [x] `Maintenance\Backups` — stats, recent backups, Run Backup Now; Restore opens a
      Livewire type-RESTORE confirm modal
- [x] `Maintenance\DangerZone` — 3 cards from one GROUPS config: mode radio-cards → Preview Count
      (random, like the mockup) → type-the-exact-count modal (fully Livewire: the required text and
      button label follow the previewed count) → queued-job toast, badges reset

## Step 8 — Help & auth shell ✅
- [x] Glossary page content (`/help/glossary`) — temp component gallery replaced with the real
      thing: journey strip via x-stage-tracker (current="Filed"), 15-row status table looping
      x-status-pill with mockup labels, tags pane via x-tag-dot, roles pane
- [x] Login page from `panda-login-concept.html` (`/login`, standalone Blade view, inline styles
      + data-theme override added; Sign in navigates to /requests; sidebar Log out links here)
- [x] `Shared\NotificationBell` Livewire component — unread state + Mark all read live in the
      component (badge hides at 0); open/close stays in app.js (#notif-clear JS removed)

## Step 9 — Polish pass ✅
- [x] `wire:navigate` sweep — every internal route link has it; deliberate exceptions are the
      standalone pages (Log out → /login, print links → new tab). Shell JS already survives
      navigation by design (document-level delegation). Unused Laravel welcome.blade.php deleted.
- [x] Empty states — new `x-empty-state` shared component; live on the Final Approver queue:
      rows are now component state, so Approve / Approve selected actually clear rows and
      "All caught up" is reachable. (Static tables get theirs when real queries arrive.)
- [x] Print view placeholder route (`/pan/{pan}/print`, standalone page, 3 labeled copies +
      window.print; real layout ports from print-view.blade.php). `x-print-btn` now takes
      `href` and all 8 HR-Prep print icons link to it (new tab) instead of toasting.
- [x] Cross-check — all 24 routes verified 200 with mockup content (content-level checks each
      step; final visual side-by-side is the user's browser pass)

---

*Deliberately deferred to the real build (not UI scaffold work): policies/gates, enums,
state machine, migrations, seeders, file uploads, notifications backend.*
