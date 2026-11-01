# Learner access, enrollment, and progress

Operational guide for the Burbank-owned learner suite on top of Phase 1 course authoring. Design source: `docs/superpowers/specs/2026-10-30-full-suite-integration-design.md`. Course publication and BFC identity remain in [course-authoring.md](course-authoring.md). PressPrimer hook contracts are in [pressprimer-compatibility.md](pressprimer-compatibility.md).

## Code map

| Concern | Class |
| --- | --- |
| Invite table install | `Access\Tables` |
| Site and course invite CRUD / redeem | `Access\Invites` |
| Site-invite registration, alternate-path block | `Access\Registration` |
| Training access, suspend, `can_learn`, quiz-cap sync | `Access\Learner` |
| Legal name guards and admin correction audit | `Access\Profile` |
| Site invite admin + user profile Training Access | `Access\Admin` |
| Enrollment table install | `Enrollment\Tables` |
| Enroll / withdraw / restricted redeem | `Enrollment\Service`, `Enrollment\Repository` |
| Enrollment dispatch helpers (non-editor) | `Enrollment\Admin` |
| Course editor Invites / Enrollments panels + editorial REST | Course sidebar + `REST\Admin_Controller` (see [course-authoring.md](course-authoring.md)) |
| Quiz pass stamp and certificate completion | `Progress\Service` |
| Catalog queries and FSE templates | `Learner\Catalog`, `Learner\Templates`, `Learner\Block_Context` |
| Soft gate (invite-only presentation) | `Learner\Soft_Gate` |
| Training shell subnav and contextual actions | `Learner\Shell` |
| Learner REST register / enroll / withdraw | `REST\Controller` (`burbank-lms/v1`) |
| Lifecycle emails | `Mail\Mailer` |
| Optional PressPrimer chrome branding | `Branding\PressPrimer` |
| Quiz / certificate adapters and course chrome | `Integrations\PressPrimer_Quiz`, `Integrations\PressPrimer_Certificate` |
| PressPrimer front-end skin (CSS enqueue) | `Integrations\PressPrimer_Assets` |

All hooks register through `Loader` in `src/Burbank_LMS.php`. Invite and enrollment tables survive uninstall (Phase 1 `Uninstallor` remains a no-op).

## Access ladder

```
Site invite (or admin grant)
        │
        ▼
WordPress user + burbank_lms_access
        │
        ├── Open course ──────────► self-enroll (no course code)
        │
        └── Restricted course ────► course invite (or admin enroll)
                │
                ▼
        Active enrollment → quiz → pass → certificate → completed
```

`Learner::can_learn( $user_id )` is true when the user has `burbank_lms_access` and is not suspended. Self-enroll and mapped quiz start/submit require it. Administrators receive `burbank_lms_access` from the capabilities installer, so they can enroll and take courses without a separate grant.

| State | Catalog CTAs | Self-enroll | Mapped quiz | Own cert history |
| --- | --- | --- | --- | --- |
| No training access | No | No | No | No |
| Training access | Yes | Per course policy | With enrollment | Yes |
| Suspended | No | No | No | Yes (read-only past credentials) |

### Soft gate and shell (presentation)

Domain rules above are unchanged. `Learner\Soft_Gate` and `Learner\Shell` only affect HTML on learner surfaces; REST and PressPrimer gates still require `can_learn` and enrollment as documented.

**Soft gate audiences** (`Soft_Gate::audience( $user_id )`):

| Audience | When | Markup |
| --- | --- | --- |
| `guest` | `$user_id < 1` | `.bfc-soft-gate` panel: Training caption, **H1** title, invite-only copy, **Log in** + **Register** (`/register/`) |
| `no_access` | Logged in, `! can_learn` | Same caption/title pattern, administrator/reactivation copy; no Register CTA |
| `learner` | `can_learn` | No gate card (`should_block` is false) |

When `should_block` is true, `burbank-lms/catalog`, `course`, `profile`, and `my-courses` render only the gate card—no catalog rows, filters, course meta, profile form, or my-courses list (no course-list leakage). The LMS subnav does not print on gated views.

