# Full Suite Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver the complete Burbank-owned learner suite on top of Phase 1: site/course invites, registration and profiles, enrollment, quiz gates, certificate merge/completion, Pods picks, and FSE learner blocks.

**Architecture:** Domain modules (`Access/*`, `Enrollment/*`, `Progress/*`, `Learner/*`, `Mail/*`) own business rules. Thin `Integrations/PressPrimer_Quiz` and `Integrations/PressPrimer_Certificate` adapters translate PressPrimer hooks. Phase 1 `Courses/*` and `Integrations/PressPrimer` stay authoritative for identity, approval, and mapping validation. Hooks register only through `Loader` in `src/Burbank_LMS.php`.

**Tech Stack:** WordPress 7.1.3+, PHP 8.2+, Pods 3.3.9.2, PressPrimer Quiz 3.1.3, PressPrimer Certificate 2.0.2, Gutenberg blocks (`@wordpress/scripts`), PHPUnit via `npm run test:php`, PHPStan, PHPCS.

**Spec:** `docs/superpowers/specs/2026-10-30-full-suite-integration-design.md`

**Out of scope for this plan:** VentraConnect; foreign LMS bridges; moving `bc_level`/`bc_requirement` ownership; cert.vip sync; Certificate upsells.

---

## Global constraints

- Namespace `Burbank_LMS`, text domain `burbank-lms`; no business logic in constructors; no hand-edits to `build/`.
- Custom tables use InnoDB + `dbDelta`, versioned options (same pattern as `Courses\Registry`), uninstall retains data (`Uninstallor` stays a no-op for these tables).
- Invite plaintext shown once at create; store `code_hash` only (`wp_hash_password` / `wp_check_password` or `hash_hmac` with site key — pick one scheme and use it for both scopes).
- Site vs course invite scopes are never interchangeable.
- Email verification required before enroll / mapped quiz / cert history; suspended users keep read-only past credentials.
- Legal name required at registration; afterward admin correction only (audited).
- Run `npm run test:php`, `composer run phpstan`, `composer run phpcs` after each task; `npm run build` / block registration tests when blocks change.

## File map

| Path | Responsibility |
| --- | --- |
| `src/Access/Invites.php` | Create/revoke/list/redeem invite codes (`scope=site\|course`) |
| `src/Access/Tables.php` | Install `{prefix}bfc_invite_codes` (+ shared install orchestration with Enrollment tables) |
| `src/Access/Learner.php` | Training access grant/revoke, suspend, email-verified checks, PP take-quiz cap sync |
| `src/Access/Registration.php` | Site-invite registration; block alternate learner register paths |
| `src/Access/Profile.php` | Legal name storage; admin correction audit; profile field guards |
| `src/Enrollment/Tables.php` | Install `{prefix}bfc_enrollments` |
| `src/Enrollment/Repository.php` | Enrollment CRUD / queries |
| `src/Enrollment/Service.php` | Self/admin enroll, withdraw, course-invite redeem, open vs restricted |
| `src/Progress/Service.php` | `quiz_passed_at`, complete on certificate issued, `burbank_lms_course_completed` |
| `src/Integrations/PressPrimer_Quiz.php` | `can_start`, `before_submit`, pass/fail/attempt hooks, embed chrome |
| `src/Integrations/PressPrimer_Certificate.php` | Merge fields, issue validation, issued handler |
| `src/Courses/Picks.php` | Pods related-object / pick sources for quiz, template, optional `bc_requirement` |
| `src/Courses/Publication.php` / `Validator.php` | Matrix slug soft-check when `bc_requirement` exists |
| `src/Courses/Capabilities.php` | Bump version; add `enroll_bfc_courses` |
| `src/Learner/Catalog.php` | Discoverable course queries |
| `src/Mail/Mailer.php` | Verification, welcome, enroll, complete emails |
| `src/Branding/PressPrimer.php` | Optional PP name/logo filters |
| `src/REST/Routes.php` (or `src/Access/Rest.php` + `src/Enrollment/Rest.php`) | Register/enroll/withdraw/redeem REST |
| `src/Access/Admin.php`, `src/Enrollment/Admin.php` | Site invites UI; course invite + enrollment meta boxes; user profile fields |
| `src/Blocks/*` + `resources/blocks/*` | register, profile, catalog, course, enroll, my-courses |
| `src/Burbank_LMS.php` | Wire all hooks via Loader |
| `tests/php/Access*.php`, `Enrollment*.php`, `Progress*.php`, `PressPrimerGate*.php`, block tests | Application coverage |
| `docs/course-authoring.md`, `docs/learner-access.md`, `docs/pressprimer-compatibility.md`, `AGENTS.md` | Ops docs |

