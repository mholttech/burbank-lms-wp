# Burbank LMS

WordPress plugin foundation for Burbank LMS.

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