**Shell / portal chrome** (`Learner\Shell`) appears only when `should_show( $user_id )` (`can_learn`). `render_chrome()` prints at most once per request (static once-flag; optional `force` for tests) as a Duty Officer–style `.bfc-portal` workspace: uppercase **Training** kicker, learner display name as the document **H1** (or a `<p>` title on course singles so the course post title keeps H1), context lede, then `.bfc-shell` pill-track nav. Primary nav ids: **Catalog** (`get_post_type_archive_link( 'bfc_course' )`), **My Courses** and **Profile** (pages at `/my-courses/` and `/profile/` with `home_url` fallbacks). On `bfc_course` singles, context `course` highlights Catalog; **contextual actions** add Enroll (`#bfc-course-enroll`), Continue (`#bfc-course-quiz` when enrolled and a quiz is mapped), or Withdraw (my-courses URL with `course` query arg) from enrollment state. Pill-track nav (Catalog · My Courses · Profile) stays expanded at all breakpoints; course contextual actions wrap onto a second row when needed.

**Heading ownership:** The catalog block owns the course-archive H1 (“Course catalog”). `Learner\Portal_Headings` suppresses `core/query-title` on the `bfc_course` archive and suppresses `core/heading` level-1 **and** `core/post-title` on `/profile/` and `/my-courses/` so theme patterns do not duplicate the portal/soft-gate title. On course singles, `burbank-lms/course` owns the document H1 (`.bfc-course__title`); `suppress_course_singular_title` drops theme `core/post-title`, and embedded PressPrimer quiz titles are demoted H1→H2.

Optional `burbank-lms/shell` block prints the same chrome then InnerBlocks; learner block renders also call `Shell::render_chrome`, so the once-flag prevents duplicate nav when both run.

**Outside the shell:** `burbank-lms/register` stays public (site invite form; logged-in users see “already signed in”). No soft gate and no shell on register. Public certificate **verify** pages (PressPrimer) likewise stay outside the shell; they may still pick up shared control tokens from the frontend bundle. `burbank-lms/enroll` does not use `Soft_Gate` (short login/access messages only).

**Shared LMS CSS:** portal, pill tabs, panels, control, shell, and soft-gate styles live in `resources/frontend/scss/app.scss`, compiled with the `burbank-lms-frontend` webpack entry to `build/burbank-lms-frontend.css` and enqueued on `wp_enqueue_scripts` (alongside block assets). Tokens prefer CERT presets with **solid** `html[data-bc-theme="dark"]` overrides so controls stay readable when theme presets lag. Pill nav and contextual action links are scoped as `.bfc-portal .bfc-shell a.bfc-shell__*` (with `:link` / `:visited` and `-webkit-text-fill-color`) so the CERT theme rule `.wp-site-blocks main a:not(.wp-block-button__link):not(.has-text-color)` cannot paint content-link + underline on active forest pills or LMS/PressPrimer filled CTAs. Primary quiz/cert CTAs, LMS `a.wp-element-button`, and active certificate filter pills use `--bfc-cta-text` (white) on `--bfc-cta-bg` (action-red); the same inverse rules ship on the late frontend entry so they still win when the PressPrimer skin prints early. Portal adds narrow-viewport padding so content clears typical WP Accessibility / GTranslate floats. PressPrimer quiz/certificate skin remains `build/burbank-lms-pressprimer.css` (see below).

**Intentional differences from Duty Officer:** LMS keeps multi-page route nav (not in-page SPA tabs), soft-gate presentation (not hard login redirect), always-expanded pill nav on mobile (no disclosure), and PHP block rendering (not React). Visual language (kicker / title / lede / mint pill track / forest selected pill / 24px workspace / 16px panels) is shared.

## Invites

One table `{prefix}bfc_invite_codes`, two scopes. Site and course codes are never interchangeable.

| Scope | Purpose | `course_id` |
| --- | --- | --- |
| `site` | Registration / training access | Always `NULL` |
| `course` | Restricted self-enroll for one course | Required |

| Kind | Behavior |
| --- | --- |
| `multi` | Reusable until revoked, expired, or `max_uses` reached (`max_uses` null = unlimited) |
| `once` | Single use (`use_count` must stay `0` while active) |

