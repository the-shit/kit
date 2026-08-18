# Kit

Bikes v2 asset factory agent. Lives on Loki. Sibling to Lexi — not a class inside her process.

SDK: **[laravel/ai](https://laravel.com/docs/ai-sdk)** (`BaseAgent` + tools). Webhook + CLI. Not a Filament/Horizon app.

```
GET  /health
POST /api/ask                  Bearer KIT_PEER_TOKEN  {"message":"..."}
POST /api/webhooks/mattermost  token=KIT_WEBHOOK_TOKEN  → queues ReplyOnMattermost
php artisan queue:work --queue=kit
php artisan kit:ask "what's in the catalog?"
php artisan kit:ask --prompt   # dump assembled instructions
```

Queue is Laravel `database` (sqlite), not Horizon. Horizon is Redis + a dashboard; Loki has neither. Add Horizon when Redis exists and there is more than one worker type worth watching.

## System prompt

Four layers, assembled every turn by `App\Agent\PromptBuilder`:

| Layer | Source | Changes |
|---|---|---|
| Identity | `identities/kit.json` | Rarely. Birth cert. |
| Hard rules | `prompts/hard-rules.md` | When Jordan locks taste. |
| Mission | `prompts/mission.md` | Voice + job. |
| Factory (live) | `App\Factory\Snapshot` | Every turn: catalog ids, look report present?, kitd/vite/blender, bikes-v2 branch. |
| Memory | `storage/app/kit/memory.json` | Pinned facts (`MemoryStore`). |
| Scratchpad | per-request | Webhook/CLI can pass extra focus. |

Clock is America/Phoenix. History is the last 40 turns on that thread (`ConversationStore`) — not stuffed into the system prompt.

This is Lexi's `instructions()` idea (identity + domain + boot + scratch + mission) without the citizen stack.

## Tools (v1)

| Tool | Does |
|---|---|
| `CatalogRead` | Read `catalog.json`. List or one id. |
| `HeartbeatRead` | kitd / vite / blender stamp. |
| `BoardWrite` | Flock-upsert `board.json`. Grok sessions use `bin/board-write`. |
| `LookReport` | Last Playwright `report.json`. Does not run the suite. |
| `YouTubeTranscript` | Full captions via `yt-dlp` (timestamps). Not Lexi's 8k clip. |
| `MemoryStore` / `MemorySearch` | File-backed `knowledge_kilt`. |
| `AskLexi` | Shells to `~/.grok/kit/ask-lexi` (her MCP). Taste / life only. |

Next (not built): `BlenderRun`, `LookCompare` (run Playwright), `GitHubOpenIssue`.

## Inspiration

- `laravel/ai` Agent / HasTools / Promptable — the wheel Jordan already uses.
- `the-shit/agent-skeleton` `BaseAgent` — thin, not Lexi's 400-line citizen.
- Lexi `instructions()` layering — identity, live context, scratchpad, mission.
- bikes-v2 `catalog.json` as the file API (AGENTS.md).
- Claude/Grok skills: the tool description *is* the UX.

Not used: a second Mastra/Vercel agent, a Go LLM loop, Horizon, Filament, Prism-direct (laravel/ai wraps it).

## Show-off asks

Hit Mattermost or `php artisan kit:ask`:

1. **What's shipping in the catalog right now?**
2. **Are kitd and vite up?**
3. **Read the last rider look report. Which regions failed?**
4. **Remember: rider is M1-locked, next mesh is hero-ebike.** Then: **what are you working on?**
5. **Ask Lexi what she stored about Kit's stack lock.**
6. **Fetch the HBM tutorial (`https://www.youtube.com/watch?v=qSR-qK2vRQY`) and give me 10 lines + timestamps.**

(6 needs captions. Following the steps in Blender is a later slice.)

## Boot

```bash
cp .env.example .env   # already done on Loki
# set XAI_API_KEY or OPENROUTER_API_KEY
php artisan test
php artisan serve --host=0.0.0.0 --port=8788
```

Needs an xAI or OpenRouter key in `.env` before `kit:ask` talks. Tools and prompt dump work without one.

## Ship gate

```bash
php artisan test
vendor/bin/pint --test
```

GitHub Actions (`.github/workflows/ci.yml`) runs the same pair on PHP 8.4. No Blender, no Playwright — factory hands stay on Loki. Required-status merge-block is convention-only on GitHub Free.

Queue knobs: `DB_QUEUE_RETRY_AFTER=240` (default in `config/queue.php`). Job / `KitAgent` / `kit-queue.service` timeout is **200s** so a 170s `BlenderRun` is not killed or re-dispatched.
