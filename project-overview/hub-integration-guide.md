# Access Hub — Integration Guide for Individual Systems

Read this before adding hub sync to a system. It describes **what the hub gives you and what you must do with it**, not how to write it. Every system stores access differently — enum `role` column, JSON `access` column, something else entirely — so the translation is yours to write. The contract below is what stays the same.

> Mirrors `docs/api.md` in the hub repo, which is the source of truth if this guide and that ever disagree.

---

## 1. Ground rule: this feature is optional

The system must keep working perfectly with **no hub connection at all**. That is the normal state, not a degraded one.

- Login is untouched. The hub is never called during login. It does not exist as far as authentication is concerned.
- The existing access panel keeps working. Admin ticks boxes by hand, same as always.
- If the hub is down, unreachable, or was never enrolled, the panel shows what it always showed. At most, the sync button is hidden or shows an error when clicked.

If your implementation can break the access panel when the hub is unavailable, it is wrong. Every hub call is wrapped, timed out, and failure means "no sync available right now", never a broken page.

---

## 2. What the hub is

One central list of people and their **org role(s)**. Nothing more.

The roles are fixed and there are only four:

- `manager` — manager / supervisor
- `division_head`
- `vp`
- `user`

**A person can hold more than one of these at once** (e.g. both `manager` and `division_head`). It is still a fixed four-value vocabulary, not a per-project permission list — see §3.

The hub does not know your module codes, your enum values, or your permission names. It says "Maria is a division head" (and maybe also a manager) and stops. What that means inside your system is your business.

---

## 3. What you receive

`GET /api/v1/grants` returns:

```json
{
  "generated_at": "2026-09-07T10:00:00Z",
  "people": [
    {
      "user_id": 412,
      "name": "Maria Santos",
      "email": "m.santos@example.org",
      "farm": "Farm A",
      "department": "Finance",
      "position": "Senior Accountant",
      "roles": ["division_head", "manager"],
      "active": true
    },
    {
      "user_id": 87,
      "name": "Juan Cruz",
      "email": "j.cruz@example.org",
      "farm": "Farm B",
      "department": "Operations",
      "position": null,
      "roles": ["manager"],
      "active": true
    }
  ]
}
```

Notes:

- `user_id` is the same numeric ID your `users` table already keys on. It is already decrypted — you do not decrypt anything here.
- **`roles` is always an array with one or more values.** Do not assume exactly one — a person with two roles ships as `["division_head", "manager"]`, not two separate records. Your mapping step must iterate the array (see §4.2), not read `roles[0]` and stop.
- `active: false` means the person was removed at the hub. You must handle these — they are the revoke signal. Do not filter them out on arrival.
- Treat unknown role strings as "skip that string and warn", not as a crash — and not as "skip the whole person" if at least one of their other roles is recognized.

### Farm, department, position

These three are **display only, with no bearing on access**. Access is decided by `roles` alone. Never map from position, farm, or department, and never gate anything on them.

They are here because they make people identifiable. In a preview listing thirty names, "Juan Cruz — Farm B, Operations" tells the admin who they are approving; "Juan Cruz" does not.

- **All three can be null**, and often will be. The hub maintains them by hand, so they lag reality. Never make anything required on them and never let a null one break a sync.
- **Do not confuse `position` with `role`.** Position is the job title, e.g. "Farm Supervisor". Role is the access level. Two people with the same position can have different roles, and that is intentional.

Use them for:

- Showing context in the preview.
- Grouping or filtering the preview list — useful when a sync brings in a lot of people at once.
- Displaying in your own access panel, if you want the extra context there too.

You may store them on your local row if there is somewhere natural to put them, refreshing on each sync like any other hub-owned field. This is optional — the feature works fine without.

---

## 4. What you must build

### 4.1 A `source` column on `users`

Add a column marking where each row came from — `hub` or `manual`. Default existing rows to `manual`.

This is the single most important piece. **Sync only ever touches rows it owns.** Your one-off special access people, your seeded admin, anyone added by hand — the sync must never modify or delete them, and this column is what guarantees it.

### 4.2 A role mapping config

A file committed to git that translates hub roles into whatever your system actually stores. Set it once when you build the system.

**A person can carry more than one hub role now — decide up front how multiple roles combine**, and write that as code once, not as a judgment call each sync:

- *JSON/bitmask access column* → union every mapped permission across all of the person's roles.
- *Single enum role column* → pick the highest-privilege role present (define your own ordering, e.g. `vp > division_head > manager > user`) and map only that one.

Shape the mapping table itself however fits your access model. Two examples:

```php
// System using a JSON access column — union across all roles a person holds
'division_head' => ['HRA' => true, 'HRP' => true, 'REP' => true],
'manager'       => ['HRA' => true],
'vp'            => ['HRA' => true, 'REP' => true],
'user'          => [],
```

```php
// System using an enum role column — apply only to the person's highest hub role
'division_head' => 'admin',
'manager'        => 'reviewer',
'vp'             => 'vp',
'user'           => 'reviewer',
```

Keep it dumb and readable — a flat array someone can understand in ten seconds. **Do not put logic in it.** No conditionals, no callbacks, no computed values. If you find yourself wanting logic here, the mapping is trying to solve a problem that belongs somewhere else — the union/highest-role decision above is the one exception, and it lives in the code that *reads* this table, not in the table.

