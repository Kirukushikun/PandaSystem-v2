# PANDA v2 — Development Plan

**Stack baseline:** Laravel 13 · Livewire 4 *(plan originally said 12/3; composer installed 13/4.3 — classic class-based components still apply)* · MySQL/MariaDB (Laragon) · Vite
**References:** `system-overview.md` (behavior spec) · `panda-ui-concept.html` (UI contract — the tabs marked "mockup only" become real routes)

> **Status update:** a full **UI-scaffold pass (Steps 0–9, see `ui-scaffold-checklist.md` at repo
> root) was completed before this plan's phases** — every screen below already exists as a
> Livewire component with real routes and the mockup's hardcoded sample data. The phases in §5
> therefore start from "replace hardcoded arrays with real models + policies", not from blank
> screens. Deviations from this plan made during the scaffold are marked *(actual: …)* below.

---

## 1. Recommended tech per function

You said you have your own picks — treat this as a second opinion. The "Default" column is what I'd ship; "Alternative" is the fallback if the default fights you.

| Function | Default recommendation | Alternative / notes |
|---|---|---|
| Auth (external company identity) | Custom guard or `Auth::attempt` against the external system's API/DB via a dedicated `AuthService`; store only the PANDA profile locally | If the external system speaks LDAP: `directorytree/ldaprecord-laravel`. Never mirror passwords into PANDA |
| Roles & permissions | **Boolean columns + Gates/Policies.** The five stage permissions + three flags are fixed and small — `spatie/laravel-permission` is overkill and hides the logic | Revisit spatie only if roles become admin-definable |
| PAN status flow | **Enum (`PanStatus`) + a small state-machine service** (`PanWorkflow`) that owns every transition and throws on illegal ones | `spatie/laravel-model-states` if you want transitions as classes |
| Notifications (bell + expiry) | **Laravel's database notifications** + a scheduled command that generates allowance-expiry notifications; Livewire `wire:poll.30s` on the bell | Real-time later with Laravel Reverb + Echo — don't start there; polling is fine at this scale |
| File attachments / e-signatures | Local `storage/app` via a private disk + policy-gated download route (`->middleware('can:view,pan')`) — **never a public path**, this is the direct-link hole from v1 | `spatie/laravel-medialibrary` if attachments multiply per PAN |
| Backups + health check | **`spatie/laravel-backup`** — nightly run, cleanup, and `backup:monitor` gives you the "stale/oversized" health check out of the box | Plain `mysqldump` in a scheduled job if you want zero deps |
| Spreadsheet import/export (employees) | **`maatwebsite/excel`** (chunked imports, validation rows) | `spatie/simple-excel` for lighter, stream-based CSV |
| PAN print document | Keep the **Blade print view + `window.print()`** you already have — it works and matches the official form | `spatie/laravel-pdf` (headless Chrome) only if you need server-side PDFs (e.g. emailing copies) |
| Audit trail | **Custom `audit_trails` table + model observers** — your audit is domain-worded ("tagged Manila", "voided with reason"), generic packages log diffs, not intent | `owen-it/laravel-auditing` for field-level diffs *in addition*, not instead |
| Access log | `LoginListener` on `Login`/`Failed` auth events → `access_logs` table | — |
| Sensitive fields (participants, prep details) | Laravel **encrypted casts** for reversible fields; `hash('sha256', …)` only where you truly never need the value back | Don't hash participant IDs if you need "who acted" in the UI — encrypt instead |
| Danger zone (wipe/purge) | **Queued jobs** with chunked deletes (`lazyById()->each`) + typed-count confirmation server-side re-check | — |
| Queue driver | `database` queue (Laragon-friendly, no Redis dependency) | Redis + Horizon if volume grows |
| Testing | **Pest** + Livewire testing helpers; the state machine is the highest-value test target | — |

---

## 2. Folder structure

