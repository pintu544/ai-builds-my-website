# SPEC — AI Builds My Website

**Problem.** A small business owner has scattered raw material — a name, a city, social links, a Google listing, a PDF, a price list, photos — and no website. This app turns that material into a ready-to-publish website without inventing a single fact about the business.

**Users.** Business owner (creates and publishes a site); reviewer (opens the public URL).

## Functional requirements
1. The system SHALL let a user create a project with business name, city, any mix of social links, a Google Business listing URL, and uploaded files (PDF, price list in PDF/CSV/TXT, photos JPG/PNG).
2. The system SHALL ingest every source: fetch links, extract text from files, and run a web search for the business name + city.
3. The system SHALL extract a fact set (phones, emails, address, hours, services, prices, description lines) where every fact carries its source.
4. The system SHALL present a facts board showing each fact with its source, and SHALL flag any field with two or more different values as a conflict for the user to resolve.
5. The system SHALL, when search returns several distinct businesses matching name + city, require the user to pick the correct candidate before merging facts.
6. The system SHALL mark a source that blocks fetching (403, login wall, empty result) as BLOCKED and offer manual text paste as a replacement source.
7. The system SHALL scan extracted text for instruction-like content, flag it to the user, and exclude it from fact extraction and generation.
8. The system SHALL generate a website as structured sections (hero, about, services, prices, gallery, contact, footer) using ONLY the confirmed fact set; each stored section records the fact keys it used.
9. The system SHALL let the user edit any section, regenerate a single section, preview the site, and publish it to a public URL; unresolved conflicts SHALL block publishing with an explanation.
10. The system SHALL persist projects, sources, facts, sections and publish state in MySQL.
11. When the AI provider fails, the system SHALL keep all project data, show a plain-language error with retry, and offer a facts-only fallback site.

## Non-functional
- No API keys or secrets in the repository; `.env.example` documents every key.
- Ingestion of one project completes in under 60 seconds on a local machine.
- Generated public pages render without JavaScript.

## Out of scope
Accounts/login, payments, custom domains, image generation, SEO tools, analytics, multi-user collaboration.