**Active code** = not revoked, not expired, and (`once` with `use_count = 0`) or (`multi` under `max_uses` if set). Plaintext is shown once at create; storage is hashed (HMAC). Optional `bound_email` on a **site** invite requires the registration email to match; on a **course** invite, `Enrollment\Service` compares it to the learner’s `user_email` before redeem.

Admin UI: **Training → Site invites** for site codes (PHP admin screens); course codes are managed in the Course sidebar **Invites** panel on the block editor (admin REST). Capability: `enroll_bfc_courses` (administrator by default). Site invite admin and user profile Training Access stay PHP for now.

## Registration

Learner registration goes through `Registration::register()` (Register block or `POST /burbank-lms/v1/register`). Required fields:

- site invite code
- email
- password (WordPress-native strength; the invite is never the password)
- first name + last name (composed into the certificate / legal name `burbank_lms_legal_name`; both required when either is present)
- display name

The Register block collects first/last name (hint: certificates print as first + last). API clients may still send a single `legal_name` when first/last keys are omitted.

Flow: validate active **site** invite → create user (with WP `first_name` / `last_name`) → store legal name → grant `burbank_lms_access` → increment invite use. Granting access enables `can_learn` immediately (and fires the welcome email via `Mail\Mailer`). There is no separate email-verification step.

**Compensating transaction (not a single DB transaction):** `wp_insert_user` cannot share a MySQL transaction with invite redeem across WordPress’s user tables reliably. On failure after user create, `Registration::register` compensates by revoking training access (when granted) and deleting the user before returning a `WP_Error`. Course restricted enroll uses a real shared transaction for enrollment insert + invite redeem.

Default `wp-login.php?action=register` for learners is rejected (`registration_errors`). Admin `create_users` stays intact; staff may grant training access without a code.

**Certificate (legal) name:** WordPress first + last name is the single source; the composed string is stored in `burbank_lms_legal_name` for certificates. Learners may change it via the Profile block (`Profile::set_legal_name_self_service` / `POST /profile/legal-name`); changes are audited (`by` = the learner). Admins with `enroll_bfc_courses` may still correct it (audited). Direct usermeta writes stay blocked by the legal-name guards. Certificate merge fields use legal name and never email.

## Suspension

`Learner::suspend()` sets `burbank_lms_suspended = 1` and removes `pressprimer_quiz_take_quiz`. Suspended learners cannot enroll or start mapped quizzes. Past credentials remain readable. `reactivate()` clears the flag and restores the quiz cap when `can_learn` is true again.

## Enrollment

Stored in `{prefix}bfc_enrollments` with unique `(course_id, user_id)`.

| Column | Notes |
| --- | --- |
| `status` | `active` \| `withdrawn` \| `completed` |
| `source` | `self` \| `admin` \| `invite` |
| `invite_code_id` | Course invite used, if any |
| `quiz_passed_at` | First successful pass while enrolled |
| `credential_id` | PressPrimer certificate id after issuance |
| `course_revision_snapshot` | Revision string at enroll; compared to live `course_revision` for retake gating |

Course policy is implicit:

- **Open** — no active course-scoped invites → any `can_learn` user may self-enroll (`source=self`).
- **Restricted** — one or more active course invites → self-enroll must redeem a valid code for that `course_id` in one transaction (`source=invite`).

Publication and archive checks apply to every enroll path. Archived courses (`catalog_state=archived`) reject new enrollments; existing active enrollments may finish unless withdrawn.

| Action | Who | Rules |
| --- | --- | --- |
| Self-enroll (open) | `can_learn` | Published, not archived, no active course invites |
| Self-enroll (restricted) | `can_learn` | Same + redeem course invite |
| Admin enroll | `enroll_bfc_courses` | Publication/archive checks; bypasses course codes (`source=admin`). Course sidebar Enrollments panel or `POST /enrollments/admin` |
| Withdraw | Learner (own) or admin | `status=withdrawn`; blocks quiz until re-enrolled |

