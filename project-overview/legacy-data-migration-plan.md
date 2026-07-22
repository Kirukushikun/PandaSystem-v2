# PANDA v1 → v2 Data Migration Plan

**Status as of 2026-07-20:** extraction tool built and verified working against v1's live
database (practice run). Mapping decisions not yet made. Importer (the v2-side half) not yet
built — waiting on the mapping file below being filled in.

**Goal:** bring v1's historical PANs (and reference data) into v2 as real, importable records —
not just an archive — so the workflow history isn't lost when v1 is retired.

---

## The two-repo architecture (why it's split this way)

- **`PandaSystem`** (v1) — has the export command. It runs *there* because v1 encrypted several
  fields with its own `APP_KEY`, and decryption can only happen inside the app that holds that
  key. v1's own Eloquent models already auto-decrypt on read (`getAttribute()` overrides), so
  the export command just reads through the models and gets plaintext back for free.
- **`PandaSystem-v2`** (this repo) — will get an *importer* command (not built yet) that reads
  the plain JSON v1 exported and creates real `pan_requests`/`pan_forms`/`pan_returns` rows.
  v2 never needs v1's `APP_KEY` — by the time data reaches v2, it's already plaintext JSON.

## Step 1 — Export (done, in v1)

Command: `php artisan panda:export-for-v2` (run from `PandaSystem/`, not this repo).
Writes to `PandaSystem/storage/app/legacy-export/`:

| File | Contents |
|---|---|
| `departments.json` | v1's 9 departments |
| `employees.json` | v1's ~500 employees, keyed by `company_id` (matches v2's `employee_no`) |
| `users.json` | v1's ~57 accounts (no email in v1 — reference only, not for account creation) |
| `requests.json` | Every PAN request, joined with its prepared form details and correction/return log, fully decrypted |
| `MAPPING_NEEDED.md` | Auto-generated checklist — see Step 2 |

**Verified working:** ran against the live v1 DB, zero leftover encrypted blobs, spot-checked
fields (`justification`, `requested_by`, `action_reference_data`) all came out correctly
decrypted. `action_reference_data` already matches v2's `{field, from, to}` shape exactly —
no conversion needed there.

**Re-run on deployment day:** the command is safe to re-run any time. Plan is: load the *real*
current v1 data into the local v1 database on deployment day, re-run the export, and treat
today's run as a practice pass only. **Re-check `MAPPING_NEEDED.md` after that re-run** — it's
regenerated fresh each time and may surface new department/status/action-type values not seen
today.

## Step 2 — Mapping decisions (not started — this is on the user)

File: `PandaSystem/storage/app/legacy-export/MAPPING_NEEDED.md`

Three checklists to fill in (third one not yet added to the generator — see Known Gaps):
1. **Departments** — old name → new v2 department. Most are 1:1 (`Feedmill → Feedmill`).
2. **(request_status, current_handler) pairs** — each old combination → one v2 `PanStatus`.
3. **Action types** *(TODO: add this section to the export command before next run)*.

## Risk checklist — what's handled vs. what needs a decision

| # | Risk | Status | Resolution |
|---|---|---|---|
| 1 | v1 fields were encrypted | ✅ Solved | Decrypted at export time inside v1 |
| 2 | `action_reference_data` shape mismatch | ✅ Solved | Already matches v2's shape exactly |
| 3 | Confidentiality tag wording | ✅ Solved | `tarlac`/`manila` already match; blanks → untagged |
| 4 | One v1 department (Financial Ops) split into 3 in v2 | 🔧 Planned | Resolve **per employee**: look up each PAN's employee in v2's *current* roster and use their real department, instead of guessing from the old department name |
| 5 | Ghost department "Production" (no longer exists) | 🔧 Planned | Same fix as #4 — resolved by employee, not by old label |
| 6 | Old actor ids (`requestor_id`/`divisionhead_id`/`hr_id`/`approver_id`) don't match v2 ids | 🔧 Planned | Look up the name in v1's `users.json` and store it as a plain historical note, not a live account link |
| 7 | v1 has no `previous_pan_id` chain concept | 🔧 Planned | Auto-rebuild by sorting each employee's PANs chronologically |
| 8 | `employment_status` wording differs slightly ("Project-Based" vs `ProjectBased`) | 🔧 Planned | Trivial normalization |
| 9 | Old status names → v2's `PanStatus` | ❓ Needs input | Checklist #2 in `MAPPING_NEEDED.md` |
| 10 | Old action-type names → v2's `ActionType` | ❓ Needs input | Checklist #3 — not yet generated, add to export command |
| 11 | Employees who left the company before v2 existed | ❓ Needs input | Can't auto-resolve via #4 (no current record to look up) — expect a short manual list, not all 841 requests |
| 12 | Attached PDF files aren't in the SQL dump | ⚠️ Open question | Only the file *name/path* is in the DB — actual files live on v1's server disk separately. Need to confirm that folder is still reachable if old attachments should stay viewable in v2 |
| 13 | Old PAN reference numbers look different (`PAN-BFC-2025-0430` vs `PAN-2026-00001`) | 📝 Recommendation given | Keep old numbers as-is on imported records — don't renumber (people may already have printed copies) |

## Known gaps to close before building the importer

- [ ] Add an **action-type checklist section** to `ExportForV2.php`'s `printMappingSummary()`,
      same pattern as the department/status sections.
- [ ] User fills in `MAPPING_NEEDED.md` (all 3 sections once #1 above is done).
- [ ] Confirm whether v1's attachment files (physical PDFs) are still accessible.
- [ ] Decide the short list of departed-employees' department (risk #11).

## Step 3 — Importer (not built yet)

Once the mapping file is filled in, build a v2-side Artisan command that reads the four JSON
files + the completed mapping and creates real `pan_requests`/`pan_forms`/`pan_returns` rows.
Should run against a copy of the v2 database first, not production, so results can be eyeballed
before trusting them as real history.
