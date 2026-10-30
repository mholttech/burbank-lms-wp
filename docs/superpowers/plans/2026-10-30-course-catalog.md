# Course Catalog Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver Phase 1 Pods-backed course authoring, permanent BFC identity and controlled publication.

**Architecture:** Pods provisions a WordPress-backed course type and fields. Plugin services enforce identity, authorization, approval and publication, while a dedicated PressPrimer adapter validates external mappings.

**Tech Stack:** WordPress, Pods, PHP, Gutenberg, PHPUnit, wp-env.

**Spec:** `docs/superpowers/specs/2026-10-30-course-catalog-design.md` and Phase 1 of `docs/independent-study-implementation-spec.md`.

## Global Constraints

- PHP 8.2+, WordPress 7.1.3+, Node 24; namespace `Burbank_LMS`, text domain `burbank-lms`.
- Required dependency: Pods; WordPress plugin header `Requires Plugins: pods`.
- Hooks through Loader; bootstrap contains no business logic; no manual build edits.
- No learner progress tracking, membership updates, seed credentials or paid dependencies.
- Phase 0 accepted as passed by the user; retain native PressPrimer grading and issuance.

## Review Focus

- Existing incompatible Pods definitions must produce an error without overwriting site data (Task 1).
- Concurrent code assignment and deleted courses must never allow published code reuse (Task 2).
- Pods, REST and classic editor writes must enforce the same authorization and validation (Tasks 2–4).
- Missing or unsupported assessment contracts must block publication with actionable errors (Task 3).
- Revision or mapping changes must preserve previous approved configuration (Task 4).

### Task 1: Required Pods schema and course capabilities

**Files:** Modify `burbank-lms.php`, `src/Burbank_LMS.php`, `.wp-env.test.json`; create `src/Courses/Schema.php`, `src/Courses/Capabilities.php`, `tests/php/CourseSchemaTest.php`.

**Interfaces:** `Schema::register(): void`, `Capabilities::install(): void`. Schema exposes `bfc_course` and the metadata named in baseline §7; course fields use postmeta storage.

- [ ] Inspect supported Pods APIs in the exact release artifact and record its version and hash; add that pinned artifact to the isolated test environment.
- [ ] Write tests for missing Pods, incompatible existing type, Gutenberg/REST support, repeated schema setup preserving existing data, and administrator/editor/learner capability boundaries.
- [ ] Run `npm run test:php`; confirm failures describe missing course behavior.
- [ ] Implement versioned Pods schema setup, required plugin header and runtime notice, dedicated capabilities and Loader wiring. Keep course schema migrations idempotent and avoid overwriting unrelated Pods settings.
- [ ] Run application tests, `composer run phpstan`, `composer run phpcs`; fix failures.

### Task 2: Permanent course identity and validated writes

**Files:** Create `src/Courses/Registry.php`, `src/Courses/Validator.php`, `tests/php/CourseIdentityTest.php`; extend schema and activation/upgrade wiring.

**Interfaces:** `Registry::reserve(int $course_id, string $code): bool`, `Registry::publish(int $course_id): bool`; `Validator::validate(array $fields, int $course_id): \\WP_Error`. Registry uses a unique database key on code and an immutable published reservation.

- [ ] Write tests for BFC format, all §4.1 subject ranges, duplicate codes including trashed/deleted courses, draft code changes, immutable published code, concurrent reservations, bounded text, independent-study outcome and positive quiz/template references.
- [ ] Run application tests and confirm expected failures.
- [ ] Implement transactional reservations and tombstones with idempotent database migration, prepared queries and error handling. Share validation across Pods, REST and admin entry points; reject invalid writes before changing identity.
- [ ] Run application tests and PHP checks; fix failures.

### Task 3: PressPrimer mapping adapter and publication gates

**Files:** Create `src/Integrations/PressPrimer.php`, `src/Courses/Publication.php`, `tests/php/CoursePublicationTest.php`; extend Loader wiring.

**Interfaces:** `PressPrimer::validate_mapping(int $quiz_id, int $template_id): \\WP_Error`; `Publication::validate(int $course_id, array $fields): \\WP_Error` consumes Validator and the adapter.

- [ ] Inspect exact released Quiz and Certificate source contracts, record supported versions and hooks, and create compatibility fixtures using those actual contracts.
- [ ] Write tests for missing plugins, unsupported versions/contracts, unpublished quiz, inactive or mismatched trigger, broad any-quiz trigger, duplicate quiz assignment, absent approval and valid approved mapping. Test REST rejection and classic-editor draft fallback with notices.
- [ ] Run application tests and confirm expected failures.
- [ ] Implement adapter and server-side publication gates; do not invent unsupported APIs or permit optimistic publication. Make every error actionable and keep failed submissions recoverable.
- [ ] Run application tests and PHP checks; fix failures.

### Task 4: Editorial approval, revision history and archiving

**Files:** Create `src/Courses/Editorial.php`, `src/Courses/Admin.php`, `tests/php/CourseEditorialTest.php`; extend schema and Loader wiring.

**Interfaces:** `Editorial::approve(int $course_id): \\WP_Error|bool`, `Editorial::start_revision(int $course_id, string $revision): \\WP_Error|bool`. Approval snapshots retain title, code, revision, outcome and quiz/template mapping.

- [ ] Write tests for editor versus approver permissions, CSRF protection, protected audit fields, invalidation on assessed metadata changes, material revision renewal, retained historical snapshots, harmless editorial edits and archive history preservation. Exercise Pods saves and REST requests.
- [ ] Run application tests and confirm expected failures.
- [ ] Implement bounded field UI through Pods, explicit approval/material-revision actions, immutable configuration snapshots, archive action and useful admin columns/notices. Use core post status plus explicit archived catalog state.
- [ ] Run application tests, PHP checks, and JS lint/build if assets changed; fix failures.

### Task 5: Integration documentation and final verification

**Files:** Update `AGENTS.md`, `README.md`; create `docs/course-authoring.md` and `docs/pressprimer-compatibility.md`.

- [ ] Document Pods provisioning, capabilities, field limits, approval invalidation, material revisions, permanent reservations, archive behavior and exact supported dependency contracts.
- [ ] Run `npm run test:php`, `composer run phpstan`, `composer run phpcs`, and applicable asset checks; report any environmental blocker accurately.
- [ ] Review the complete diff against the approved design and correct defects. Generate any commit or PR descriptions from the final diff; do not publish or release without authorization.
