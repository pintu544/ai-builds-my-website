# DESIGN — AI Builds My Website

**Architecture.** One Laravel 12 app, server-rendered Blade + Tailwind. Pipeline: Project → SourceIngestor (fetch/parse/search) → FactExtractor (deterministic) → FactsBoard (conflicts, disambiguation, blocked, injection flags) → SiteGenerator (AI, structured JSON) → Editor → Publisher (public slug route). All state in MySQL via Eloquent.

**Components**
- `IngestionService`: per-source drivers — `LinkFetcher` (HTTP with browser UA, detects 403/login-wall), `PdfParser` (smalot/pdfparser), `TextListParser` (CSV/TXT price lists), `PhotoStore` (validates + stores uploads), `WebSearcher` (Exa API via env key; returns candidate businesses).
- `FactExtractor`: regex/rule extractors for phone, email, address lines, hours, price rows, service names; every fact row stores `source_id` + raw snippet.
- `ConflictResolver`: groups facts by field; >1 distinct value = conflict; user picks the winner on the facts board.
- `InjectionScanner`: line patterns for instruction-like text; flags + excludes.
- `AiClient` interface: `GeminiDriver` (REST, `GEMINI_API_KEY`), `OpenAiCompatibleDriver` (FastRouter, `AI_API_KEY`/`AI_BASE_URL`); `AI_PROVIDER` env selects. JSON-schema-shaped prompt; response validated before storage.
- `SiteGenerator`: builds the prompt from confirmed facts only; stores sections with `fact_keys` used. `FallbackSiteBuilder`: deterministic sections from facts when AI is down or unconfigured.
- `Publisher`: sets `published_at` + slug; public route `/s/{slug}` renders sections with Blade; gallery uses stored photo paths.

**Data model (MySQL)**
- `projects` (id, business_name, city, slug, status, published_at)
- `sources` (id, project_id, type[link|google|social|pdf|pricelist|photo|search|manual], url/path, status[pending|ok|blocked|failed], raw_text)
- `facts` (id, project_id, source_id, field, value, snippet, confirmed bool)
- `conflicts` resolved via `facts.confirmed` + `projects.resolution` JSON (field → chosen fact id)
- `site_sections` (id, project_id, kind, position, content JSON, fact_keys JSON)
- `uploads` handled by `sources` rows with storage paths.

**Key decisions**
- Structured sections, not raw HTML from the AI: editable, regenerable per section, safe to render.
- Deterministic extraction before AI: facts exist and are auditable even if the AI is down; the AI only writes prose around confirmed facts.
- Search behind an interface with an Exa driver; absent key → search step skipped with a visible note, never faked.
- Blade over SPA: the brief says "any frontend"; server rendering keeps the demo robust.

**Risks**
1. Social/Google fetches blocked → treated as a first-class state with manual paste fallback (requirement 6).
2. AI returns malformed JSON → validate + one repair retry, then graceful failure path (requirement 11).
3. PDF text extraction quality → pdfparser + raw-text preview on the facts board so the user sees exactly what was read.
