# PANDA v2 — Asana Development Plan

*(PANDA v2's actual status — filled in from the reusable Asana Plan Template kept outside this repo)*

**Sponsor / supervisor:** —
**Target completion:** —
**One-line description:** A workflow system that moves Personnel Action Notices (PANs) through a
fixed HR approval chain — Requestor → Division Head → HR Preparation → DH Confirmation →
HR Approver → Final Approver → Served → Filed.

---

## Objective 1 — Overview ✅ Done

**Source:** conversation with the project owner — no formal requirements doc handed down;
captured directly as `system-overview.md`.

| Status | Task | Owner | Duration | Notes |
|---|---|---|---|---|
| Done | Write the overview | — | — | `system-overview.md` — roles, PAN lifecycle, confidentiality tiers |

## Objective 2 — Mockup ✅ Done

| Status | Task | Owner | Duration | Notes |
|---|---|---|---|---|
| Done | Build static HTML mockup | — | — | `panda-ui-concept.html` + `panda-login-concept.html` |
| Done | Settle domain naming/glossary | — | — | "Tarlac"/"Manila" confidentiality tags; "DH Head" naming; Glossary screen is the state-list source of truth |

## Objective 3 — Development ✅ Done

| Status | Task | Owner | Duration | Notes |
|---|---|---|---|---|
| Done | UI scaffold complete (Steps 0–9) | — | — | see `ui-scaffold-checklist.md` |
| Done | Domain core — `PanStatus` enum + `PanWorkflow` state machine | — | — | tested before any screen touched it |
| Done | Foundation — auth (`AUTH_FAKE` dev mode), roles, reference data | — | — | farms BDL/BFC/BRD/PFC/RH; 11 departments |
| Done | Requestor + Division Head modules | — | — | real-build Steps 5–6 |
| Done | HR Preparation (tagging, Action Reference editor, carry-over) | — | — | real-build Step 7 |
| Done | HR Approver + Final Approver modules | — | — | real-build Step 8 |
| Done | Documents/printing, notifications, admin, maintenance | — | — | real-build Steps 9–12 |
| Done | Polish — styled confirm dialogs, full-cycle capstone test | — | — | real-build Step 13 |

## Objective 4 — Testing & Hardening 🟡 In progress

**Definition of done:** hardening checklist fully checked; automated suite covers happy path +
every branch; no known authorization gap; UAT signed off.

| Status | Task | Owner | Duration | Notes |
|---|---|---|---|---|
| Done | Automated test suite green | — | — | 179 Pest tests / 627 assertions |
| Done | Hardening checklist (policy-on-every-entry-point, deletion guards, Manila direct-link closure) | — | — | carried out alongside each module, not deferred to the end |
| Not started | UAT / stakeholder acceptance | — | — | |

## Objective 5 — Deployment / Go-Live 🔴 Not started

| Status | Task | Owner | Duration | Notes |
|---|---|---|---|---|
| Not started | Production environment/config ready | — | — | fill `AUTH_API_*` / `USER_API_*` / `TURNSTILE_*` in `.env`; set `AUTH_FAKE=false` |
| Not started | Data & auth cutover | — | — | recreate `users` rows with ids matching the external auth API (dev seed ids are placeholders — `admin_it` currently pinned to `61` as a placeholder external id) |
| Not started | Scheduler running | — | — | `panda:backup` (nightly) + `panda:expiry-reminders` (daily) need `schedule:work` or Task Scheduler |
| Not started | Go-live | — | — | |
| Not started | Post-launch check-in | — | — | |

---

*Deferred / explicitly out of scope for now:*
- *Spreadsheet import/export on the Employee Directory (`maatwebsite/excel` not installed — buttons toast "planned")*
- *Real-time push notifications (polling is sufficient at this scale)*