---

### Task 1: Invite + enrollment tables and capability bump

**Files:**
- Create: `src/Access/Tables.php`, `src/Enrollment/Tables.php`
- Modify: `src/Courses/Capabilities.php` (VERSION → `2`, grant `enroll_bfc_courses` to administrator), `src/Burbank_LMS.php`, `src/Activator.php` if activation should call install
- Test: `tests/php/AccessTablesTest.php`

**Schema (exact):**

`{prefix}bfc_invite_codes`: `id` PK, `scope` enum/varchar (`site`|`course`), `course_id` nullable bigint, `code_hash` varchar(255), `label` varchar(191), `kind` (`multi`|`once`), `max_uses` int unsigned null, `use_count` int unsigned default 0, `expires_at` datetime null, `revoked_at` datetime null, `created_by` bigint, `created_at` datetime, indexes on `(scope, course_id)`, `code_hash`.

`{prefix}bfc_enrollments`: columns per spec (`id`, `course_id`, `user_id`, `status`, `source`, `invite_code_id`, `enrolled_at`, `completed_at`, `quiz_passed_at`, `credential_id`, `course_revision_snapshot`); UNIQUE `(course_id, user_id)`.

Version options: `burbank_lms_invite_tables_version`, `burbank_lms_enrollment_tables_version` (start at `1`).

- [ ] **Step 1: Write failing tests** for table install idempotency, InnoDB engine, unique enrollment pair, capability version 2 grants `enroll_bfc_courses` to administrator only (not editor by default).

```php
public function test_invite_and_enrollment_tables_install_once(): void {
	$invites = new \Burbank_LMS\Access\Tables();
	$this->assertTrue( $invites->install() );
	$this->assertTrue( $invites->install() ); // idempotent
	global $wpdb;
	$this->assertNotEmpty( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'bfc_invite_codes' ) ) );
	$this->assertNotEmpty( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'bfc_enrollments' ) ) );
}

public function test_enroll_capability_granted_to_administrator(): void {
	( new \Burbank_LMS\Courses\Capabilities() )->install();
	$this->assertTrue( get_role( 'administrator' )->has_cap( 'enroll_bfc_courses' ) );
	$this->assertFalse( (bool) get_role( 'editor' )->has_cap( 'enroll_bfc_courses' ) );
}
```

- [ ] **Step 2: Run** `npm run test:php -- --filter AccessTablesTest` — expect FAIL (classes missing).

- [ ] **Step 3: Implement** `Tables::install()` via `dbDelta` + InnoDB check (mirror `Courses\Registry::install`), bump Capabilities VERSION to `2`, wire `init` priority 12 installs in `Burbank_LMS.php`.

- [ ] **Step 4: Re-run tests +** `composer run phpstan` / `phpcs` on touched files — expect PASS.

- [ ] **Step 5: Commit** `feat(access): install invite and enrollment tables`

---

### Task 2: Invite service (site + course)

**Files:**
- Create: `src/Access/Invites.php`, `tests/php/AccessInvitesTest.php`
- Modify: `src/Burbank_LMS.php` (no hooks yet beyond what Admin needs later)

**Interfaces:**

```php
namespace Burbank_LMS\Access;

class Invites {
	/** @return array{id:int,plaintext:string}|\WP_Error */
	public function create( string $scope, array $args ): array|\WP_Error;
	public function revoke( int $id ): bool|\WP_Error;
	/** @return object|null Active matching row for scope (+ course_id when course). */
	public function find_active( string $plaintext, string $scope, ?int $course_id = null ): ?object;
	/** Atomically increment use_count; returns false if no longer active. */
	public function redeem( int $id ): bool;
	public function course_has_active_invites( int $course_id ): bool;
}
```

`$args` keys: `course_id` (required if course), `label`, `kind`, `max_uses`, `expires_at`, `bound_email` (optional meta or column — if adding column, bump table version in Task 1 follow-up migration `VERSION=2`). Prefer optional `bound_email` varchar null on invite table from Task 1 to avoid a second migration.

- [ ] **Step 1: Write failing tests** covering: create site multi/once; create course requires `course_id`; revoke; expiry; max_uses; once burns; site code fails `find_active` for course scope and vice versa; `course_has_active_invites`; concurrent redeem of once-code allows only one success (transaction).

