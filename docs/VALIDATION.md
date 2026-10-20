# Validation record

Validated on 19 October 2026 in an isolated demo workspace:

- PHP 8.5 local run: **94 tests passed**, **3 MySQL-only tests skipped**.
- Vite production asset build: passed.
- Composer configuration validation: passed.
- Demo database migration and seeding: passed (8 items, 14 deliveries, 188 sales).
- The two new seed tests verify fictional fixtures, the reviewer login, the
  visible demo banner and refusal to seed an occupied database.

The included GitHub workflow runs PHP 8.4 with SQLite and MySQL 8. The repository's live Actions page is the authoritative record of current
CI status; the figures above describe the recorded local run. A headless Chromium walkthrough verified the demo login, dashboard,
selling-price choice visibility and mobile stock page. Three screenshots were
captured and visually reviewed; they show only the generated demo data.

The source history was inspected for sensitive filenames and common credential
patterns before export. The portfolio history was created fresh after
sanitization. Automated scans are limited checks, not a guarantee that arbitrary
secrets can always be detected. No operational data was copied.
