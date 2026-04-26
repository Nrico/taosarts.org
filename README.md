# taosarts.org

LAMP-ready PHP/MySQL codebase for `https://taosarts.org`, based on the provided frontend/backend example and organized for deployment to shared hosting such as GoDaddy.

## Structure

- `index.php`, `questionnaire.php`, `story.php`, `gallery.php`: public site pages
- `admin/`: admin dashboard for questionnaires, stories, galleries, and newsletter signups
- `assets/`: shared CSS and JavaScript
- `includes/`: config, database connection, auth, and helper functions
- `database/schema.sql`: schema and starter seed data
- `uploads/`: user-uploaded images for stories and galleries
- `examples/`: original source examples kept for reference

## Local setup

1. Create a MySQL database.
2. Import [database/schema.sql](/Volumes/Blue/code/taosarts.org/database/schema.sql).
3. Set these environment variables in Apache or your local PHP environment:
   - `TAOSARTS_DB_HOST`
   - `TAOSARTS_DB_NAME`
   - `TAOSARTS_DB_USER`
   - `TAOSARTS_DB_PASS`
   - `TAOSARTS_BASE_URL`
4. Serve the project root as the web root so the homepage loads from `/`.

## Demo admin login

- Email: `admin@taosarts.org`
- Password: `password`

Change the seeded admin account immediately before production use.

## Shared hosting notes

- This repo is arranged so the domain root can point directly at the project root.
- `uploads/` is intentionally ignored by Git except for a placeholder file.
- `includes/` and `database/` are blocked in `.htaccess`.

## Next steps

1. Clone or connect your GitHub repo in this folder.
2. Commit this migrated baseline.
3. Replace demo copy/content with final Taos Arts content and imagery.
4. Configure production database credentials.
5. Deploy to GoDaddy over SSH once hosting access is ready.