- [ ] **Step 2: Run filter** — expect FAIL.

- [ ] **Step 3: Implement** hashing, active rules per spec, transactional `redeem`, non-leaking errors (`WP_Error` codes like `burbank_invite_invalid` without revealing which rule failed to anonymous callers).

- [ ] **Step 4: Tests + static analysis** — PASS.

- [ ] **Step 5: Commit** `feat(access): site and course invite redemption`

---

### Task 3: Learner access, registration, email verification, profile

**Files:**
- Create: `src/Access/Learner.php`, `src/Access/Registration.php`, `src/Access/Profile.php`, `src/Mail/Mailer.php`, `tests/php/AccessRegistrationTest.php`, `tests/php/AccessLearnerTest.php`
- Modify: `src/Burbank_LMS.php`

**Interfaces:**

```php
class Learner {
	public function grant_access( int $user_id, ?int $site_invite_id = null ): void;
	public function revoke_access( int $user_id ): void;
	public function suspend( int $user_id ): void;
	public function reactivate( int $user_id ): void;
	public function can_learn( int $user_id ): bool; // access + verified + !suspended
	public function sync_quiz_cap( int $user_id ): void; // pressprimer_quiz_take_quiz iff can_learn
}

class Registration {
	/** @param array{invite:string,email:string,password:string,legal_name:string,display_name:string} $data */
	public function register( array $data ): int|\WP_Error;
	public function block_default_registration( \WP_Error $errors ): \WP_Error;
}

class Profile {
	public function set_legal_name( int $user_id, string $name, bool $admin_correction = false ): bool|\WP_Error;
	public function get_legal_name( int $user_id ): string;
}
```

Meta keys per spec: `burbank_lms_training_access`, `burbank_lms_site_invite_id`, `burbank_lms_suspended`, `burbank_lms_legal_name`, plus email verification token/timestamp meta owned by Registration/Mailer.

- [ ] **Step 1: Write failing tests:**
  - Register without invite / bad invite / missing legal name fails.
  - Successful register creates user, stores legal name, grants `burbank_lms_access`, burns/increments invite, user not `can_learn` until verified.
  - After mark-verified, `can_learn` true and `pressprimer_quiz_take_quiz` present.
  - Suspend blocks `can_learn`; reactivate restores when verified.
  - `registration_errors` / `wp_login.php?action=register` path rejects learner self-serve (admin `create_users` still works in a separate assertion).
  - Learner cannot update legal name via profile meta guard; admin correction writes audit usermeta history (e.g. `burbank_lms_legal_name_audit` JSON log).

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** atomic register (invite redeem + `wp_insert_user` in transaction where possible), verification email via `Mailer`, hooks: `registration_errors`, `register_new_user` / custom REST later, `user_register` not enough alone — prefer single `Registration::register` entry point used by block/REST. Sync quiz cap on grant/verify/suspend.

- [ ] **Step 4: Tests + phpstan/phpcs** — PASS.

- [ ] **Step 5: Commit** `feat(access): invite registration, verification, and profiles`

---

### Task 4: Enrollment service

**Files:**
- Create: `src/Enrollment/Repository.php`, `src/Enrollment/Service.php`, `tests/php/EnrollmentServiceTest.php`
- Modify: `src/Burbank_LMS.php`

**Interfaces:**

```php
class Service {
	public function self_enroll( int $user_id, int $course_id, ?string $course_invite = null ): true|\WP_Error;
	public function admin_enroll( int $actor_id, int $user_id, int $course_id ): true|\WP_Error;
	public function withdraw( int $actor_id, int $user_id, int $course_id ): true|\WP_Error;
	public function get_active( int $user_id, int $course_id ): ?object;
}
```

Rules from spec: verified + `can_learn`; published + not archived; open if `! Invites::course_has_active_invites`; else require course invite redeem in same transaction as insert; admin bypasses course codes and may grant access in-flow.

- [ ] **Step 1: Write failing tests** for open enroll, restricted without code fails, restricted with code succeeds, site invite cannot enroll, withdrawn blocks until re-enroll, admin enroll, archive rejects new self-enroll, unverified/suspended fail.

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** Repository + Service; snapshot `course_revision` from post meta at enroll.

- [ ] **Step 4: Tests + static analysis** — PASS.

- [ ] **Step 5: Commit** `feat(enrollment): open and restricted course enrollment`

---

### Task 5: Progress + PressPrimer quiz/certificate adapters