Successful enroll fires `burbank_lms_enrolled` (Mailer sends confirmation). The Course sidebar Enrollments panel lists enrollments for the open course via `GET /courses/{id}/enrollments` (`REST\Admin_Controller`).

## Progress and completion

1. Mapped `pressprimer_quiz_quiz_passed` → `Progress\Service` stamps `quiz_passed_at` once on the active enrollment.
2. PressPrimer Certificate issues via its existing `ppq_quiz` trigger (Phase 1 mapping still required).
3. `ppcert_issue_validation` requires active enrollment + `quiz_passed_at` for the mapped course.
4. `ppcert_certificate_issued` stores `credential_id`, sets `status=completed` / `completed_at`, fires `burbank_lms_course_completed` (Mailer sends completion mail).

After a certificate completes the enrollment, learner surfaces hide Enroll/Withdraw for that course version (`Enrollment\Service::credential_locks_actions`). Self/invite enroll is refused with `burbank_enrollment_already_completed` while live `course_revision` matches `course_revision_snapshot`. When editors start a new material revision (different `course_revision`), `retake_available` opens Enroll again; re-enroll reactivates the row and clears progress fields for the new attempt. Admins may still force a retake on the same revision.

Unmapped quizzes (no published `bfc_course` with that `pressprimer_quiz_id`) are not gated by Burbank.

## REST (`burbank-lms/v1`)

### Learner (`REST\Controller`)

| Method | Route | Auth |
| --- | --- | --- |
| `POST` | `/register` | Public (rate-limited); site invite required; accepts `first_name` / `last_name` (or legacy `legal_name`) |
| `GET` | `/enrollments` | Logged-in learner (`can_learn` for useful data) |
| `POST` | `/enrollments` | Logged-in `can_learn`; optional `course_invite` |
| `DELETE` | `/enrollments/{course_id}` | Self or `enroll_bfc_courses` (optional `user_id`) |
| `POST` | `/enrollments/admin` | `enroll_bfc_courses` |
| `POST` | `/profile/legal-name` | Logged-in `can_learn`; audited self-service certificate name |
| `POST` | `/profile/sign-out-others` | Logged-in `can_learn`; destroys other sessions |

### Course editor admin (`REST\Admin_Controller`)

Cookie + REST nonce. Used by the Course sidebar Invites / Enrollments / Approval panels. Full editorial route table is in [course-authoring.md](course-authoring.md). Course-invite and enrollment-list routes:

| Method | Route | Auth |
| --- | --- | --- |
| `GET`/`POST` | `/courses/{id}/invites` | `enroll_bfc_courses` + edit course |
| `DELETE` | `/invites/{id}` | `enroll_bfc_courses` (course-scoped invite) |
| `GET` | `/courses/{id}/enrollments` | `enroll_bfc_courses` + edit course |

Does not proxy PressPrimer attempt APIs. Service `WP_Error` codes map to REST responses.

## Blocks and FSE

The `bfc_course` CPT is public with `has_archive` and archive slug **`courses`** (`Courses\Schema`), so the catalog lives at `/courses/` (pretty permalinks). My Courses and empty-state CTAs use `get_post_type_archive_link( 'bfc_course' )`. When the course **data schema version** upgrades (for example v2 enabling the archive), the plugin flushes rewrite rules once on the next request; if `/courses/` still 404s, save **Settings → Permalinks** once.

### Composition

| Layer | Responsibility |
| --- | --- |
| **Plugin fallback** | `Learner\Templates` registers `burbank-lms//archive-bfc_course` and `burbank-lms//single-bfc_course` so any block theme gets working course routes without custom templates. |
| **Theme (optional)** | Thin FSE shells (header/footer template parts, learner page patterns for `/register/`, `/my-courses/`, `/profile/`) compose the same blocks; the plugin does not create WordPress pages. |

**Archive fallback:** constrained group → “Course catalog” heading → `burbank-lms/catalog`.

**Single fallback:** featured image → **post content** (course materials) → `burbank-lms/course` (H1 title, meta, enrollment status, actions, in-page quiz region). Theme `single-bfc_course` templates should mirror that block order (no competing `core/post-title`).