```
app/
├── Enums/
│   ├── PanStatus.php            # Draft, WithDivisionHead, ReturnedToRequestor, AwaitingTag,
│   │                            # InPreparation, ForConfirmation, ReturnedToPreparer,
│   │                            # ForHrApproval, ForFinalApproval, Approved, Served,
│   │                            # Unserved, Filed, Withdrawn, Voided
│   ├── ActionType.php           # the 13 PAN action types
│   ├── ConfidentialityTag.php   # Untagged, Tarlac, Manila
│   ├── EmploymentStatus.php     # Probationary, Regular, ProjectBased, FixedTerm, Casual, PartTime, Seasonal
│   └── ReturnReason.php         # preset return reasons (+ Custom)
│
├── Models/                      # see §3 for fields & relationships
│   ├── User.php
│   ├── Department.php
│   ├── Farm.php
│   ├── Employee.php
│   ├── PanRequest.php
│   ├── PanForm.php              # preparation details
│   ├── PanReturn.php            # correction/return log
│   ├── AccessLog.php
│   ├── AuditTrail.php
│   └── ExpiryNotification.php   # or fold into database notifications — see §5 phase 7
│
├── Livewire/                    # one folder per module = one sidebar item
│   ├── Requestor/               # Index (table), Form, Show
│   ├── DivisionHead/            # Queue, Show (renders request-only OR request+PAN state)
│   ├── HrPreparation/           # Queue, PrepareForm, Show, Employees, EmployeeHistory
│   ├── HrApprover/              # Queue, Show
│   ├── FinalApprover/           # Queue (bulk actions live here), Show
│   ├── Admin/                   # Users, UserAccess, Employees
│   │                            # (actual: AccessLogs/AuditTrail moved under Maintenance — the
│   │                            #  mockup put Logs & Audit in the Maintenance tab strip)
│   ├── Maintenance/             # Logs, ReferenceValues, Backups, DangerZone
│   ├── Help/                    # Glossary (actual: full-page Livewire component, not plain Blade)
│   └── Shared/                  # NotificationBell (Livewire — it has state)
│                                # (actual: StatusPill, StageTracker, TagDot, Kebab, Modal,
│                                #  ActionReference tables etc. are ANONYMOUS BLADE components in
│                                #  resources/views/components/ — they're stateless, so Blade is
│                                #  the right tool; promote to Livewire only if they gain behavior)
│
├── Services/
│   ├── PanWorkflow.php          # THE state machine: every transition, who may do it, side effects
│   ├── CarryOverService.php     # prior PAN "To" → new PAN "From"; employment-status lock
│   ├── PanReferenceGenerator.php# PAN-2026-00001 style reference numbers
│   └── ExternalAuthService.php  # company-system credential check + profile sync
│
├── Policies/
│   ├── PanRequestPolicy.php     # view/act rules incl. Manila gating — the single source of truth
│   ├── EmployeePolicy.php       # incl. "no delete while ongoing PAN"
│   └── UserPolicy.php
│
├── Jobs/
│   ├── WipePanRecords.php / PurgeAttachments.php / PurgeLogs.php
│   └── GenerateExpiryNotifications.php
│
├── Observers/
│   └── AuditObserver.php        # writes audit_trails on significant model events
│
└── Listeners/
    └── RecordLoginAttempt.php

resources/views/
├── layouts/app.blade.php        # sidebar shell, theme toggle, notification bell
├── components/…                 # anonymous Blade components (shared UI — see note above)
├── livewire/…                   # mirrors app/Livewire
├── login.blade.php              # standalone sign-in page (from panda-login-concept.html)
└── pan-print.blade.php          # (actual path) the real print view — 3 copies, signatories;
                                 # currently static sample + CDN assets; wire data + localize
                                 # assets in Phase 6

routes/web.php                   # thin: route → Livewire full-page component, all behind middleware
```

**Livewire conventions to commit to early**

- Full-page components per screen; small nested components only for the reusable pieces in `Shared/`.
- Authorization happens in **policies**, called from `mount()`/actions — never only in the sidebar. (This is exactly the v1 gap: "a couple of admin-adjacent screens are gated by the wrong module permission".)
- Every table gets the same action grammar you settled on in the mockup: View (link) · one primary verb · kebab.

---

## 3. Data model — migrations & relationships

### Migration order (respects FK dependencies)