**Files:**
- Create: `src/Progress/Service.php`, `src/Integrations/PressPrimer_Quiz.php`, `src/Integrations/PressPrimer_Certificate.php`, `tests/php/ProgressPipelineTest.php`, `tests/php/PressPrimerGateTest.php`
- Modify: `src/Burbank_LMS.php`, `docs/pressprimer-compatibility.md` (partial ok; finish in Task 11)

**Hooks to register (exact names from research inventories):**

Quiz: `pressprimer_quiz_can_start`, `pressprimer_quiz_before_submit`, `pressprimer_quiz_quiz_passed`, `pressprimer_quiz_quiz_failed`, `pressprimer_quiz_quiz_completed`, optionally attempt started/submitted.

Certificate: `ppcert_register_merge_fields`, `ppcert_merge_data`, `ppcert_before_issue` / `ppcert_issue_validation`, `ppcert_certificate_issued`.

Resolve course from quiz via postmeta `pressprimer_quiz_id` on published `bfc_course`. Unmapped quizzes: adapters return early (do not gate).

- [ ] **Step 1: Write failing tests** (use existing PP fixtures/patterns from `CoursePublicationTest`):
  - `can_start` false without enrollment / unverified / suspended; true with active enrollment.
  - Pass sets `quiz_passed_at` once; second pass does not clear completion.
  - Issue validation fails without enrollment/pass; succeeds with both.
  - `ppcert_certificate_issued` sets `credential_id`, `status=completed`, fires `do_action( 'burbank_lms_course_completed', $user_id, $course_id, $enrollment )`.
  - Merge data includes legal name and `bfc_code`, never email.

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** adapters + Progress; keep grading/issuance inside PressPrimer.

- [ ] **Step 4: Tests + static analysis** — PASS.

- [ ] **Step 5: Commit** `feat(progress): gate quizzes and complete on certificate issue`

---

### Task 6: Pods picks + matrix soft validation

**Files:**
- Create: `src/Courses/Picks.php`, `tests/php/CoursePicksTest.php`, `tests/php/CourseMatrixValidationTest.php`
- Modify: `src/Courses/Publication.php` and/or `Validator.php`, `src/Burbank_LMS.php`

- [ ] **Step 1: Write failing tests:**
  - When `bc_requirement` post type exists, publish with unknown `matrix_requirement_slug` fails; known published slug passes.
  - When type absent, non-empty slug does not fail publish for matrix reasons.
  - Pick registration callbacks expose only published quizzes/templates (mock or integration with PP models as in Phase 1 tests).

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** `pods_api_register_related_object` / pick filters per Pods 3.3.9.2 research; store requirement **slug**; wire into Schema field UI if Pods pick config must change (version bump `burbank_lms_course_editorial_ui_version` or schema version only if data shape changes — prefer UI version for pick presentation).

- [ ] **Step 4: Tests + static analysis** — PASS.

- [ ] **Step 5: Commit** `feat(courses): pods picks and matrix slug validation`

---

### Task 7: REST API for register / enroll / withdraw

**Files:**
- Create: `src/REST/Controller.php` (or split Access/Enrollment REST classes), `tests/php/LearnerRestTest.php`
- Modify: `src/Burbank_LMS.php`

Namespaces: `burbank-lms/v1` routes:

| Method | Route | Permission |
| --- | --- | --- |
| POST | `/register` | public; rate-limited; uses `Registration::register` |
| POST | `/enrollments` | logged-in `can_learn` |
| DELETE | `/enrollments/(?P<course_id>\d+)` | self or `enroll_bfc_courses` |
| POST | `/enrollments/admin` | `enroll_bfc_courses` |

- [ ] **Step 1: Write failing REST tests** for happy paths and 401/403/400 error codes matching `WP_Error` codes.

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** `register_rest_route` via Loader/`rest_api_init`; never proxy PP attempt APIs.

- [ ] **Step 4: Tests + static analysis** — PASS.

- [ ] **Step 5: Commit** `feat(rest): learner register and enrollment endpoints`

---

### Task 8: Admin UI — invites, enrollments, user profile

**Files:**
- Create: `src/Access/Admin.php`, `src/Enrollment/Admin.php`, `tests/php/AccessAdminTest.php`
- Modify: `src/Courses/Admin.php` (columns for enrolled/passed/completed counts), `src/Burbank_LMS.php`

- [ ] **Step 1: Write failing tests** for capability checks on invite create/revoke, admin enroll action nonce, user profile grant/suspend/legal-name correction requires `enroll_bfc_courses`, editors cannot manage invites.

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** wp-admin pages/meta boxes: global Site Invites; course meta Course Invites + Enrollments; user profile Training Access section; show plaintext invite only in the request that created it (admin notice).