Quiz taking stays in PressPrimer’s player; the course block embeds `[pressprimer_quiz]` in `#bfc-course-quiz` only when the learner is actively enrolled.

### Blocks

| Block | Role |
| --- | --- |
| `burbank-lms/register` | Site invite + first/last name (certificate name) + email/password/display name; success copy tells the learner they can sign in (no email-verification step). |
| `burbank-lms/profile` | Summary band (training access + enrolled count); personal form (editable first/last → audited certificate name, nickname, display-name select, email); separate security form (password + strength meter + sign out other sessions) |
| `burbank-lms/catalog` | Section head + available count; tile grid (subject badge, title, code/outcome, enrolled **Continue course** / invite View / open Enroll); client-side **search** (title + BFC code) and **subject** filter; archive H1 owned by the block (`core/query-title` suppressed) |
| `burbank-lms/course` | BFC meta row, enrollment status, enroll / invite actions; primary quiz CTA scrolls to `#bfc-course-quiz` when enrolled |
| `burbank-lms/enroll` | Standalone enroll / course-invite redeem |
| `burbank-lms/my-courses` | Stats strip (enrolled / in progress / completed / certificates); enrolled-course tile grid (status badge, meta, Continue/View, withdraw); catalog link; embeds `[ppcert_my_certificates]` when PressPrimer Certificate registers that shortcode |
| `burbank-lms/shell` | Optional Site Editor wrapper: once-per-request training subnav + InnerBlocks slot (same `Shell` API as block render paths) |

### Quiz course chrome (mapped quizzes only)

`Integrations\PressPrimer_Quiz` prints `.bfc-quiz-chrome` (back link to the mapped course + enrollment status label) on PressPrimer hooks:

- `pressprimer_quiz_before_quiz_meta`
- `pressprimer_quiz_before_results` / `pressprimer_quiz_after_results`

Unmapped quizzes get no chrome. On the mapped course single, chrome is skipped (the course block owns enrollment UX). `demote_quiz_title` on `render_block_pressprimer-quiz/quiz` and on shortcode embed output rewrites `.ppq-quiz-title` H1 → H2 so the course page keeps a single H1. Hook contracts: [pressprimer-compatibility.md](pressprimer-compatibility.md).

## Mail and branding

`Mail\Mailer` sends filterable `wp_mail` messages for welcome (on `burbank_lms_access_granted`), enrollment confirmation (on `burbank_lms_enrolled`), and completion (on `burbank_lms_course_completed`). Subject/message/headers filters are namespaced `burbank_lms_*_email_*`. Registration grants training access immediately; there is no verification email or “check your inbox” step in the Register block or welcome flow.

### PressPrimer visual skin (plugin-owned)

Token-mapped PressPrimer Quiz/Certificate CSS is compiled to `build/burbank-lms-pressprimer.css` and enqueued by `Integrations\PressPrimer_Assets` on learner surfaces: `bfc_course` singular/archive, pages containing `burbank-lms/course` or `burbank-lms/my-courses`, and (on singular views) when PressPrimer Quiz or Certificate plugins are present. Variables map to `--wp--preset--color-*` with optional CERT theme vars (`--bc-surface-*`, `html[data-bc-theme]`) when defined. **CERT and other block themes should not enqueue a separate PressPrimer skin** once this plugin build is deployed; the plugin remains usable without a custom theme.

Optional **labels/branding:** set option `burbank_lms_brand_pressprimer` to a truthy value. `Branding\PressPrimer` then overrides PressPrimer `plugin_name` / dashboard logo (and quiz shell branding) filters with Burbank-facing labels. Certificate upsell and mascot filters are not implemented.

## Capabilities

| Cap | Default |
| --- | --- |
| Phase 1 `*_bfc_courses` + `approve_bfc_courses` | Unchanged |
| `enroll_bfc_courses` | Administrator (invites, admin enroll, grant/revoke, suspend, legal-name correction) |
| `burbank_lms_access` | Administrator role; learners after site invite or admin grant (meta mirror `burbank_lms_training_access`) |
| `pressprimer_quiz_take_quiz` | Synced to non-suspended users with training access |

