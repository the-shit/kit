# SPEC: Factory 3 seats — Cut / Look / Ship

Status: **DRAFT**  
Repo: `the-shit/kit`  
Issue: [kit#10](https://github.com/the-shit/kit/issues/10)  
Slug: `factory-seats`

**Story:** Kit chairs the factory. Asgard already has `/api/assign` (no LLM, board row + `DispatchKitWork`). The job is still a hallway ping. Three mortal seats should take the hop: Cut (one mesh slice), Look (stills + honest fail), Ship (PR + babysit CI, never merge). Host-side `~/.cache/kit/factory-queue.jsonl` was a stopgap. After this SPEC, assign owns the queue.

Feel / Bench identities stay. They are not these seats. Catalog rider is not this issue. Image-to-3D is [kit#6](https://github.com/the-shit/kit/issues/6). [kit#11](https://github.com/the-shit/kit/issues/11) stays `park`.

## Locked

- **No LLM on assign.** `POST /api/assign` SHALL NOT construct `KitAgent` or call a provider. Mouth stays `POST /api/ask`.
- **Kinds:** `cut` | `look` | `ship`. If `kind` is one of those and `chair` is omitted, `chair` SHALL equal `kind`.
- **Chairs:** existing `kit` | `bench` | `feel`, plus `cut` | `look` | `ship`. Unknown chair still folds to `kit` (today’s behavior).
- **One queue:** Laravel `kit` queue + `board.json`. Do **not** read or write `~/.cache/kit/factory-queue.jsonl` from PHP in this slice. Host script can keep enqueueing; Asgard assign is the product door.
- **Board:** assign writes `lifecycle=queued`, `owner=<chair>`. `DispatchKitWork` then sets `lifecycle=wip` and posts the hallway. Do not DM Jordan. Skip Mattermost when hallway is the Jordan DM (already tested).
- **Cut:** receipt is “one slice queued”. SHALL NOT spawn Blender, bpy, or blender-mcp. `BlenderRun` is a later SPEC.
- **Look:** if `config('kit.look_report')` is a readable JSON object, the hallway message SHALL include an honest summary. If any region/check failed, the message SHALL contain `fail` and SHALL NOT contain `visual_ok`. If the file is missing or unreadable, the message SHALL say `look report missing` and SHALL NOT contain `visual_ok`. SHALL NOT run Playwright.
- **Ship:** hallway message SHALL contain `does not merge`. SHALL NOT call `gh pr merge`, SHALL NOT hit GitHub merge APIs, SHALL NOT set `lifecycle=live`.
- **Catalog rider is not this issue.** Do not special-case `rider` / `hero-ebike` beyond today’s board-id heuristic.
- **Files under ~300 lines.** No Filament, no Horizon, no 10 empty agents.

## Files

| Path | Job |
|---|---|
| `app/Http/Controllers/AssignController.php` | Accept cut/look/ship chairs; default chair from kind |
| `app/Jobs/DispatchKitWork.php` | wip + look honesty + ship “does not merge” |
| `app/Factory/LookHonesty.php` | Pure helper: missing / fail / pass from a look-report path |
| `tests/Feature/FactorySeatsTest.php` | Assign + job receipts for the three seats |
| `README.md` | Document kinds cut/look/ship on `/api/assign` |
| `specs/factory-seats/spec.md` | This contract |

## Acceptance

- [ ] `POST /api/assign` `{kind: cut}` → 202, `chair=cut`, board owner `cut`, lifecycle `queued` before the job runs
- [ ] Job handle on cut → board lifecycle `wip`; hallway contains `chair=cut`; no Blender argv
- [ ] Job handle on look with missing report → hallway contains `look report missing`; `visual_ok` absent
- [ ] Job handle on look with a JSON fixture where one check failed → hallway contains `fail`; `visual_ok` absent
- [ ] Job handle on ship → hallway contains `does not merge`; board lifecycle is not `live`
- [ ] Existing AssignTest cases still pass (`kit`/`bench`/`feel`, rider board id, empty 422, no LLM)
- [ ] `rg -n 'gh pr merge|visual_ok' app/` is empty except LookHonesty *rejecting* `visual_ok` on fail/missing
- [ ] `php artisan test --filter=AssignTest\\|FactorySeatsTest` green
- [ ] `vendor/bin/pint --test` green on touched PHP

## Non-goals

- `BlenderRun` / bpy on Loki
- Playwright `LookCompare` runner
- GitHub merge, restack, or PR babysit implementation
- Hiring Night / Gate / Print / Clip / Lot / Horde
- TRELLIS / Hunyuan ([kit#6](https://github.com/the-shit/kit/issues/6))
- Multitenancy assertions ([kit#11](https://github.com/the-shit/kit/issues/11) is park)
- Importing the host jsonl queue
- Mattermost as the worker (hallway is a receipt)

## After APPROVE

1. Bind this SPEC into `the-shit/kit`, land it, put `spec: specs/factory-seats/spec.md` on [kit#10](https://github.com/the-shit/kit/issues/10).
2. `/local-worker` against `repos/kit` with `jobs/factory-seats.json`. Grade the receipt.
3. Jordan merges. Do not label `night-ready` until that SPEC is on GitHub and he wants it picked up.
