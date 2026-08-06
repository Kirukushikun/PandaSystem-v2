# Legacy Peek Tool — v1/v2 Live Comparison

**Status as of 2026-08-06:** planning only — nothing built yet on either side. This doc is the
spec to hand to a `PandaSystem` (v1) session, mirroring how `legacy-data-migration-plan.md`
coordinated the export command.

**Not the [v1→v2 migration importer](legacy-data-migration-plan.md).** That's a one-time batch
job that runs once at go-live. This is a standing developer tool for the transition period
*before* go-live — both systems are live and being used in parallel, and it's easy for v2's
carry-over/population logic to silently diverge from what v1 actually has for a given employee.
The tool exists to catch that without alt-tabbing between two apps.

## The itch this scratches

Concrete example: HR Preparation in v2 auto-populates a PAN's "From" values (via
`previous_pan_id` carry-over). During the transition, some employees' PAN history still lives
only in v1. Right now the only way to check "does what v2 just populated actually match what v1
has on file" is to log into v1 separately and look it up by hand. The tool should let a dev pull
up v1's live value for that employee inline, right where v2 is deciding what to populate.

More generally: "does employee X exist in v1, and if so what's their latest PAN status there" —
a quick existence/status check, not tied to any specific screen.

## Architecture: live API bridge

v1 stays the source of truth for its own encrypted data — it already decrypts on read via its
Eloquent models' `getAttribute()` overrides (same mechanism `panda:export-for-v2` relies on), so
v2 never needs v1's `APP_KEY`. v2 calls v1 over HTTP, on demand, only when a dev opens the peek
panel — no polling, no background sync, no new persistent state in v2.

```
v2 LegacyPeekService ──HTTP, x-api-key──▶ v1 GET /api/legacy-peek/employee/{company_id}
                                                │ v1 decrypts w/ its own APP_KEY
                                                ▼
                                          JSON snapshot (see contract below)
```

Same shape as the existing `UserDirectoryService` → external User Listing API integration in
this repo (`app/Services/UserDirectoryService.php`) — reuse that pattern: `Http::withHeaders`,
short timeout, `Cache::remember` briefly, fail soft (log + return null, never break the v2 screen
the dev is actually working on).

## What v1 needs to build

A new route, `routes/api.php` in `PandaSystem`, protected by a shared secret header (same
`x-api-key` convention v1 already exposes for its own APIs) — **not** the org's real auth system,
this is dev-tool-to-dev-tool, so a static key in both `.env` files is enough:

```
GET /api/legacy-peek/employee/{company_id}
Header: x-api-key: <LEGACY_PEEK_API_KEY, shared secret, new env var on both sides>
```

Suggested response contract:

```jsonc
{
  "found": true,
  "employee": {
    "company_id": "10234",
    "name": "Juan Dela Cruz",
    "department": "Feedmill",       // v1's label, pre-mapping — see risk #4/#5 in the migration plan
    "position": "...",
    "employment_status": "Regular"
  },
  "latest_pan": {
    "pan_number": "PAN-BFC-2025-0430",
    "status": "Filed",              // v1's status label, pre-mapping — see risk #9
    "action_reference": [ { "field": "section", "from": "...", "to": "..." }, ... ],
    "filed_at": "2025-04-30T00:00:00+08:00"
  },
  "recent_pans": [
    { "pan_number": "...", "status": "...", "created_at": "..." }
    // last ~5, newest first — enough to eyeball a carry-over chain without a full history dump
  ],
  "checked_at": "2026-08-06T14:32:00+08:00"
}
```

`"found": false` (with `employee`/`latest_pan`/`recent_pans` omitted) when `company_id` doesn't
exist in v1 — this is itself a useful answer ("nope, this one's v2-only, don't bother checking").

No write endpoints. This is read-only, peek-only, by construction.

## What v2 builds (this repo)

- `config/services.php` — new `legacy_v1` block (`base_uri`, `key`), same shape as the existing
  `user_api` block.
- `.env.example` — `LEGACY_V1_BASE_URI`, `LEGACY_V1_API_KEY` (blank by default — tool is inert
  until configured, same env-gated-optional pattern as Google Drive backups).
- `App\Services\LegacyPeekService::forEmployee(string $companyId): ?array` — mirrors
  `UserDirectoryService`: `Http::withHeaders(['x-api-key' => ...])`, short timeout/connectTimeout,
  `Cache::remember` for ~30s (same reasoning as the backups summary cache — avoid hammering v1 on
  every Livewire round-trip), returns `null` on any failure (unreachable, non-2xx, `found: false`)
  and logs a warning rather than surfacing an error to whoever's using the panel.
- UI: a collapsible "Compare to v1" panel wired into HR Preparation's PAN form, showing v1's
  `latest_pan.action_reference`/status next to what v2 just auto-populated, plus a standalone
  employee lookup (Admin or Employee Directory) for the general existence/status check. Visual
  treatment: reuse the existing status-pill/tag-dot components so it reads as "just another PANDA
  panel," not a bolted-on debug widget.
- **Access:** hard-pinned to `auth()->id() === 61` (Iverson Guno) — not `is_admin`, not a new
  permission column. Deliberately a single-user check, not a role: this is a personal dev cheat
  code for the transition period, invisible to the supervisor account and everyone else,
  including other admins. Meant to be easy to delete outright later (one `@if` in the Blade, one
  guard clause in the service/route) if it never gets removed for real — no migration, no
  permission bit to clean up.

## Open questions before building

- [ ] Confirm `LEGACY_PEEK_API_KEY` naming/value — needs to be set identically in both repos'
      `.env`, real value chosen when this is actually wired up (not committed anywhere).
- [ ] Confirm v1 is reachable from v2's runtime environment (same Docker network / same server /
      public URL?) — determines whether `LEGACY_V1_BASE_URI` is an internal or external address.
- [x] Access scope — hard-pinned to user id 61 only, not a role. See note above.
- [ ] Decide whether `recent_pans` needs more than 5 entries, or whether the full history belongs
      in a separate "expand" call instead of one heavy response.
