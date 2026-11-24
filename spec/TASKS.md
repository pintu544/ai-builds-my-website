# TASKS — AI Builds My Website

## Slice 1 — Project, ingestion, facts board
- [x] T1.1 Scaffold Laravel 12 + MySQL (MariaDB), Tailwind, git repo. Done = `php artisan serve` boots, migrations run.
- [x] T1.2 Project create form (name, city, links, Google URL, file uploads) + project dashboard. Done = a project with mixed inputs saves and lists.
- [x] T1.3 Ingestion: link fetch with blocked detection, PDF text extraction, price-list parse, photo store, Exa web search with candidate grouping. Done = each source row shows status + extracted text.
- [x] T1.4 Fact extraction + facts board with sources, conflicts, disambiguation picker, blocked-source manual paste, injection flags. Done = two different phone numbers from two sources show as a conflict; an instruction line in a PDF is flagged and excluded.

## Slice 2 — Generation, editing, publish
- [x] T2.1 AiClient interface + Gemini and OpenAI-compatible drivers, env-selected; JSON validation + repair retry. Done = generation succeeds with a configured provider.
- [x] T2.2 SiteGenerator: sections from confirmed facts only, fact_keys stored per section; fallback facts-only builder. Done = generated site contains no fact absent from the facts board.
- [x] T2.3 Editor: edit section, regenerate one section, preview. Done = an edit persists; regenerate changes only that section.
- [x] T2.4 Publish: slug + public route; unresolved conflicts block publish with explanation; AI-down path shows error + fallback offer. Done = public URL renders without login; killing the AI key produces the graceful path, not a 500.

## Slice 3 — Proof and submission
- [x] T3.1 Feature tests for extraction, conflicts, injection exclusion, publish gating, fallback. Done = `php artisan test` green.
- [ ] T3.2 Two real-business demo projects built end to end locally; adversarial QA pass (fresh reviewer) + three-CLI validation (Codex, Kiro, Mistral) with findings fixed.
- [ ] T3.3 README (setup, env, architecture), `.env.example`, DECISIONS.md; GitHub repo public with description + topics.
- [ ] T3.4 5-minute demo video (2 real businesses) via the recording pipeline; submission email to hr@websites.co.in with repo + video links + Pintu's stipend and joining date.