Any hub role missing from this file means "this system does not grant anything for that role" — skip it, do not error. **This is a silent no-op, not a warning** — if the hub ever renames or adds a role and this file isn't updated, people quietly stop getting access instead of the sync failing loudly. Re-check this file whenever `docs/api.md` in the hub repo changes its role list (it changed once already: `requestor` was renamed to `manager` in September 2026 — any mapping still keyed on `requestor` has been silently granting nothing since).

### 4.3 Connection storage

One row in your own database holding the hub connection:

- `client_id`
- `client_secret` (encrypted at rest)
- `last_synced_at`

**Not in `.env`.** The whole point is that nobody carries secrets between environments. The only thing in config is the hub's base URL, which is not a secret and lives in a committed config file.

### 4.4 The enrollment step

The first time an admin clicks "Sync from hub" and no connection exists:

1. Show a modal asking for the connection code.
2. Send the code, your project key, and your environment name to the hub's enroll endpoint.
3. Store the returned `client_id` and `client_secret` in the connection row.
4. Proceed with the sync.

After that, the modal never appears again. A redeploy that keeps the database keeps the connection.

Also provide a **"reset connection"** action in the panel, so a bad or revoked connection is fixable from the UI instead of a database edit.

### 4.5 The preview

**Never write directly from a sync.** Fetch, compare against your local `users` table, and show the admin what would change. Split into three groups so it is obvious what is being looked at:

| Group | Meaning | Default |
|---|---|---|
| **New** | Hub has them, this system does not | Safe — tick all |
| **Changed** | Their access here would be different (including a roles-array change), or they went inactive | Needs eyes |
| **Local only** | Rows this system has that the hub does not know about | **Never touched.** Shown for reassurance only |

The admin ticks what to apply and confirms. Add "apply all" once the flow is trusted.

Two things this prevents: blindly nuking locally-granted users, and an admin discovering after the fact that access changed everywhere.

### 4.6 The apply step

For each confirmed row, reuse the assign and revoke logic the panel already has. Do not write a second path into `users` — go through the existing one so validation, logging, and any side effects stay consistent.

- **New** — create the row, mark `source = hub`.
- **Changed** — update, only if `source = hub`.
- **Inactive at hub** — revoke, only if `source = hub`. Prefer soft delete if the table supports it, so a re-grant restores the row instead of rebuilding it.
- **Local only** — do nothing, ever.

Set the user ID explicitly. If your `users` model guards `id`, mass assignment silently drops it and you get an auto-increment ID that will not match what the login flow looks up.

### 4.7 The staleness line

Stamp `last_synced_at` **on a successful fetch**, not on apply. A sync that finds nothing to change still means the system is current.

Show one line in the panel header: "Last synced 12 days ago". Make it turn amber past 30 days — a gray timestamp gets ignored, a colored one gets noticed.

This is the mitigation for the known lag: someone removed at the hub keeps access here until this system syncs. The line makes that visible.

---

## 5. Build order

Build against a **hand-written fake response** first, before the hub exists or is reachable. Paste the JSON from section 3 into a fixture and work from that.

1. `source` column migration, existing rows default to `manual`.
2. Mapping config.
3. Compare logic — fake response in, three groups out. This is the part worth getting right.
4. Preview UI.
5. Apply, reusing existing assign/revoke.
6. Connection storage + enrollment modal.
7. Real fetch.
8. Staleness line.

Steps 1 to 5 are the whole feature. The network part is trivial by comparison.

**When the real hub exists, rebuild your test fixture from a pasted real response.** Do not keep the hand-written one. A fixture built from your own assumptions can only prove the code agrees with itself — it is blind to a contract mismatch. This has bitten this codebase before: a fully green test suite while every name would have rendered blank in production.

---

## 6. Failure handling

| Situation | Behaviour |
|---|---|
| Never enrolled | Panel works normally. Sync button offers enrollment. |
| Hub unreachable | Timeout on connect and read. Show "could not reach hub". Panel otherwise normal. |
| Bad or revoked credentials | Show a clear message and offer "reset connection". Do not silently retry. |
| Unknown role in the `roles` array | Skip that string, log a warning, still apply the person's other recognized roles. |
| Null farm / department / position | Normal. Display a dash or leave blank. Never block a sync on it. |
| Empty response | Show "nothing to sync". Never interpret empty as "revoke everyone". |

Log failures. A silent catch makes a real outage invisible.

---

## 7. Checklist

- [ ] `source` column added, existing rows set to `manual`
- [ ] Role mapping config committed, flat and logic-free — maps from `roles` (an array) only, never from position; keyed on the **current** four role names (`manager`, `division_head`, `vp`, `user`)
- [ ] Mapping decides up front how multiple roles on one person combine (union, or highest-wins) — and that logic lives in the reader, not the table
- [ ] Farm / department / position shown in the preview for context, tolerant of nulls
- [ ] Connection row in the database — client ID, encrypted secret, `last_synced_at`
- [ ] Hub base URL in committed config, no secrets in `.env`
- [ ] Enrollment modal on first sync
- [ ] "Reset connection" action available in the panel
- [ ] Compare produces three groups: new, changed, local-only
- [ ] Preview shown before any write, admin confirms
- [ ] Apply reuses existing assign/revoke logic
- [ ] Sync never modifies or deletes `source = manual` rows
- [ ] User ID set explicitly on create, not mass assigned
- [ ] `last_synced_at` stamped on fetch, displayed in panel, amber past 30 days
- [ ] Every hub call has connect and read timeouts
- [ ] Panel verified working with the hub switched off entirely
- [ ] Test fixture rebuilt from a real hub response once the hub exists
