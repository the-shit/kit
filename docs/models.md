# Kit model routing

Official xAI list prices (per 1M tokens unless noted). Long-context row is the ≥200k prompt tier.

## Text

| Model | Use on the bench | In / cached / out | ≥200k |
|---|---|---|---|
| `grok-4.6` | Rig, sockets, “is this sit pose legal”, hard calls | $2 / $0.50 / $6 | $4 / $1 / $12 |
| `grok-4.5` | Same class, previous flagship | $2 / $0.30 / $6 | $4 / $0.60 / $12 |
| `grok-4.3` | Cheap spec / transcript takeaway | $1.25 / $0.20 / $2.50 | $2.50 / $0.40 / $5 |
| `grok-4.20-0309-reasoning` | Same rate as 4.3 | $1.25 / $0.20 / $2.50 | $2.50 / $0.40 / $5 |
| `grok-4.20-0309-non-reasoning` | Fast lists, no think | $1.25 / $0.20 / $2.50 | $2.50 / $0.40 / $5 |
| `grok-4.20-multi-agent-0309` | Parallel part briefs | $1.25 / $0.20 / $2.50 | $2.50 / $0.40 / $5 |
| `grok-build-0.1` | bpy / TS patches | $1.00 / $0.20 / $2.00 | $2.00 / $0.40 / $4 |

Default KitAgent: **xai / grok-4.6**. Drafts: 4.3 or build-0.1.

## Imagine

| Model | Use | Price |
|---|---|---|
| `grok-imagine-image` | Quick albedo / part sketch | $0.02 / image ($0.002 / input img) |
| `grok-imagine-image-2.0` | Hero stills, turnarounds | $0.04 / image (docs also list $0.05 1K / $0.07 2K) |
| `grok-imagine-image-quality` | Clothes + texture lock | $0.05 1K / $0.07 2K ($0.01 / input img) |
| `grok-imagine-video` | Pedal / swing motion ref | $0.05/s 480p, $0.07/s 720p |
| `grok-imagine-video-1.5` | Cleaner motion ref | $0.08/s |

Imagine does not ship a GLB. It is the photo. Blender still owns meters, origin, sockets, solidify+shrinkwrap (clothes slightly bigger than the body).

## Studio loop

1. **4.3 / build-0.1** — write the part brief (socket, meters, garment offset).
2. **Imagine image-2.0 / quality** — front / side / 3q stills. Same subject.
3. **4.6** — turn the still + brief into bpy (or reject: “watch my mouse”).
4. **Imagine video** — 6s pedal/swing only when we need timing, not every export.
5. Look-compare vs refs. Catalog id only.

Source: https://docs.x.ai/developers/models
