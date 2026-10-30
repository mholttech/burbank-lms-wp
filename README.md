# Burbank LMS

WordPress plugin foundation for Burbank LMS.

## Course authoring

Burbank LMS provides the course authoring foundation for Burbank CERT Independent Study:

- A `bfc_course` post type, provisioned through [Pods](https://wordpress.org/plugins/pods/) (required) and edited in Gutenberg.
- Permanent `BFC-###` course codes that are never reused, with subject ranges and a separate revision field.
- Training Administrator approval (`approve_bfc_courses`) with immutable approval history, material revisions, and archiving.
- Publication checks against PressPrimer Quiz 3.1.3 and PressPrimer Certificate 2.0.2. Grading and certificate issuance stay in PressPrimer.

Learner catalog pages, registration and certificate history are not part of this foundation.

- [Course authoring and editorial controls](docs/course-authoring.md)
- [PressPrimer compatibility](docs/pressprimer-compatibility.md)

## Project identity

- Plugin: Burbank LMS
- Slug and text domain: `burbank-lms`
- PHP namespace: `Burbank_LMS`
- Repository: https://github.com/mholttech/burbank-lms-wp
- License: GPL-3.0-or-later (see `LICENSE.txt`)

## Development

Requires PHP 8.2+, WordPress 7.1.3+, Composer 2, Node.js 24, and a Docker environment for `@wordpress/env`.

```bash
composer install
npm ci
npm run build
npm run env:start # Optional interactive development site
npm run test:php
composer run phpstan
composer run phpcs
```

`npm run test:php` starts an isolated wp-env, runs PHPUnit, and leaves that environment running. Stop it with `npm run env:test:stop`.

Plugin logic belongs in `src/`, source assets in `resources/`, and PHP tests in `tests/php/`. See `AGENTS.md` for conventions.

## Attribution

Initial scaffold derived from [JUVO WordPress Plugin Boilerplate](https://github.com/JUVOJustin/wordpress-plugin-boilerplate), retaining its applicable license notices.
