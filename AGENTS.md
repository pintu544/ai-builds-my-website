# AI Builds My Website

Laravel app built for the InstaWeb Labs assignment. Start with `spec/` (GRILL, SPEC, DESIGN, TASKS) and `README.md`.

Conventions:
- The golden rule is enforced in code: facts come only from sources (`app/Services/FactExtractor.php`), AI copy passes a fact guard (`app/Services/SiteGenerator.php`), and unresolved conflicts block publishing.
- Run tests with `php artisan test`. Local dev uses MariaDB; see `.env.example`.
- No API keys in the repo — everything is read from `.env`.
