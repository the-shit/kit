# Mesa Studio floor

Kit chairs the factory. Lexi chairs the company. Neither clones the other.

Jordan’s lock: ~10 specialists on Loki that collaborate. Kit does not implement new game dynamics. When stuck, Mattermost. Works when he is away.

## Company shape

The product is **photo → catalog-ready GLB** (look report included). bikes-v2 is customer zero and the Steam demo. Agents are the shop floor, not the SKU. Do not sell an asset HTTP API until the factory is boring.

## Ten Loki seats

Hire in this order. A seat is a `laravel/ai` agent with 4–8 tools, one queue, one Mattermost handle. Not a Filament app.

| # | Seat | Owns | Does not own |
|---|---|---|---|
| 1 | **Kit** | Catalog, factory lead, mouth | Jordan’s life, combat internals |
| 2 | **Night** | Heartbeat, log parse, morning digest | Gameplay |
| 3 | **Gate** | `npm run quality`, “red CI blocks” | Taste |
| 4 | **Look** | Playwright look-compare | Authoring meshes |
| 5 | **Bench** | Blender/bpy on Loki | Catalog ids |
| 6 | **Print** | Imagine stills / albedo / clothes refs | Shipping GLBs |
| 7 | **Clip** | Sit, pedal, swing, hop clips | Physics |
| 8 | **Lot** | 7620, palms, lot signs | OSM crawl at large |
| 9 | **Horde** | Shambler presentation | AI spawn rates |
| 10 | **Feel** | Wheels, lean, chase-cam read | Bike physics constants |

Feel/Horde/Lot *brief* playables. They do not edit `src/bike/physics.ts` or `src/combat/*` unless Gate + Kit say the brief is graphical.

## Odin (mouth, memory, watch)

| Service | Why Odin |
|---|---|
| Lexi | CoS, taste, Jordan memory |
| Mattermost | The hallway |
| Qdrant `knowledge_jordan` | Life. Kit does not rewrite it |
| n8n | Phone webhooks, night timers |
| Loki watchdog | Already `kit-watch-loki` |
| Forge | Body lane |

| Stays on Loki |
|---|
| Blender, vite, Playwright, GLBs, `catalog.json` |
| KitAgent + future seats |
| Factory vectors `knowledge_kilt` (first copy) |
| Local embeddings (bge) when the 3060 is free |

Odin feeling slow from the work laptop is expected: Tailscale + a small box + Qdrant/Lexi over WAN. Do not pull factory meshes through Odin. Work laptop → Loki Tailscale for studio (`:5173`, `:8788`). Lexi stays on Odin.

## Local AI

- Loki 3060 8 GB: embeddings + one small model. Unload Ollama when Blender needs VRAM.
- Framework 128 GB UMA (when it lands): Hunyuan/TRELLIS + a 70B Kit + Blender HIP.
- Night parse does not need a 70B. Regex + 4.3 is enough.

## Night loop

1. `journalctl` for kitd, kit-queue, vite, blender, Night.
2. Cluster repeats. Write `storage/app/kit/night.json`.
3. Embed errors into `knowledge_kilt`.
4. 07:00 Phoenix: one Mattermost post. Jordan only if kitd/vite down >15 min (Lexi already owns that alert).

## Stuck

1. Retry once.
2. Ask Kit (catalog / factory) or the seat that owns the file.
3. Ask Lexi if it is taste, Jordan-life, or money.
4. Post in a **channel**, not the Jordan DM: blocker, last tool, what you will try at 07:00.
5. Wake Jordan only for policy / spend / “the ride feels wrong.”

## Do not spawn tonight

Do not stand up 10 empty agents. Next three seats after Kit: **Night**, **Gate**, **Look**. They make the other seven safe to leave running. Lexi MCP 500’d when Kit asked; try her again before hiring.
