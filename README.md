# AI Builds My Website

Give the app whatever you have about a business — its name and city, a website or Google Business link, social media links, a brochure PDF, a price list, photos — and it builds a real, publishable website **only from facts it can show you**.

**Golden rule: never invent facts. If unsure, ask the user.** That rule is enforced in code, not just in a prompt:

- Facts are extracted **deterministically** from every source and stored with the source and the exact snippet they came from (the *facts board*).
- If two sources disagree on a single-value fact (e.g. two phone numbers), it's a **conflict**: publishing is blocked until the user picks the right one.
- Links that block scraping (Instagram, Google, login walls) become **blocked sources** with a manual-paste replacement — nothing is guessed.
- Instruction-like lines hidden in uploaded files are **flagged, shown to the user, and excluded** from facts and from anything sent to the AI.
- The AI writes copy **only around confirmed facts**, returns structured JSON sections, and its output passes a **fact guard**: any phone/email in the copy that isn't a confirmed fact rejects the output.
- If the AI is down or unconfigured, the app builds a **deterministic facts-only site** and says so — never a 500, never invented content.

## Stack

Laravel 13 · MySQL/MariaDB · Blade + Tailwind (CDN) · Exa (web search) · AI via an OpenAI-compatible endpoint (e.g. FastRouter) or Gemini — selected by env.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# create the database and set DB_* in .env
php artisan migrate
php artisan storage:link
php artisan serve
```

Open http://localhost:8000, create a project, review the facts board, generate, and publish. Published sites live at `/s/{slug}`.

## Configuration (.env)

| Key | Purpose |
| --- | --- |
| `DB_*` | MySQL connection (all state is stored in MySQL) |
| `EXA_API_KEY` | Web search. Optional — without it the search step is skipped with a visible note |
| `AI_PROVIDER` | `openai-compatible` (default) or `gemini` |
| `AI_API_KEY`, `AI_BASE_URL`, `AI_MODEL` | OpenAI-compatible provider settings |
| `GEMINI_API_KEY`, `GEMINI_MODEL` | Gemini settings (free-tier key works) |

No keys belong in the repo — `.env` is git-ignored and `.env.example` documents every key.

## How it works

1. **Ingest** — links are fetched (with blocked/login-wall detection), PDFs parsed (`smalot/pdfparser`), price lists (CSV/TXT) parsed, photos stored, and an optional web search returns candidate businesses.
2. **Extract** — `FactExtractor` pulls phones, emails, hours and price rows from each source's text; every fact keeps `source_id` + snippet. `InjectionScanner` removes instruction-like lines first (and reports them).
3. **Resolve** — the facts board groups facts by field, highlights conflicts, and lets the user confirm values or pick a winner. Search results double as the disambiguation step for same-name businesses.
4. **Generate** — `SiteGenerator` sends confirmed facts to the AI for structured sections, guards the output, and falls back to `FallbackSiteBuilder` when needed. Each section records the `fact_keys` it used.
5. **Edit & publish** — sections can be edited or regenerated individually; publishing requires zero unresolved conflicts and produces a public page at `/s/{slug}`.

## Tests

```bash
php artisan test
```

Feature tests cover extraction with provenance, injection flagging/exclusion, conflict detection and resolution, the publish gate, and the facts-only fallback. Tests run against a MySQL test database (see `phpunit.xml`).

## More

- `DECISIONS.md` — key choices, what was cut, what's next.
- `spec/` — the GRILL / SPEC / DESIGN / TASKS documents this build was made from.