- [ ] **Step 4: Tests + static analysis** — PASS.

- [ ] **Step 5: Commit** `feat(admin): invite and enrollment management UI`

---

### Task 9: Learner blocks + FSE templates

**Files:**
- Create via `npm run create-block` (or manual matching existing block scaffold): blocks `register`, `profile`, `catalog`, `course`, `enroll`, `my-courses` under `src/Blocks/` / `resources/` per project convention
- Create: block theme templates `templates/archive-bfc_course.html`, `templates/single-bfc_course.html` in the plugin’s block theme template registration path (use `get_block_templates` / `register_block_template` or plugin file convention already used by boilerplate — inspect existing block registration before choosing)
- Modify: `src/Burbank_LMS.php` `register_blocks`, `tests/php/BlockRegistrationTest.php` expectations
- Create: `src/Learner/Catalog.php`, front-end view scripts calling REST

- [ ] **Step 1: Write failing tests** that each new block.json is registered and built assets exist (extend `BlockRegistrationTest`).

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** blocks:
  - **register** — form → REST `/register`
  - **profile** — display name/email/password; legal name read-only
  - **catalog** — `Catalog::query()`; CTAs only if `can_learn`
  - **course** — open enroll vs course-invite field; quiz link when enrolled
  - **enroll** / **my-courses** — REST-backed
  - FSE templates compose catalog/course blocks

- [ ] **Step 4: `npm run build`**, tests, eslint if required — PASS.

- [ ] **Step 5: Commit** `feat(blocks): learner register catalog course and profile`

---

### Task 10: Mail events, light branding, optional AI context

**Files:**
- Extend: `src/Mail/Mailer.php`, create `src/Branding/PressPrimer.php`, optional filter in `PressPrimer_Quiz` for AI context
- Test: `tests/php/MailerTest.php` (assert `wp_mail` args via hooks)

- [ ] **Step 1: Write failing tests** that enrollment confirmation and `burbank_lms_course_completed` send mail; branding filter returns Burbank label when enabled (option `burbank_lms_brand_pressprimer`).

- [ ] **Step 2: Run** — FAIL.

- [ ] **Step 3: Implement** mail on verify/welcome/enroll/complete; optional PP plugin_name filter; optional AI prompt context with course title/outcome only — no AI settings UI.

- [ ] **Step 4: Tests** — PASS.

- [ ] **Step 5: Commit** `feat(mail): learner lifecycle emails and light PP branding`

---

### Task 11: Documentation and final verification

**Files:**
- Create: `docs/learner-access.md`
- Update: `docs/course-authoring.md`, `docs/pressprimer-compatibility.md`, `AGENTS.md` (layout + feature quick reference for Access/Enrollment/Progress/Blocks)

- [ ] **Step 1: Document** site vs course invites, registration fields, verification, suspension, enrollment rules, REST routes, blocks, and every PP hook consumed.

- [ ] **Step 2: Run full gate:** `npm run test:php`, `composer run phpstan`, `composer run phpcs`, `npm run build`, `npm run lint:js` if JS changed.

- [ ] **Step 3: Diff against** `docs/superpowers/specs/2026-10-30-full-suite-integration-design.md`; fix gaps (especially: alternate register path closed; merge fields use legal name; unmapped quizzes ungated; matrix absent soft-skip).

- [ ] **Step 4: Commit** `docs: learner suite operations and PressPrimer hook list`

---

## Spec coverage checklist

| Spec area | Task(s) |
| --- | --- |
| Invite tables + scopes | 1–2 |
| Training access, register, verify, profile, suspend | 3 |
| Open vs restricted enrollment | 4 |
| Quiz gates + completion pipeline + merge fields | 5 |
| Pods picks + matrix Path A | 6 |
| REST | 7 |
| Admin lifecycle UI | 8 |
| Blocks + FSE | 9 |
| Mail / branding / AI context | 10 |
| Docs + AGENTS | 11 |
| VentraConnect / foreign LMS / cert.vip | Explicitly excluded |

## Review focus

- Site and course invites must never cross-redeem.
- `can_learn` (access + verified + !suspended) is required for self-enroll and mapped `can_start`.
- Legal name at register; admin-only corrections afterward; certificates never show email.
- Unmapped PressPrimer quizzes remain ungated.
- Missing `bc_requirement` must not break publish.
- Tables and invites survive uninstall (no-op uninstallor).
