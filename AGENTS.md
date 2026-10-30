## AI Coding Agent Instructions for Burbank LMS

This plugin is a modern WordPress plugin with strict conventions and automated workflows. Follow these guidelines.

Plugin identity: slug and text domain `burbank-lms`, PHP namespace `Burbank_LMS`, license GPL-3.0-or-later.

### Requirements

- **Runtime:** PHP 8.2+ (`composer.json` platform and the plugin header), WordPress 7.1.3+, Composer 2, Node.js 24, and Docker for `@wordpress/env`.
- **Bootstrap only:** `burbank-lms.php` loads the plugin. Business logic belongs in `src/`.
- **Registration:** Register hooks, filters, shortcodes, CLI commands, and abilities through `Loader`. Do not register hooks in constructors.
- **Versions stay in lockstep.** `Burbank_LMS::PLUGIN_VERSION` is the single version constant. The `Version:` header in `burbank-lms.php` must match it. Do not introduce a second version source.
- **Every release uses Semantic Versioning 2.0.0.** See [Releases](#releases).

### Layout

```text
burbank-lms.php          WordPress plugin header and bootstrap
constants.php            Path and URL constants visible to PHPStan
src/                     All plugin logic (PSR-4: Burbank_LMS\)
  Burbank_LMS.php        Plugin class and PLUGIN_VERSION
  Loader.php             Hook, shortcode, CLI, and ability registration
  Activator.php          Activation
  Deactivator.php        Deactivation
  Uninstallor.php        Uninstall
  Abilities/             Abilities API interfaces
  Blocks/                Gutenberg blocks (npm run create-block)
resources/admin/         Admin JS and SCSS source
resources/frontend/      Front-end JS and SCSS source
languages/               Text domain burbank-lms
tests/php/               PHPUnit application tests
docs/                    Implementation guides
scripts/validate-release.py
release-please-config.json
.release-please-manifest.json
.github/workflows/release.yml
```

Built assets land in `build/`. Do not edit `build/` by hand. Dependencies are prefixed into `vendor-prefixed/` by Strauss; do not vendor them into `src/`.

### Releases

Releases are **Semantic Versioning** only: `MAJOR.MINOR.PATCH`. [Release Please](https://github.com/googleapis/release-please) computes the next version from Conventional Commits on `main`, opens a release pull request, and publishes `burbank-lms-vMAJOR.MINOR.PATCH` when that pull request merges. `.github/workflows/release.yml` then packages `burbank-lms-MAJOR.MINOR.PATCH.zip` and rejects any other tag shape.

| Commit | Version bump |
| --- | --- |
| `feat:` backward-compatible feature | MINOR (`1.1.0` → `1.2.0`) |
| `fix:` backward-compatible fix | PATCH (`1.1.0` → `1.1.1`) |
| `feat!:` or a `BREAKING CHANGE:` footer | MAJOR (`1.1.0` → `2.0.0`) |
| `chore:`, `docs:`, `refactor:`, `test:`, `ci:`, `style:` | No release |

Rules:

- Write Conventional Commits. The type and the breaking-change marker are how SemVer is chosen. A vague subject such as `update courses` does not produce a correct release.
- Reset MINOR and PATCH to `0` when MAJOR increments. Reset PATCH to `0` when MINOR increments. Release Please does this; do not calculate a custom scheme.
- Do not hand-edit `Burbank_LMS::PLUGIN_VERSION`, the plugin header `Version:`, `.release-please-manifest.json`, or `CHANGELOG.md` to force a version. Release Please owns those, using the `x-release-please-version` comment and the `x-release-please-start-version` / `x-release-please-end` markers. Leave the markers in place.
- Do not push `burbank-lms-v*` tags by hand. The package job accepts only `burbank-lms-vMAJOR.MINOR.PATCH` (no `v` prefix on the version itself, and no `-alpha`, `-beta`, or `-rc` suffix).
- Do not use dates, branch names, or commit SHAs as versions.
- `scripts/validate-release.py` checks that the header version and `PLUGIN_VERSION` are the same `MAJOR.MINOR.PATCH` as the tag. A release that drifts between those files fails packaging.

Current released version is tracked in `.release-please-manifest.json`.

### Asset Management

- **Assets:** Place source in `resources/admin/` and `resources/frontend/`.
- **Build:** `@wordpress/scripts` compiles them. Entry points are in `webpack.config.js`.
- **Scripts:** `npm run start` (watch), `npm run build` (production).

### Quality Assurance

- **PHP:** PHPStan (`phpstan.neon`), PHPCS (`phpcs.xml`)
- **JS:** ESLint (`eslint.config.cjs`)
- **CI:** GitHub Actions in `.github/workflows/`

### Key Primitives

**Loader methods:**

- `add_action()`, `add_filter()` — WordPress hooks
- `add_shortcode()` — Shortcode registration
- `add_cli()` — WP-CLI commands
- `add_ability()` — Abilities API (WP 6.9+)

**Composer scripts:** `phpstan`, `phpcs`, `phpcbf`, `i18n:extract`, `i18n:compile`

**NPM scripts:** `start`, `build`, `lint:js`, `lint:style`, `format`, `create-block`, `env:*`

### Feature Quick Reference

- **Blocks:** Run `npm run create-block`. Registration is automatic; `tests/php/BlockRegistrationTest.php` guards that every built block is loaded (via `/wp/v2/block-types`) and its assets exist. Use the `wp-plugin-bp` skill for block guidance.
- **Abilities API:** Implement interfaces in `src/Abilities/`, register via Loader. `tests/php/AbilityRegistrationTest.php` guards that every `Ability_Interface` implementation (and its category) is registered. Use the `wp-plugin-bp` skill for ability guidance.
- **i18n:** Extract with `composer run i18n:extract`, compile with `composer run i18n:compile`. Use the `wp-plugin-bp` skill for translation work.
- **wp-env:** Run `composer install` and `npm run build` before `npm run env:start`. The lifecycle only activates Burbank LMS. Use the `wp-plugin-bp` skill when tests are involved.
- **Testing:** Run application tests with `npm run test:php`. It starts the isolated test environment automatically. Stop it with `npm run env:test:stop`. Use the `wp-plugin-bp` skill for testing guidance.
- **Plugin upgrades:** Use the `wp-plugin-bp` skill or ask naturally to sync with upstream project conventions.
- **Official WordPress skills:** `.agents/skills/wp-*/` contains focused skills for block development, Interactivity API, PHPStan, project triage, and REST API work. Use the `wp-plugin-bp` skill `wp-skills` workflow to refresh or add official WordPress skills.
- **Composer setup:** `.agents/` ships in the initial Composer package so setup can run `wp-plugin-bp/scripts/plugin-replace.php`. Replacement cleanup removes `.agents/`, then setup asks whether to install agent skills for ongoing work.
- **Missing skills:** If `wp-plugin-bp` is unavailable in an initialized plugin, install it with `npx skills add https://github.com/JUVOJustin/wordpress-plugin-boilerplate --skill=*`.

### Maintaining the plugin

When adding new primitives, patterns, or documentation to this plugin:

1. Update `docs/` with detailed implementation guides
2. Update `AGENTS.md` with a high-level reference
