# {{PROJECT_NAME}} — Development Plan

> **TEMPLATE — how to use this file**
> Copy it into a new project's repo as `DEVELOPMENT_PLAN.md` once the mockup is finished.
> Fill every `{{…}}` placeholder and delete sections that don't apply. Anything you leave
> as a placeholder, the agent should ask about once — then fill in and never ask again.
>
> **Instructions to the agent reading this:** this project follows the mockup-first method in
> `DEVELOPMENT_PLAYBOOK.md` (keep both files at repo root). The mockup is the UI contract.
> Work one checklist step per session, verify in the browser/tests before calling a step done,
> commit per step, and when reality diverges from this plan, update the plan with an
> *(actual: …)* note instead of silently deviating. Maintain the checklist file as the single
> source of "where are we".

**Stack baseline:** {{FRAMEWORK + VERSION}} · {{FRONTEND/COMPONENT LAYER}} · {{DATABASE}} · {{BUILD TOOLS}}
**References:** `{{BEHAVIOR_SPEC.md}}` (behavior spec) · `{{MOCKUP.html}}` (UI contract — tabs marked `<!-- mockup only -->` become separate routes) · `{{LOGIN_MOCKUP.html}}` (if separate)
**Domain glossary:** {{KEY TERMS SETTLED DURING MOCKUP — names for statuses, roles, tags. List renames that differ from older docs so the agent doesn't resurrect dead names.}}

---

## 1. Tech per function

One row per capability. "Default" is what we ship; note the alternative only when a real
trade-off exists. Delete rows that don't apply.

| Function | Default | Notes / alternative |
|---|---|---|
| Auth | Org-standard external auth (`authentication-implementation-guide.md`) + `AUTH_FAKE` dev mode (see §6) | {{changes, if any}} |
| Roles & permissions | {{e.g. fixed boolean columns + Gates/Policies — avoid a permission package unless roles are admin-definable}} | |
| Core domain state | {{e.g. one status enum + a state-machine service that owns every transition}} | |
| Notifications | {{e.g. database notifications + polling bell; real-time only if truly needed}} | |
| File uploads | Private disk + policy-gated download route — never a public path | |
| Backups | {{e.g. spatie/laravel-backup nightly + monitor}} | |
| Import/export | {{package}} | |
| Printing / documents | {{e.g. Blade print view + window.print()}} | |
| Audit trail | {{e.g. custom table + observers — domain-worded, not field diffs}} | |
| Destructive maintenance | Queued jobs, chunked deletes, typed-count confirmation re-checked server-side | |
| Queue driver | {{database / redis}} | |
| Testing | {{e.g. Pest}} — the state machine and the policy matrix are the highest-value targets | |

## 2. Folder structure

```
{{Sketch the target tree: enums, models, one component folder per sidebar module,
  services (state machine, generators), policies, jobs, observers.}}
```

**Conventions to commit to early**
- Full-page components per screen; stateless shared UI = plain template components
  (promote to framework components only when they gain behavior).
- Authorization happens in **policies/gates called from every route and action** — never
  only in navigation visibility.
- One row-action grammar for every table: {{e.g. View (ghost) · one primary verb · kebab}}.
  No disabled buttons — a status label explains inaction.

## 3. Data model

### Migration order (respects FK dependencies)

```
{{1. lookup tables
 2. users (+ permission columns; NO password column if auth is external)
 3. pivots
 4. core domain tables — one status column, never split status into two
 5. detail/child tables
 6. logs (access, audit)
 7. notifications}}
```

### Relationship map

```
{{A 1─* B diagram — ten lines max, just the load-bearing edges,
  including any self-FK chain and what it powers.}}
```

### Modeling decisions worth locking in

- {{JSON columns and their exact consumed shape, e.g. ordered {field, from, to} rows}}
- {{Deletion guards: which records block deletion of what — enforced in policy + query}}
- {{Confidentiality/visibility rules and WHERE they're enforced (every entry point:
   list, show, file download, print)}}
- {{Soft-delete vs hard-delete per table, and why}}

## 4. Route ↔ mockup mapping

| Mockup screen/tab | Real route | Component |
|---|---|---|
| {{…}} | {{…}} | {{…}} |
| Login | `/login` | standalone view, no app layout |
| Print/document views | {{route}} | standalone view behind the same record policy |
| Modals (list them) | — | shared modal component; type-to-confirm ones hold their state in the page component |

## 5. Build order

### Part A — UI scaffold (pure UI, hardcoded mockup data, no database)

Create `UI_SCAFFOLD_CHECKLIST.md` from this outline and tick it there, not here:

- **Step 0 — Shell:** layout, theme, navigation, global JS (document-level delegation so it
  survives SPA navigation), every route stubbed.
- **Step 1 — Shared components:** status pills (keys mirror the future status enum), trackers,
  modals, menus, stat cards, search/chips, row-actions.
- **Steps 2…N — one module per step, in workflow order.** Sample data identical to the mockup.
  Buttons that would persist show a "nothing is persisted yet" toast. Every transition gets a
  visible control. Per-role detail views stay separate components (separate doors → separate
  locks later), composing shared body partials.
- **Step N+1 — Auxiliary:** help/reference pages, login page, notification component.
- **Final step — Polish:** navigation attributes, empty states, placeholder document routes,
  side-by-side mockup cross-check.

### Part B — Real build (each phase ends runnable + tested)

- **Phase 1 — Domain core:** status enum(s) + state-machine service, fully tested BEFORE any
  screen touches data. The spec's state table is the test list. Include: initial state per
  origin, every action's from/to, who may act (permission key), reason-required flags,
  terminal states, "every non-terminal state has an exit".
- **Phase 2 — Foundation:** migrations for users/lookups/pivots/logs, models + factories,
  seeders that mirror the mockup sample data exactly, auth (see §6).
- **Phase 3 — Core domain tables:** the main record + children, enum casts, reference-number
  generator (race-safe), deletion-guard scopes.
- **Phase 4 — Authorization layer:** gates for permission columns on route groups; the record
  policy with the full visibility matrix; gate the file-download and print entry points the
  same day, not later.
- **Phases 5…N — one module per phase, in workflow order.** Because the scaffold exists,
  "build module X" = swap its hardcoded arrays for queries and its toast stubs for real
  actions through the state machine. Keep the markup.
- **Phase N+1 — Cross-cutting:** documents/printing with real data, notifications + scheduled
  commands, audit observers, backups, destructive-maintenance jobs.
- **Phase N+2 — Hardening checklist:**
  - [ ] Every route/action policy-authorized (not menu-gated)
  - [ ] No record reachable by direct link / file URL / print URL the list would hide
  - [ ] Every transition reachable from a real control; every control has a real handler
  - [ ] Deletion guards in policy + query
  - [ ] Tests: happy path end-to-end, every backward branch, every visibility × role cell,
        bulk actions, deletion guards
  - [ ] Sensitive fields encrypted where required
  - [ ] Destructive jobs re-verify counts server-side

## 6. Authentication (org standard)

Follow `authentication-implementation-guide.md` (keep a copy in the repo):
external Auth API → user-id API → local `users` row required (local row IS the
authorization) → cache lockout (3 strikes / 15 min) → every attempt in `access_logs` →
optional Turnstile gated by config.

Project-specific decisions:
- Authorization strategy: {{role enum / module JSON / boolean columns}} — column(s): {{…}}
- Local `users.id` must match the external user API's id — user rows are created via the
  User Management panel (guide Part 2) with real ids; seeded dev ids are placeholders.
- **`AUTH_FAKE` dev mode** (the provider API is offline outside office hours): config-gated
  short-circuit that signs in any local `users.email` with any password; guarded by
  `flag && ! app()->isProduction()`; lockout/logging/authorization still run; pinned off in
  the test config. {{Keep? yes/no}}
- Turnstile: {{on/off; keys owner}}

## 7. Working agreements

- One checklist step per session; each step ends navigable/testable. Verify mechanically
  (hit routes, run suite) before calling it done.
- Commit per step: `feat: Step N - what it delivered` (scaffold) / `feat: real-build Step N - …`
  (or "Phase" for the big chunks — pick one and stay consistent). Fixes: `fix: …`.
- Update this plan **and** the checklist the moment a step lands or deviates.
- Check current file state before editing — files change by hand between sessions.
- Report reality: failing tests and skipped steps get said out loud, not papered over.

## 8. Environment gotchas

{{Copy the relevant block from DEVELOPMENT_PLAYBOOK.md §gotchas and add project-specific ones
  discovered along the way — stale route caches, encoding traps, locked files, provider API
  quirks (e.g. auth API offline after {{TIME}}), etc.}}

---

*Deliberately deferred (list what is explicitly out of scope for the scaffold or MVP so the
agent doesn't wander into it): {{…}}*
