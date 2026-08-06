# PANDA v2 — Development Plan

**Stack:** Laravel 13 · Livewire 4.3 · MySQL (Laragon local / Docker prod) · Vite · Pest
**Status:** Live
**Last updated:** 2026-08-05
**Repo:** — · **Prod URL:** —

---

## Objectives

*What this project aims to accomplish and provide. These are descriptive goals, not tasks — 3 milestones max.*

1. **Digitize the PAN approval chain** — replace manual/paper-adjacent handling of Personnel Action Notices with a single system that moves each request through a fixed workflow: Requestor → Division Head → HR Preparation → DH Confirmation → HR Approver → Final Approver → Served → Filed.
2. **Enforce confidentiality and authorization correctly** — Manila (confidential) vs Tarlac (routine) PANs gated by policy on every entry point, not by hiding UI, closing the direct-link and permission gaps v1 had.
3. **Carry v1's history forward** — migrate v1's PAN requests, employees, and supporting-document attachments into v2 rather than starting the live system with an empty slate.

---

*Any task can carry an optional `Comment —` line right after its section's table, referencing the task by name. These are free-form context/reasoning too long for the Notes column, and sync to Asana as task comments (not the description) — filled in once, no separate follow-up step.*

## 1. Planning

| Status | Task | Notes |
|---|---|---|
| ✅ | Analyze requirements / problem statement | Captured directly from the project owner as `system-overview.md` — no formal requirements doc |
| ✅ | Set up Git repo & local dev environment | Laravel 13 + Livewire 4.3, MySQL via Laragon |
| ✅ | Define scope — what's in, what's explicitly out | Excel import/export and OS-level push notifications explicitly deferred |

---

## 2. Design

| Status | Task | Notes |
|---|---|---|
| ✅ | Build static HTML/UI mockup | `panda-ui-concept.html` + `panda-login-concept.html` — became the UI contract for Build |
| ✅ | Settle domain naming / glossary | "Tarlac"/"Manila" confidentiality tags; "DH Head" naming; Glossary screen is the state-list source of truth |
| ✅ | Data model / database design | `PanStatus` enum + `PanWorkflow` state machine designed and tested before any screen touched it |

---

## 3. Build

*This is the working spec — the actual instruction set your coding agent follows.*

### 3a. Recommended tech per function

| Function | Default | Alternative / notes |
|---|---|---|
| Auth | External company system via `ExternalAuthService` | PANDA never stores passwords, only local profile + permissions |
| Roles & permissions | 8 fixed booleans on `users` + Gates/Policies | Deliberately not spatie/laravel-permission |
| Core status flow | `PanStatus` enum + `PanWorkflow` state-machine service | One enum owns every transition |
| Notifications | In-app bell via Laravel Reverb | OS-level push (FCM) deliberately skipped |
| File attachments | Private disk, policy-gated download routes | Up to 3 per PAN |
| Backups + health check | spatie/laravel-backup | Local disk + optional Google Drive destination |
| Audit trail | `legacy_actors` JSON + status history via `pan_returns` | |
| Testing | Pest | 179 tests / 627 assertions |

### 3b. Folder structure

```
app/
├── Enums/
├── Models/
├── Livewire/
├── Services/
├── Policies/
├── Console/Commands/
└── Observers/

resources/views/
├── layouts/
├── components/
└── livewire/
```

### 3c. Data model — migrations & relationships

Core tables: `users`, `employees`, `departments`, `farms`, `pan_requests`, `pan_forms`,
`pan_attachments`, `pan_returns`, plus legacy-import columns (`legacy_id`, `legacy_department`,
`legacy_actors`) on `pan_requests` for the v1 migration.

**Relationship map:**
```
Employee  1─*  PanRequest  1─*  PanAttachment
PanRequest  1─1  PanForm
PanRequest  1─*  PanReturn
PanRequest  *─1  PanRequest (previous_pan_id, self-FK carry-over chain)
```

**Modeling decisions worth locking in:**
- One `PanStatus` enum, not split columns — keeps illegal states unrepresentable.
- `action_reference` is a JSON column on `pan_forms` — ordered `{field, from, to}` rows.
- Delete guards (employee deletion blocked while an ongoing PAN exists) live in policy + query, not just UI.

### 3d. Module ↔ mockup mapping

