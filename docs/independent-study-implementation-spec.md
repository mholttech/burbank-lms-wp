# Burbank CERT Independent Study — Product & Implementation Specification

**Status:** Proposed implementation baseline; requires Phase 0 validation and program approval  
**Document version:** 1.0 · **Updated:** 2026-10-30  
**Repository:** [mholttech/burbank-lms-wp](https://github.com/mholttech/burbank-lms-wp)  
**WordPress plugin:** Burbank LMS (existing scaffold); learner-facing brand: **Burbank CERT Independent Study**  
**Primary website:** Burbank CERT WordPress (Bedrock/Trellis), separate from [CERT.vip](https://burbank.cert.vip/)

> **Product constraint:** This is not a traditional learning management system. There are **no lessons, enrollment sequences, lesson checkmarks, watched-video records, progress percentages, mandatory content order, or course-progression tracking.** Learners review one Gutenberg-authored course page, take its final quiz, and obtain a certificate upon passing.

## 1. Purpose and outcomes

Build a free/open-source, self-hosted WordPress independent-study portal that makes Burbank CERT-authored knowledge training easy to publish, assess, certify, and revisit. Volunteers register with an invitation code, use dedicated WordPress credentials, view available courses, complete final assessments, download earned PDF certificates, and manually upload those certificates to CERT.vip when required.

Successful delivery means:

- An authorized member can register, verify an email address, sign in, read any published digital course in any order, take its quiz, and receive a branded downloadable PDF after passing.
- Members see a catalog of available courses, course-level **Not yet passed / Passed / Certificate pending or unavailable** outcomes, their quiz attempts, and their issued certificate history. These are **assessment/certificate states**, never page or learning-progress states.
- Training administrators can create a course with native Gutenberg content, assign a permanent BFC code, associate exactly one final quiz and its award configuration, publish it after checks, and archive it without destroying history.
- Quizzes and certificates are created by existing **free** PressPrimer plugins. Burbank LMS is an integration and access layer, not a second assessment or credential engine.
- Training achievements do not automatically satisfy field qualifications or update membership eligibility. CERT.vip remains the **official volunteer training record**; there is **no API integration, SSO, sync, or shared account**.

## 2. Decisions and explicit non-goals

**Decided:**
- Repository: `mholttech/burbank-lms-wp`.
- WordPress code identity must follow the existing scaffold: plugin `Burbank LMS`, slug/text domain `burbank-lms`, PHP namespace `Burbank_LMS\`, GPL-3.0-or-later, PHP 8.2+, WordPress 7.1.3+, Node 24; do not use earlier suggested `BurbankCert\LMS` namespace.
- Learner-facing program: `Burbank CERT Independent Study`; course catalog prefix **BFC = Burbank Fire CERT**.
- Course identifier: `BFC-###` (stable catalog identity), separate revision (e.g. `1.0`), separate unique certificate credential ID.
- One Gutenberg post per online course; one published final PressPrimer quiz per course. No lesson CPT or completion engine.
- Existing Pods-backed membership requirements stay authoritative for **public requirement descriptions**; online courses reference their stable requirement slugs, without changing membership status.
- No paid plugins, pro extensions, or SaaS dependencies. No course fees.

**Excluded from v1:** course progression, SCORM/xAPI tracking, lesson CPT, attendance, classroom practical assessments, course prerequisites, video watch verification, instructor dashboards, grades beyond quiz results, CERT.vip automation, multi-tenant organizations, badge gamification, automatic membership advancement, bulk certificates, and complex analytics.

## 3. Initial course scope — approved development shortlist

Only the following **five proposed fully digital courses** are in scope. Final wording, syllabus, passing threshold, qualification recognition, and publication are subject to Training Coordinator/program approval.

| Proposed BFC ID | Course title | Existing matrix requirement slug | Notes |
| --- | --- | --- | --- |
| **BFC-210** | Event/Incident Scene Safety & Awareness | `scene-safety` | Preferred Phase 0 pilot; scenario-based hazard and authority questions |
| **BFC-810** | Public Outreach & Education | `public-outreach` | Public interaction, authorized messaging, accessibility, referrals |
| **BFC-410** | Documentation Training — Common ICS Forms | `documentation` | ICS-213 / ICS-214 examples and scenario questions |
| **BFC-510** | Incident Rehab & Hydration I — Basics & Awareness | `rehab-1` | Knowledge-based awareness; does not cover Rehab II practical |
| **BFC-310** | Basic 2-Way Radio Operations | `radio-operations` | **Approval gate:** confirm a written assessment alone can satisfy this specific requirement; otherwise adjust course outcome to knowledge-only, not full operational proficiency |

These IDs are **proposed catalog assignments**, not yet approved existing credentials. Do **not** provision certificates indicating an approved qualification before leadership validates course scope.

**Explicitly removed from the digital catalog:** Agency Rep, Policies & Procedures, Starlink. Do not quietly add these through category seeds or examples. **Out of scope:** ACS Net Control, Cellular Bonding, Rehab II, Volunteer Fire Patrol, Vehicle/Utility Truck/Driver courses, CERT Camp, and anything requiring an in-person skills evaluation; FEMA/CSTI/FCC credentials remain external.

## 4. Identity, numbering and certificate semantics

### 4.1 BFC identifier policy

- Use `BFC-###`, never FEMA `IS-###` or CSTI `G-###` conventions for locally issued courses.
- Subject ranges organize a catalog **without** indicating difficulty, membership level, order, or prerequisites:
  - 100–199: Program Administration & Membership (reserved; not a current digital course).
  - 200–299: Safety & Field Awareness.
  - 300–399: Communications.
  - 400–499: Field Operations & Documentation.
  - 500–599: Rehabilitation & Logistics.
  - 600–699: Incident Management & Coordination.
  - 700–799: Leadership & Program Operations.
  - 800–899: Community Engagement & Public Education.
  - 900–999: Reserved.
- Assign initial IDs in increments of ten. Never renumber or reuse after publication. Keep revisions in a distinct field, never appended to the permanent ID.
- Validate format, uniqueness including trashed/archived courses, approved category/range, and prevent reuse through an immutable registry/tombstone.
- Display BFC ID on course cards, course pages, downloadable certificates, and exports. Keep provider as Burbank CERT.

### 4.2 Certificate language

Award for **successful completion of the Burbank CERT independent-study knowledge course**, not certification of hands-on response competencies. Include issuer, learner display/legal name per approved policy, exact course title, BFC ID, revision if securely available, completion date, credential ID, and verification QR when enabled. Never include a volunteer ID, birth date, email, or sensitive personnel details on public verification pages.

PressPrimer merge fields must be tested against the **installed release**. If the course code/revision cannot be passed safely as a merge field, use a course-specific template with explicit static code and revision or implement a supported extension. Do not claim native custom merge tokens before validating them.

Certificate issuance is a **credential event** derived from an authenticated passing attempt, not proof that an entire practical qualification has been met. The member downloads the PDF for manual submission to CERT.vip.

## 5. Components and responsibility boundaries

| Component | Owns | Must not own |
| --- | --- | --- |
| WordPress Gutenberg | Course narrative, text, media embeds, downloads | Assessment state |
| Pods / existing website CPTs | Required course type/field management and existing membership training matrix `bc_requirement` / `bc_level` | Learner pass state |
| **Burbank LMS** | Course catalog type and BFC metadata; quiz mapping; invitation accounts; authz; learner course-status projections; admin health checks; free export tool | Quiz grading, certificate engine, CERT.vip records |
| **PressPrimer Quiz (free)** | Questions, quiz configuration, attempts, grading, per-user results | Membership verification, BFC catalog |
| **PressPrimer Certificate (free)** | Award triggers, issued certificates, PDF generation, verification, revocation, history | Course progress, member qualification |
| Site theme `burbankcert-fse` | Page presentation, responsive styling, forest-green branding and light/dark support | Security and completion rules |
| `mholttech/burbankcert.org` | Bedrock/Trellis deploy, version pin, activation/configuration and integration | LMS feature source code |

**Site coupling:** the Burbank LMS plugin must run independently of the child theme. Pods is a required plugin dependency (`Requires Plugins: pods`) and manages the course post type and fields. The existing training-matrix requirement relationship is read-only; the external matrix catalog may be absent. An unavailable external requirement catalog must not break course viewing or exam completion.

**Version note:** during source review, upstream PressPrimer GitHub `develop` files declared Quiz 3.1.3 and Certificate 2.1.0; installed WordPress.org release ZIPs may differ. Lock and test exact **released artifact versions**, not merely `develop` implementation details.

## 6. UX and information architecture

### 6.1 Learner pages

- **Independent Study catalog:** all published and eligible courses; searchable/filterable by subject, stable BFC ID; statuses derived from assessment/certificate data.
- **Course detail:** BFC number, title, revision, overview, learning objectives, Gutenberg text, embedded YouTube videos, downloads, final assessment call-to-action, explicit course outcome.
- **My Training:** available course list, own assessment results, certificates. Use free PressPrimer quiz dashboard (`[pressprimer_quiz_dashboard]`), or its My Attempts block, and free certificate history (`[ppcert_my_certificates]`) where usable. Embed only if access-controlled and accessible. A custom unified screen can present summaries but must query the underlying systems read-only.
- **Register / Sign in / Password reset / Verify email:** accessible, branded, member-focused; explain that CERT.vip credentials will not work.
- **Certificate retrieval:** downloadable PDF and verification information; failed issuance must provide a helpful recovery path.

**No progress UI.** Avoid “25% complete,” “lessons completed,” “continue where you left off,” or “watched” labels. A learner may take a final quiz immediately. The site may show an incomplete *quiz attempt* if PressPrimer already maintains one, but that is not a course-progress state.

### 6.2 Status policy

Determine by course-to-quiz mapping and current learner ID:

1. `Not yet passed` — no qualifying passing submitted, non-practice attempt.
2. `Passed · certificate available` — qualifying pass and a valid issued certificate.
3. `Passed · certificate pending` — qualifying pass but credential not yet issued or PDF not available.
4. `Passed · certificate revoked` — historical pass exists, but the corresponding credential is revoked. Do not label as currently certified.
5. `Unavailable/archived` — course removed from new registrations but history remains viewable.

Show attempt scores/histories through PressPrimer; do not store a second completion boolean in custom usermeta. Only legitimate quiz attempts, not admin previews or practice attempts, count.

## 7. Course data contract

Register a single `bfc_course` post type (final name should be checked against the existing website Pods types and plugin conventions) with native Gutenberg editor, title, editor body, excerpt, featured image, revisions, REST support, and capability mapping. The type is not a “lesson.”

Required metadata:

| Field | Suggested type | Rule |
| --- | --- | --- |
| `bfc_code` | unique string | `BFC-[0-9]{3}`, permanent post-publication |
| `course_revision` | version string | stored independently of code |
| `subject_category` | taxonomy or validated enum | range consistent with code |
| `pressprimer_quiz_id` | positive integer | exactly one final quiz |
| `certificate_template_id` | positive integer or managed reference | matches active trigger configuration |
| `matrix_requirement_slug` | slug or null | read-only link, not qualification update |
| `course_outcome` | enum | initially `independent_study`; no practical credential claims |
| `catalog_state` | enum | draft/published/archived controlled by workflow |
| `approved_by`, `approved_at` | user ID / UTC datetime | approval audit |
| `source_revision` / `approval_notes` | bounded strings | trace curriculum changes |

Prefer core WP posts/postmeta/taxonomy, not a new custom learner-progress table. Use custom tables **only** for transactional invitation data and, if needed, immutable BFC-number reservations/issuance audit. The quiz and certificate plugins already own their own tables.

**Publish validation:** course code available and correct range; approved learning outcome; published mapped quiz; configured active certificate trigger; no conflicting quiz-to-course assignment; acceptable title/revision and certificate naming; approved state and correct roles. Errors are actionable and block publication rather than silently breaking awards.

**Changes:** editorial correction can keep course revision under an approved policy; material changes to assessed competencies require review, revision update, and explicit decision on whether to replace the quiz. Historical certificates must remain attributable to the original approved achievement. No retroactive mass-revocation from a simple page edit.

## 8. Invitation-only learner accounts

WordPress accounts are independent of CERT.vip. Registration is *not* a general public WordPress registration feature.

### 8.1 Invitation rules

- Authorized staff issue opaque, high-entropy, single-use invitations; store **hashes**, not plaintext tokens. Expiry, revocation, issuer, timestamp, and redemption status are recorded.
- Send invitations only to approved members; optional email binding must match verified email. Validate member authorization **out-of-band** because CERT.vip has no identity API.
- Redeem **atomically** against concurrent requests so a code can never create multiple accounts; implement transaction/conditional update appropriate to MariaDB. Avoid TOCTOU check-then-insert.
- Rate-limit attempts; use Wordfence and/or plugin protections without relying exclusively on CAPTCHA; use safe error responses that avoid email and token enumeration.
- Require email verification before learner access to quizzes and certificate history. Unverified user and suspended user get no course assessment capability.
- Protect every alternate registration path (`wp-login.php?action=register`, REST registration, multisite if applicable), not just the custom form. Never disable administrator account creation workflows.
- Registration code is **not** a password; use WordPress secure password/reset mechanisms and WP session protections.
- Admin actions (issue/revoke/invalidate/resend) are capability-checked, nonce-checked where applicable, audited, and never leak reusable codes in logs.

### 8.2 Account lifecycle and roles

Use dedicated learner capability e.g. `burbank_lms_access`; assign `pressprimer_quiz_take_quiz` only to verified active learners as supported by the installed quiz plugin. Do not give learners `edit_posts`, `manage_options`, teacher/instructor, or PressPrimer management capabilities.

Separate Training Administrator/Training Editor capabilities from existing Steering Committee, Duty Officer Admin and Duty Officer roles; integrate through PublishPress Capabilities as appropriate. Define an admin path for suspension, reactivation, name corrections, lost invitations, duplicate accounts, and data export/deletion, with certificate history retention policy.

**Authorization invariant:** an account with `read` alone cannot start/submit a BFC quiz. All relevant REST/AJAX handlers and direct shortcode endpoints must reject unauthorized or suspended accounts. Protect against invoking a mapped quiz on an unrelated page. Ensure the quiz is configured to require authentication and that access rules are enforced server-side, not only by hiding links.

## 9. PressPrimer integration — verified code and unverified gates

### 9.1 Known source interfaces

PressPrimer Quiz exposes `pressprimer_quiz_quiz_passed` with graded attempt and quiz objects, and `[pressprimer_quiz_dashboard]` with free Home/My Results screens. PressPrimer Certificate has a `PressPrimer_Certificate_PPQ_Adapter` listening to this event and calling `PressPrimer_Certificate_Issuance_Service::issue(...)`, with matching active trigger and duplicate suppression. Its historical pass queries exclude guest and practice attempts. These findings came from upstream source inspection, **not a deployed smoke test**.

**Prefer PressPrimer's native quiz-pass → certificate flow.** Do not intercept grading or reimplement issuing for normal submissions. Our integration only validates catalog mappings and presents projected statuses. Avoid undocumented API/database coupling unless reviewed, pinned, and covered by compatibility tests.

### 9.2 Phase 0 tests (hard gate)

Use the exact free release distributions on an isolated staging clone and record versions, SHA256, DB schema, hooks/filters, and failure results:

1. Create a Gutenberg course with YouTube, downloadable PDF and a linked quiz.
2. Confirm guest, inactive, unverified and unauthorized users cannot attempt/submit the exam via UI **or direct REST/AJAX**; authorized verified members can.
3. Configure a passing score and show server-side persisted passing/failing attempts, retry limits and false submissions.
4. Confirm native PPQ pass event causes **one** certificate for one pass and no duplicate after retries.
5. Confirm branded single-page Letter landscape PDF, exact name/course/BFC ID/revision/date and QR verification render in the *free* designer.
6. Confirm free per-user attempt history and `[ppcert_my_certificates]` history render for the correct user only, with no cross-user leaks.
7. Test failure between passing and PDF generation; surface `Passed · certificate pending`, retry issuance through documented/controlled admin operation without duplicate awards.
8. Confirm revoked certificates remain historically attributable but are not shown as valid credentials.
9. Exercise quiz edits, course revision/archiving, changed score threshold, repeat pass, and historical credential preservation.
10. **Export and restore** quiz questions, answers, settings, attempts and certificates without paid extensions, including asset files; verify scores/credentials still reconcile.
11. Check theme light/dark, mobile, keyboard, focus, WCAG 2.2 AA target, print, PDF accessibility where supported, and mail delivery.
12. Verify dependencies on WordPress 7.1.3+/PHP 8.2+ and actual production PHP, database engine, and required PHP extensions.

Document each result as pass/fail, artifact version, reproduction steps, evidence, severity, and owner. **Do not advance to full implementation** if authorization, issuance, privacy or exportability gates fail. If release APIs prove too unstable or free restrictions block requirements, write an alternative design decision before further development.

## 10. Free data portability, backups, and recovery

PressPrimer's paid import/export cannot be a hidden requirement. Investigate publicly documented exports or implement a narrow, audited free WP-CLI export and import/restore tool for the **exact pinned schema**. Cover quizzes, answers, settings, attempts, user ownership mappings, issued certificates, PDFs or reproducible templates/assets, revocations, course records, invitation audit, and immutable course IDs. Do not fabricate an API or silently rely on a plain CSV insufficient to reconstruct a credential.

The database is the system of record. Backups must include database, uploads, embedded course media references, locally stored PDF/certificate files, fonts/templates, and plugin versions. Test restoring into an isolated site, checking credential links, account privacy and site-local URL rewrites. Exclude invitation plaintext secrets from backups/log exports. Define RPO/RTO with the hosting operator before launch.

## 11. Admin authoring and operational workflows

1. Training Editor drafts Gutenberg content; selects one BFC code and its range/category.
2. Creates/questions/tests a PressPrimer quiz, score threshold, attempt policy, feedback and media; does not require a learner to review content first.
3. Training Administrator creates certificate template/award trigger, checks learner name and local “Independent Study” wording, prints sample PDF, and verifies QR.
4. LMS admin validator confirms course ↔ quiz ↔ template mapping; no accidental “Any quiz” certificates.
5. Authorized program reviewer signs off course applicability (especially Basic 2-Way Radio Operations); publication is controlled.
6. Learner opens any available course, reads content at their discretion, submits final quiz and retrieves certificate.
7. Support staff correct data or diagnose missing issuance through audited, capability-restricted tools; no undocumented manual DB edits.
8. Retire course by archiving while maintaining past user results, certificate access and historical identity.

**Do not conflate published course content with code deployments.** Gutenberg posts, Pods relations, questions and certificate templates live in the WordPress database; define content promotion procedure across staging and production and prohibit overwriting production learner data through database imports.

## 12. UI and brand system

Use current Burbank CERT styling: forest-green header accents, dark graphite `#202A26`, warm off-white, legible sans-serif type, ample whitespace, mobile-first design, light/dark compatibility and accessible inputs. Public program label **Burbank CERT Independent Study**; certificates say **Burbank CERT**, not FEMA/CSTI. BFC IDs must be visible but secondary to human course titles.

Letter/landscape certificate (792×612 pt): Burbank CERT logo/issuer, prominent “Certificate of Completion”, recipient name, exact course title, BFC code, completion date, separate credential identifier and functioning verification QR. Authorized signature only if approved. Avoid implying official fire department endorsement beyond authorized Burbank CERT branding; use approved marks/assets.

## 13. Security, privacy and threat model

Threats: public invitation guessing, race-condition redemption, open alternative registration, quiz access via direct endpoint, forged client scores, privilege escalation through REST, horizontal access to others' quiz history or certificates, public verification information leakage, PDF path guessing, malicious course content/files, admin CSV injection, CSRF and compromised accounts.

Controls: server-side authorization per endpoint; WordPress capability checks and nonces; REST permissions; `$wpdb->prepare` for dynamic queries; output escaping and input sanitization; invitation hashes and atomic redemption; minimal roles; verified identity/authorized member process; audit actions, rate limiting, Wordfence compatibility; safe upload/media permissions; exclude private pages from full-page caches/SEO indexes. Maintain a public verification privacy policy and a separate account deletion/retention policy.

Never expose DOB, member ID, email or quiz answer keys in public verification metadata. Treat certificates as externally shareable where QR is provided. Local privacy disclosures should explain that course pass does not automatically write back to CERT.vip.

## 14. Repo implementation and release integration

The repository **already contains a WordPress plugin scaffold**; extend it rather than replace it:

- `burbank-lms.php`: bootstrap only.
- `src/`: business logic under existing namespace `Burbank_LMS\`; register hooks through existing `Loader`, not constructors.
- `src/Blocks/`: Gutenberg blocks if needed; do not create an independent React app when native blocks/shortcodes suffice.
- `resources/admin/`, `resources/frontend/`: JS/SCSS source; generated output in `build/`.
- `tests/php/`: WordPress integration and unit tests.
- `docs/`: detailed implementation, decisions, runbooks.
- `.github/workflows/`: preserve current SemVer tag-driven release process; see `AGENTS.md`.

Current scaffold defines **PHP 8.2+, WordPress 7.1.3+, Node 24** and uses Composer/PHPStan/PHPCS, WP scripts, `@wordpress/env` test infrastructure. The PHP namespace `Burbank_LMS` is already in autoload and must remain in code. WordPress `Burbank LMS` is its existing admin name; the public program retains `Burbank CERT Independent Study`.

Maintain **separate plugin CI and release versioning** from the website repository. Publish SemVer tags without `v` as required by `AGENTS.md`; tag build contains executable plugin assets and dependencies, validates artifact layout/hash, and is deployed through a pinned release in `mholttech/burbankcert.org` (follow established Duty Officer style if integration is approved). Site plugin settings, secrets and email configuration are managed in environment/config rather than committed to the repository.

Tests:
- Composer static analysis, coding standards, PHPUnit, JS lint/build, release artifact integrity.
- Authorization matrix tests for guests/unverified/active/suspended/admin and all routes.
- Atomic invitation concurrency/rate-limit tests.
- Catalog uniqueness, course CRUD and mapping validator tests.
- Passing/failing/retry/revocation/status and certificate recovery integration tests.
- Upgrade/migration idempotency and rollback tests.
- Accessibility / visual regression check for theme light and dark.
- Restore test and staging deployment smoke test.

**Do not create an automatic production deployment just by merging this documentation.** Implementation must follow PR review, tests, a tagged plugin release and the existing site rollout procedure.

## 15. Incremental delivery plan and release gates

### Phase 0 — Dependency proof of concept (mandatory)

Pin the released free plugin versions; audit free feature boundaries and their exact source/API contracts; run the 12 tests in §9.2; decide stable data access and export strategies. Deliver findings and fix/replace decisions.

### Phase 1 — Course catalog and editorial controls

Implement single course CPT, BFC registry, range/uniqueness validation, metadata, approval workflow, mapping validator, editor UI, and read-only matrix relation. Publish no credentials yet.

### Phase 2 — Identity and access

Build invitation issuance/redemption, verification and learner permissions; close alternate signup/access paths and harden quiz endpoint authorization. Record security tests.

### Phase 3 — Student and certificate experience

Build catalog and course pages, integrate free results/certificate history, course pass/certificate status projections, robust issuance failure messaging, retrieval and limited administrative recovery.

### Phase 4 — Pilot

Run **BFC-210 Scene Safety** with test learner accounts. Verify accessibility, security, delivery and PDF output in staging, then an authorized small pilot with real accounts after policy approval.

### Phase 5 — Remaining courses and launch

Subject to approval, prepare BFC-810, BFC-410, BFC-510, BFC-310. Confirm the Basic Radio course's online-only validity decision before allowing its certificate to imply full completion. Conduct recovery exercise, publish operating runbooks, then tag/release and pin the artifact in the site repository.

### Go/no-go criteria

Must pass: no paid dependency; correct learner isolation; atomic authorized registration; secure quiz attempts; successful nonduplicate certificates and history; accurate BFC/revision labeling; free recoverable backup/export; clear certification semantics; dark/light responsive accessibility; approved curricula/issuer identity; deployment rollback.

## 16. Decisions requiring program sign-off (not architecture blockers)

- Confirm final five BFC numbers/titles, course owner, minimum passing scores and retake policies.
- Confirm whether passing an online **Basic 2-Way Radio Operations** exam satisfies the current matrix requirement without hands-on evaluation.
- Decide course validity/expiration or renewal where the matrix says “Must still be valid”; do not invent expiration.
- Approve issuer text, Burbank Fire Department marks, signatory, and public QR verification privacy disclosure.
- Decide invitation issuance authority, membership validation method, account lifecycle/retention, and legal name correction policy.
- Set certificate treatment after material revisions or revocations; protect historical evidence.

## 17. Definition of done

A new volunteer with a valid invitation can register and verify email, log in with a dedicated account, see the five approved course offerings, open any course without prerequisites, take a secured final quiz, automatically receive a correctly branded one-page PDF certificate on passing, view their own results and credentials again later, and download a certificate to manually submit to CERT.vip. A nonmember cannot register or take the exam; another member cannot access those records. Administrators can approve, publish, revise, archive, export and restore courses/quizzes/credentials under documented policies, and the entire system runs on **free/open-source components** with no progress tracking.

---

### Source and validation notes

- Source requirements come from the Burbank CERT training-matrix catalog in `mholttech/burbankcert.org`, including requirement slugs `scene-safety`, `public-outreach`, `documentation`, `rehab-1`, `radio-operations`.
- Existing plugin structure, namespace, versions and release conventions were verified against `README.md`, `AGENTS.md`, `composer.json`, `package.json`, and `burbank-lms.php` in this repository.
- PressPrimer pass hook and certificate adapter were inspected in the public upstream source. **Live end-to-end behavior on staging is unverified**, and must pass Phase 0.
- Earlier catalog proposals included Agency Rep, Policies & Procedures and Starlink; these are intentionally **excluded** after explicit scope change.
