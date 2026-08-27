# Agent prompt: factory-seats

Implement `specs/factory-seats/spec.md` in this repo. PHP 8.4, Pest, Laravel. Do not merge. Do not start an LLM. Do not run Blender or Playwright.

## Do

1. `AssignController`: chairs `kit|bench|feel|cut|look|ship`. If `kind` is `cut|look|ship` and chair omitted, chair = kind. Unknown chair still becomes `kit`.
2. `DispatchKitWork`: after the existing hallway skip-if-DM rule, upsert the board row to `lifecycle=wip`. Cut message includes `chair=cut`. Ship message includes `does not merge`. Look uses `App\Factory\LookHonesty` on `config('kit.look_report')`.
3. `LookHonesty`: static or small class. Missing/unreadable → text contains `look report missing`, never `visual_ok`. JSON with any failed check → text contains `fail`, never `visual_ok`. Keep the class under 80 lines.
4. `tests/Feature/FactorySeatsTest.php` covering the SPEC acceptance bullets. Reuse AssignTest’s board-path + Mattermost Http fake pattern.
5. One README line: assign `kind` may be `cut|look|ship`.

## Do not

- Edit Feel/Bench identities, prompts, or KitAgent tools.
- Touch `visual_ok` as a success string anywhere in `app/`.
- Call `gh`, GitHub merge, bpy, or Playwright.
- Import `~/.cache/kit/factory-queue.jsonl`.
- Add Horizon, Filament, or new HTTP routes.
- Rewrite AssignTest.

## Verify

```
php artisan test --filter='AssignTest|FactorySeatsTest'
vendor/bin/pint --dirty
```

Stop when those are green and the SPEC acceptance list is true. Same error twice → blocked receipt, do not invent a fourth seat.
