## AI Coding Agent Instructions for Burbank LMS

This plugin is a modern WordPress plugin with strict conventions and automated workflows. Follow these guidelines.

Plugin identity: slug and text domain `burbank-lms`, PHP namespace `Burbank_LMS`, license GPL-3.0-or-later.

### Requirements

- **Runtime:** PHP 8.2+ (`composer.json` platform and the plugin header), WordPress 7.1.3+, Composer 2, Node.js 24, and Docker for `@wordpress/env`.
- **Bootstrap only:** `burbank-lms.php` loads the plugin. Business logic belongs in `src/`.
- **Registration:** Register hooks, filters, shortcodes, CLI commands, and abilities through `Loader`. Do not register hooks in constructors.
- **Versions stay in lockstep.** `Burbank_LMS::PLUGIN_VERSION` is the single version constant. `BURBANK_LMS_VERSION` in `burbank-lms.php` must mirror it. Do not introduce a second version source.
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
  Courses/               Course catalog schema, identity, approval, admin
  Integrations/          Dependency adapters (PressPrimer)
  Abilities/             Abilities API interfaces
  Blocks/                Gutenberg blocks (npm run create-block)
resources/admin/         Admin JS and SCSS source
resources/frontend/      Front-end JS and SCSS source
languages/               Text domain burbank-lms
tests/php/               PHPUnit application tests
docs/                    Implementation guides
.github/workflows/       CI and the tag-driven release workflow
```

Built assets land in `build/`. Do not edit `build/` by hand. Dependencies are prefixed into `vendor-prefixed/` by Strauss; do not vendor them into `src/`.

### Releases

Releases are **Semantic Versioning** only. The version is `MAJOR.MINOR.PATCH`, with an optional pre-release suffix. The tag is that version and nothing else. `.github/workflows/deploy.yml` copies the tag string into `src/Burbank_LMS.php`, the `Version:` header in `burbank-lms.php`, and `Stable tag:` in `README.txt`.

Allowed tags:

| Change | Tag | Published as |
| --- | --- | --- |
| Incompatible API or stored-data change | `2.0.0` | Latest stable release |
| Backward-compatible feature | `1.3.0` | Latest stable release |
| Backward-compatible fix | `1.2.1` | Latest stable release |
| Pre-release | `1.3.0-alpha.1`, `1.3.0-beta.1`, `1.3.0-rc.1` | Prerelease; not marked latest |

Rules:

- Choose the bump from compatibility: breaking changes increment MAJOR, compatible features increment MINOR, fixes increment PATCH. Reset MINOR and PATCH to `0` when MAJOR increments. Reset PATCH to `0` when MINOR increments.
- Do not prefix the tag with `v`. The workflow uses the full tag as the WordPress version, so `v1.2.0` would be stored as the version string `v1.2.0`.
- Do not use dates, branch names, or commit SHAs as versions.
- A tag is a prerelease only when it contains `-alpha`, `-beta`, or `-rc`. Any other suffix is published as a stable release.
- Stable tags are committed back to the default branch. Pre-release tags are not.
- Do not hand-edit the released version on a tag. Put the SemVer string in the tag and let the workflow write the plugin files.

Trigger a release by pushing the SemVer tag. `deploy.yml` runs on every tag push.

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

- **Course catalog:** `src/Courses/` owns the Pods-provisioned `bfc_course` type (Pods is required), permanent BFC registry, write-path validation, approval snapshots, material revisions, archive and publication gates; `src/Integrations/PressPrimer.php` checks the exact supported PressPrimer Quiz 3.1.3 / Certificate 2.0.2 contracts. Every write path (Pods, REST, classic, metadata APIs) must stay guarded, and published codes and snapshots are never mutated. See `docs/course-authoring.md` and `docs/pressprimer-compatibility.md`; tests are `tests/php/Course*Test.php`.
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