Active enrollment remains the source of truth for taking a specific course’s mapped quiz.

## Tests

`Access*Test`, `Enrollment*Test`, `ProgressPipelineTest`, `PressPrimerGateTest`, `LearnerRestTest`, `CourseEditorRestTest`, `MailerTest`, `LearnerSoftGateTest`, `LearnerShellTest`, `ProfileMyCoursesSoftGateTest`, and block registration coverage run with `npm run test:php`.

### Course journey presentation

The course block uses the learner tile stack: status badge, course title, metadata,
excerpt, mapped quiz region, and action footer. Active enrollments offer Withdraw
through the existing enrollment REST route. Standalone enrollment cards link enrolled
learners back to the course, send invite-only learners to the course invite form, and
replace successful enrollment forms with Continue course. These surfaces show status
labels rather than progress percentages. Public `[ppcert_verify]` pages explicitly load
the PressPrimer skin and remain outside the learner shell.

### Email verification

Invite registration grants training access immediately and explicitly marks the email unverified. Existing accounts with no verification flag retain their prior access; no bulk migration is required. Unverified learners can sign in and see portal navigation, but catalog, course, profile and my-courses bodies show a verification panel. Standalone enrollment also shows that panel. The panel uses an H2 beneath the portal H1.

Self-enrollment (including direct service calls), mapped quiz start/submission and the effective PressPrimer quiz capability require a verified email. Administrator enrollment remains available. Unmapped quiz behavior is unchanged.

Authenticated `POST /burbank-lms/v1/profile/resend-verification` and `POST /burbank-lms/v1/profile/email` support resend and changing the address. Both require training access. Email changes clear verification; the UI updates its address hint after success. Requests are limited to one verification email per minute. The public `?burbank_lms_verify=TOKEN` landing is outside the portal shell and supplies success/expired-link recovery links. Tokens expire after 24 hours, are bound to the current email, stored only as SHA-256 hashes, and consumed once. Registration remains successful if mail fails; the verification panel offers another attempt and send results fire `burbank_lms_sent_verification_email` for monitoring.

Course materials rendered by `core/post-content` are guarded before child blocks or shortcodes execute, including the plugin single-course template. Guests, accounts without access, and unverified learners receive no materials; the course block presents their gate. Editor screens and unrelated post content remain available. Change-email responses include the persisted address even when mail delivery fails, so the panel can show the saved address while reporting the delivery failure.


### React training administration and mail

Training → Site Invites and Training → Training Access load the dedicated `burbank-lms-training-admin` React entry. PHP provides menus, capability checks, mount point and assets; the old profile forms and admin-post UI hooks are retired. The Course editor shares the admin kit (spacing, table borders, notices, button radii).

The management API requires `enroll_bfc_courses` and the standard REST nonce:

- `GET|POST /burbank-lms/v1/training/invites`: list site invites or create one-time/multi-use invites with optional bound email, UTC expiry and use limit. Plaintext is returned only by create; list responses omit the stored hash. The create panel has copy and dismiss controls.
- `DELETE /burbank-lms/v1/training/invites/{id}`: revoke a site invite; course invite IDs are rejected.
- `GET /burbank-lms/v1/training/learners?search=...&page=1`: search login/email/display name, 20 accounts per page with total count, access/suspension/verification and certificate-name status.
- `POST /burbank-lms/v1/training/learners/{id}`: `operation` is `grant`, `revoke`, `suspend`, `reactivate`, `legal_name` (with `legal_name`) or `resend`. Existing domain services perform mutations and audit legal-name corrections. Resend respects the same verification requirement and throttle as learner requests.

All welcome, enrollment, completion and verification/resend mail uses one branded HTML template. Existing message/subject/header filters and sent actions remain available. The plain message becomes a real multipart alternative via the Loader-registered `phpmailer_init` adapter. A private encoded header carries the alternative within the synchronous send and is removed before transmission; WordPress resets its reused mailer for subsequent unrelated messages. Mail transport failures retain the existing nonblocking registration behavior and report errors to resend callers. Visual checks in real mail clients remain part of deployment QA.
