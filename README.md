# taosarts.org — Taos Arts Community Site

LAMP-stack PHP/MySQL site for a Taos, NM arts community: a public
gallery and artist stories, a recurring critique-club meeting schedule,
and an admin dashboard for running it — built for cheap shared hosting
(GoDaddy-style cPanel), not a Node/serverless stack.

## What this does

- **Public pages** — `index.php` (home), `gallery.php` (galleries of
  member artwork), `story.php` (artist stories/features),
  `questionnaire.php` (member intake).
- **Critique club meetings** — `admin/meetings.php` /
  `admin/meeting_edit.php` manage a recurring schedule (title, time,
  location, featured presenter/topic); members see what's next.
- **Admin dashboard** (`admin/`) — questionnaires, stories, galleries,
  meetings, and newsletter signups.
- **Local dev override** — `includes/config.php` loads an optional,
  gitignored `includes/config.local.php` (copy from
  `includes/config.local.example.php`) before falling back to
  `TAOSARTS_*` environment variables, so local MAMP-style settings
  never need to touch the env-based production config.

## Structure

- `index.php`, `questionnaire.php`, `story.php`, `gallery.php` — public site pages
- `admin/` — admin dashboard for questionnaires, stories, galleries, meetings, and newsletter signups
- `assets/` — shared CSS and JavaScript
- `includes/` — config, database connection, auth, and helper functions
- `database/schema.sql` — schema and seed content
- `uploads/` — user-uploaded images for stories and galleries (gitignored except a placeholder)

## Local setup

1. Create a MySQL database.
2. Import [`database/schema.sql`](database/schema.sql).
3. Either copy `includes/config.local.example.php` to
   `includes/config.local.php` (gitignored) for local MAMP-style
   settings, or set these environment variables directly:
   - `TAOSARTS_DB_HOST`, `TAOSARTS_DB_PORT` or `TAOSARTS_DB_SOCKET`,
     `TAOSARTS_DB_NAME`, `TAOSARTS_DB_USER`, `TAOSARTS_DB_PASS`,
     `TAOSARTS_BASE_URL`
4. Serve the project root as the web root so the homepage loads from `/`.

## Demo admin login

- Email: `admin@taosarts.org`
- Password: `password`

Change the seeded admin account immediately before production use.

## Shared hosting notes

- Arranged so the domain root can point directly at the project root.
- `uploads/` and `includes/config.local.php` are gitignored.
- `includes/` and `database/` are blocked in `.htaccess`; the HTTPS
  redirect there is skipped for `localhost`-style hosts during dev.

---
More projects at [enricolorenzo.com](https://enricolorenzo.com) · art at [enricotrujillo.com](https://enricotrujillo.com)