```
1. farms                      (id, name)
2. departments                (id, name)
3. users                      (external_id, name, position, farm_id,
                               is_requestor, is_division_head, is_hr_preparer,
                               is_hr_approver, is_final_approver,          ← 5 stage booleans
                               is_hr_head, is_dh_head, is_admin,           ← 3 flags
                               esign_path, timestamps, softDeletes)
4. department_user_requestor  (department_id, user_id)      ← "requests for"
5. department_user_head       (department_id, user_id)      ← "heads" (co-heads OK)
6. employees                  (id, employee_no unique, name, farm_id, department_id,
                               position, timestamps, softDeletes)
7. pan_requests               (id, reference unique, employee_id, department_id,
                               action_type enum, justification nullable,
                               attachment_path nullable, status enum,
                               confidentiality_tag enum default untagged,
                               requested_by, division_head_id nullable,
                               hr_preparer_id nullable, hr_approver_id nullable,
                               final_approver_id nullable,                 ← encrypted casts
                               origin enum('requestor','hr'),              ← "Update PAN" starts at HR
                               previous_pan_id nullable self-FK,           ← the chain / follow-ups
                               submitted_at, filed_at, timestamps, softDeletes)
8. pan_forms                  (id, pan_request_id unique, date_hired,
                               employment_status enum, doe_from, doe_to nullable,
                               wage_no nullable,                            ← Wage Order only
                               action_reference json,                      ← [{field, from, to}, …]
                               remarks text nullable, prepared_by, timestamps)
9. pan_returns                (id, pan_request_id, from_stage enum, to_stage enum,
                               reason, details nullable, returned_by, timestamps)
10. access_logs               (id, username, user_id nullable, ip, user_agent,
                               successful bool, created_at)
11. audit_trails              (id, user_id, module, action, subject_type/subject_id,
                               context json nullable, created_at)
12. notifications             (Laravel's own table — bell + expiry reminders)
```

### Relationship map

```
Farm        1─* User            1─* Employee
Department  *─* User (requestor pivot)
Department  *─* User (head pivot)
Department  1─* Employee        1─* PanRequest

Employee    1─* PanRequest ──1 PanForm
                     │
                     ├── 1─* PanReturn        (full back-and-forth history)
                     ├── *──1 PanRequest      (previous_pan_id — the carry-over chain)
                     └── participants → users (requested_by, division_head_id, …)
```

### Modeling decisions worth locking in

- **`action_reference` as JSON**, exactly like v1's print view consumes it: ordered rows `{field, from, to}` with the six fixed fields first (`section, place, head, position, joblevel, basic`), `leavecredits` only when Regularization, allowance rows appended. One accessor turns it into the table; the print view reuses it untouched.
- **Status is one enum on `pan_requests`** — resist splitting "approval status" and "serving status" into two columns; the glossary page you built is the definitive state list, and one column + state machine keeps illegal combinations unrepresentable.
- **`previous_pan_id`** powers three features at once: the From-value carry-over, the employment-status lock, and the "Pre-generated from PAN-…" link in the prep form.
- **Employee delete guard** belongs in `EmployeePolicy@delete` *and* a DB-level restraint (`whereDoesntHave('panRequests', fn ($q) => $q->ongoing())`) — the UI's disabled kebab item is decoration, not enforcement.
- **Manila gating lives in `PanRequestPolicy@view`,** checked on *every* entry point (list, show route, attachment download, print). This closes v1's known direct-link hole by construction.

---

## 4. Livewire module ↔ mockup mapping

| Mockup tab (static) | Real route | Component |
|---|---|---|
| My PAN Requests / Request Form / View | `/requests`, `/requests/create`, `/requests/{pan}` | `Requestor\{Index,Form,Show}` |
| Department Queue / View (+PAN) | `/division`, `/division/{pan}` | `DivisionHead\{Queue,Show}` — Show renders the PAN-details extension only when `pan_forms` exists |
| Preparation Queue / Form / View / Employees / History | `/preparation`, `/preparation/{pan}/edit`, `/preparation/{pan}`, `/employees`, `/employees/{employee}/pans` | `HrPreparation\*` |
| HR / Final approval queues + views | `/hr-approval`, `/final-approval`, + `/{pan}` | `HrApprover\*`, `FinalApprover\*` |
| User Accounts / User Access | `/admin/users`, `/admin/users/{user}` | `Admin\{Users,UserAccess}` |
| Employee Directory | `/admin/employees` | `Admin\Employees` |
| Maintenance tabs | `/maintenance/{logs,reference,backups,danger}` | `Maintenance\*` |
| Glossary | `/help/glossary` | *(actual)* `Help\Glossary` full-page Livewire component |
| Maintenance Logs & Audit | `/maintenance/logs` | *(actual)* `Maintenance\Logs` (Access Log + Audit Trail panes) |
| Login | `/login` | *(actual)* standalone Blade view, no layout |
| Print | `/pan/{pan}/print` | *(actual)* Blade view `pan-print.blade.php`, behind the same policy |
| Return/Dispute, Update PAN, Add Employee modals | — | *(actual)* shared `x-modal` Blade component (open/close via global JS); type-to-confirm modals are Livewire state within their page component |

