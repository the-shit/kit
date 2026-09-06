# SPEC: Cut toolkit — eyes / hands / prove (BlenderRun)

Status: **DRAFT**  
Repo: `the-shit/kit`  
Issue: [kit#16](https://github.com/the-shit/kit/issues/16)  
Slug: `cut-toolkit`  
Depends on: [kit#10](https://github.com/the-shit/kit/issues/10) factory-seats  
Related: [kit#6](https://github.com/the-shit/kit/issues/6) ImageToMesh

**Lie:** Kit does 3D modeling. Today Cut cannot see or touch meshes. This SPEC makes the lie true for one closed loop: see → act → measure → retry.

**Seat:** Cut (smart brain configurable; Astra via OpenRouter for now). Look = honesty. Ship = PR/CI, never merge. No LLM on `/api/assign`.

**Number:** LookCompare ≥ pitcher threshold on four views AND ValidateGLB green, within N turns (default 5).

## Locked

### Eyes
- `RenderViews` — GLB stills for `front|side|back|ride`
- Vision critique on those stills (no filename guessing)
- `LookCompare` — fail ⇒ `fail`, never `visual_ok`

### Hands
- `BlenderRun` — allowlisted bpy only (load/save GLB, transform, boolean, remesh/decimate, weld, simple materials). No free shell/network.
- `ImageToMesh` — optional blockout (kit#6 backends)
- `MeshOps` — cheap non-bpy repairs when enough

### Prove
- `ValidateGLB` — manifold, poly budget, units, LODs
- Turn cap with honest fail
- Ship only after prove; hallway still `does not merge`

### Brain / host
- `KIT_CUT_MODEL` config; Look/Ship stay cheap
- Blender on Loki (Thor borrow when idle); no cloud burst hardcoded in this SPEC

## Acceptance
- [ ] This file at `specs/cut-toolkit/spec.md` with `spec:` on kit#16
- [ ] Fake CutLoop CI receipt (mocked tools), no GPU in CI
- [ ] factory-seats Cut lock updated to BlenderRun allowlist after APPROVE
- [ ] Look honesty + Ship no-merge unchanged
- [ ] Pint + CutToolkit tests green

## Non-goals
Unreal, free bpy/shell, merge automation, multitenancy, frontier Look/Ship, TRELLIS training (kit#6).

## After APPROVE
Land tools behind flag → dogfood one asset on Loki → then attach Astra brain.