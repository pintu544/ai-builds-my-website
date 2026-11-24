# DECISIONS — AI Builds My Website

## Key choices

1. **Facts first, AI second.** All facts are extracted deterministically (regex/rule-based) from sources and stored with their source and the exact snippet they came from. The AI only writes prose around *confirmed* facts. This is what makes the Golden Rule ("never invent facts") enforceable rather than a prompt suggestion.
2. **A fact guard on AI output.** After generation, every phone number and email in the AI copy is checked against the confirmed facts. If the AI mentions a contact detail that is not a confirmed fact, the whole AI output is rejected and the deterministic facts-only builder is used instead.
3. **Structured sections, not raw HTML.** The AI returns JSON sections (hero, about, services, prices, gallery, contact, footer). That makes sections individually editable and regenerable, and safe to render.
4. **Conflicts are single-value only.** Two different phone numbers are a conflict; twenty different prices are a price list. Only single-value fields (phone, email, address, hours, description) can conflict, and an unresolved conflict blocks publishing with an explanation.
5. **Blocked is a state, not an error.** Social/Google links that refuse scraping are stored as `blocked` sources with a manual-paste replacement right next to them. Nothing is guessed to fill the gap.
6. **Hidden instructions are surfaced, not obeyed.** An `InjectionScanner` flags instruction-like lines in uploaded/fetched text, shows them to the user in a red panel, and excludes them from extraction and from anything sent to the AI.
7. **Graceful AI failure.** AI access sits behind an `AiClient` interface (OpenAI-compatible driver + Gemini driver, chosen by env). If the provider is down, unconfigured, or returns malformed JSON, the app builds a facts-only site and tells the user exactly that.
8. **Blade + Tailwind, server-rendered.** The brief allows any frontend; server rendering keeps setup to `php artisan serve` and makes the demo robust. Tailwind is loaded via CDN so no Node build step is needed to run the app.
9. **Search behind an interface.** Web search uses Exa when `EXA_API_KEY` is set; results land as *candidate* sources the user must sanity-check (this doubles as the same-name-business disambiguation: the user sees which business each result describes). Without a key, the step is skipped with a visible note.

## What I cut

- **Accounts/auth** — the brief doesn't ask for them; a single-user local workflow keeps the focus on the pipeline.
- **Google Places API integration** — a Google listing *link* is accepted and fetched like any link; a full Places API integration needs a billing-enabled key and adds little over fetch + manual paste for blocked pages.
- **OCR for scanned PDFs** — PDFs with no extractable text are reported honestly with a manual-paste path instead of silently failing.
- **Drag-and-drop section reordering** — sections render in a fixed sensible order; editing and per-section regeneration are in.

## What's next

- Queue ingestion (jobs) so slow fetches never block the create form.
- A "questions" inbox: when a needed fact is missing entirely (e.g. no phone anywhere), generate a targeted question list for the business owner.
- Optional AI image captions for gallery photos, still behind the same fact guard.
- Deploy target: any PHP 8.3+ host with MySQL; add a one-command Docker setup.