---

## 5. Build order

Each phase ends runnable and demoable. Don't start a phase before the previous one's tests pass — especially phases 3–6, which all lean on the state machine.

> **Note (post-scaffold):** every screen mentioned below already exists as a scaffold component.
> "Build X module" now means: add the migrations/models/policies, then swap that component's
> hardcoded arrays for real queries and its `showToast(…)` stubs for real actions — keeping the
> markup, shared components, and sample data (as seeders) intact.

### Phase 0 — Foundation *(everything depends on this)*
- Laravel + Livewire scaffold, the app layout ported from the mockup (sidebar, theme toggle, tokens → `app.css`).
- `users` migration with the 8 booleans, Gates (`stage:requestor`, … , `flag:admin`), route middleware.
- External-auth stub: a fake `ExternalAuthService` you can swap for the real company system later — don't block the whole build on the integration.
- Access log listener (cheap now, painful to retrofit).

### Phase 1 — Reference data
- Farms, Departments (+ the two pivots), Employees CRUD, import/export.
- Admin: Users list + User Access screen (permissions, departments, flags, e-sign upload).
- *Why early:* every later phase needs real employees/departments to test against.

### Phase 2 — PAN core + state machine
- `pan_requests` migration, `PanStatus`/`ActionType` enums, `PanReferenceGenerator`.
- **`PanWorkflow` service with full transition table + Pest tests before any UI.** This is the heart of the system; getting it right here makes phases 3–6 mostly UI work.
- Requestor module: draft → submit → read-only; edit/delete drafts; attachment upload (private disk).

### Phase 3 — Division Head stage
- Queue (department-scoped, Manila rows excluded unless DH Head), approve / return with reason (`pan_returns`), the two Show states.
- Requestor's returned-flow: replace attachment, resubmit, withdraw.

### Phase 4 — HR Preparation *(the deep one)*
- Tagging + the four tag/role outcomes (incl. the permanent lock for ordinary preparers on Manila).
- `pan_forms`, Action Reference editor (fixed rows + dynamic allowances), `CarryOverService` (From ← previous To, status lock), wage-number-only-for-Wage-Order.
- Submit for confirmation; DH confirm/dispute; void with reason.
- Employees tab + PAN history + **Update PAN** (HR-originated request, `origin = 'hr'`, skips phases 2–3 stages).

### Phase 5 — Approvals + closeout
- HR Approver queue: approve / return-one-step.
- Final Approver: single + **bulk** (by selection and by action type), reject-to-preparation, Regularization → auto-"Regular".
- Served / Unserved (with reason) / Filed; follow-up PAN creation off a filed one.

### Phase 6 — Documents & notifications
- Print route reusing the existing Blade (3 copies, signatory grid, e-signs from user profiles), gated by the policy.
- Database notifications + bell component (`wire:poll`); `GenerateExpiryNotifications` scheduled daily for allowances with `doe_to` approaching.
- Audit trail observer wired to every significant action (tagging, approvals, returns, voids, permission changes) + Admin viewer.

### Phase 7 — Maintenance
- `spatie/laravel-backup`: nightly backup, cleanup, `backup:monitor`; Backups screen with **Run Backup Now** (this time actually reachable 🙂) and restore-with-typed-confirmation.
- Reference Values screen (guarded deletes when in use).
- Danger zone: preview-count queries + queued wipe/purge jobs with server-side count re-verification.

### Phase 8 — Hardening *(the v1 lessons, as a checklist)*
- [ ] Every route/action authorized by **policy**, not by menu visibility (v1: wrongly-gated admin screens).
- [ ] Manila PANs unreachable by direct link, attachment URL, or print URL for unauthorized users (v1: open follow-up).
- [ ] Every "return/reject" transition reachable from a real button (v1: one existed with no UI).
- [ ] Pest coverage: full happy path Draft→Filed, every branch (return/dispute/reject/void/withdraw/unserved), every Manila/Tarlac × role combination, employee-delete guard, bulk approval.
- [ ] Encrypted casts verified on participants + sensitive prep fields.

---

## 6. Suggested first week

1. Phase 0 in full.
2. Migrations 1–6 + seeders that mirror the mockup's sample data (K. Reyes, M. Dela Cruz, S. Lim…) — you'll be able to compare every screen against `panda-ui-concept.html` one-to-one as you build.
3. `PanWorkflow` transition table on paper first (the Glossary page *is* that table — transcribe it), then as tested code.