| Mockup tab (static) | Real route | Component |
|---|---|---|
| Requestor | `requestor.*` | `App\Livewire\Requestor\*` |
| Division Head | `division.*` | `App\Livewire\DivisionHead\*` |
| HR Preparation | `hr-prep.*` | `App\Livewire\HrPrep\*` |
| HR/Final Approval | `hr-approval.*`, `final-approval.*` | `App\Livewire\Approval\*` |
| Admin | `admin.*` | `App\Livewire\Admin\*` |
| Maintenance | `maintenance.*` | `App\Livewire\Maintenance\*` |

### 3e. Build order

Each phase ends runnable and demoable.

#### Phase 0 — Foundation
- Auth (`AUTH_FAKE` dev mode), roles, reference data (farms BDL/BFC/BRD/PFC/RH, 11 departments)

#### Phase 1 — Reference data
- Department/farm seeding, employee directory

#### Phase 2 — Core + state machine
- `PanStatus` enum, `PanWorkflow` service, tested before any UI

#### Phase 3 — Requestor + Division Head
- Real-build Steps 5–6

#### Phase 4 — HR Preparation, HR/Final Approval, Documents/printing/notifications/admin/maintenance
- Real-build Steps 7–12

#### Phase N — Hardening
- [x] Policy on every entry point (list, show, attachment download, print), never sidebar visibility
- [x] Manila PANs closed to direct-link access
- [x] Every workflow transition has a reachable UI control
- [x] Employee deletion blocked while an ongoing PAN exists

---

## 4. Testing & Hardening

| Status | Task | Notes |
|---|---|---|
| ✅ | Automated test suite green | 179 Pest tests / 627 assertions |
| ✅ | Hardening checklist | Carried out alongside each module, not deferred to the end |
| ✅ | UAT / stakeholder acceptance | Superseded by direct go-live with real production data |

---

## 5. Deployment

| Status | Task | Notes |
|---|---|---|
| ✅ | Production environment/config ready | Docker Compose + Cloudflare Tunnel; real `AUTH_API_*`/`USER_API_*`/`TURNSTILE_*`; `AUTH_FAKE=false` |
| ✅ | Data & auth cutover | Real admin (external id 61) pinned in `AdminSeeder`; `Users::grant()` resyncs identity from the directory API on every grant/restore |
| ✅ | v1 → v2 legacy data migration | 853 PAN requests + 501 employees imported; 86 of 167 recoverable v1 PAN attachment files pulled from v1 production storage and re-linked |
| 🟡 | Scheduler running | `backup:run` (daily 18:00) + `panda:expiry-reminders` still need `schedule:work`/cron wired up; Reverb runs as its own supervised Docker service |
| ✅ | Go Live | Live on `main`/`deploy` branch |

Comment — v1 → v2 legacy data migration:
Migrated in two passes. First pass (DB data): fixed a real bug in v1's export command that was
silently nulling 82% of employee links, then imported all 853 PAN requests and 501 employees.
Second pass (attachments): discovered the first pass never touched the actual uploaded PDF files
— only their old name/path. Pulled the real files directly from v1's production disk (not the
stale local dev copy) and matched 86 of 167 referenced files back to their PANs; the other 81
were already missing from v1 prod itself before this migration touched anything.

---

## 6. Post-Launch

| Status | Task | Notes |
|---|---|---|
| 🔵 | Post-launch check-in | Ongoing — fixed a production identity-collision bug (soft-deleted demo users colliding with real external ids) and several UI/perf bugs (Backup & Restore screen, Division Head null-employee crash) shortly after go-live |
| ⬜ | System turnover / sign-off | |

---

## Known Gaps / Deferred

*Things intentionally not done yet — the honest list, not a to-do list.*

- Spreadsheet import/export on the Employee Directory (`maatwebsite/excel` not installed — buttons toast "planned")
- Real-time OS-level push notifications (FCM) — in-app bell via Reverb only
- Scheduler (`backups` + expiry reminders) not yet wired to `schedule:work`/cron in production
- v1's single employee-level confidential attachment (separate `employee_attachments` table) has no v2 feature yet — file kept, not imported
- 81 of v1's 167 PAN supporting-document attachments are unrecoverable (already lost from v1 production before this migration)

---

**Status legend:** ✅ Done · 🟡 In Progress · ⬜ Not Started · ❓ Unknown · 🔵 Ongoing
