# Learner access, enrollment, and progress

Operational guide for the Burbank-owned learner suite on top of Phase 1 course authoring. Design source: `docs/superpowers/specs/2026-10-30-full-suite-integration-design.md`. Course publication and BFC identity remain in [course-authoring.md](course-authoring.md). PressPrimer hook contracts are in [pressprimer-compatibility.md](pressprimer-compatibility.md).

## Code map

| Concern | Class |
| --- | --- |
| Invite table install | `Access\Tables` |
| Site and course invite CRUD / redeem | `Access\Invites` |
| Site-invite registration, email verification, alternate-path block | `Access\Registration` |
| Training access, suspend, `can_learn`, quiz-cap sync | `Access\Learner` |
| Legal name guards and admin correction audit | `Access\Profile` |
| Site invite admin + user profile + course invite meta box | `Access\Admin` |
| Enrollment table install | `Enrollment\Tables` |
| Enroll / withdraw / restricted redeem | `Enrollment\Service`, `Enrollment\Repository` |
| Course enrollments meta box | `Enrollment\Admin` |
| Quiz pass stamp and certificate completion | `Progress\Service` |
| Catalog queries and FSE templates | `Learner\Catalog`, `Learner\Templates`, `Learner\Block_Context` |
| REST register / enroll / withdraw | `REST\Controller` (`burbank-lms/v1`) |
| Lifecycle emails | `Mail\Mailer` |
| Optional PressPrimer chrome branding | `Branding\PressPrimer` |
| Quiz / certificate adapters | `Integrations\PressPrimer_Quiz`, `Integrations\PressPrimer_Certificate` |

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

`Learner::can_learn( $user_id )` is true only when the user has `burbank_lms_access`, has verified email (`burbank_lms_email_verified_at`), and is not suspended. Self-enroll and mapped quiz start/submit require it.

| State | Catalog CTAs | Self-enroll | Mapped quiz | Own cert history |
| --- | --- | --- | --- | --- |
| No training access | No | No | No | No |
| Access, email unverified | No | No | No | No |
| Verified + access | Yes | Per course policy | With enrollment | Yes |
| Suspended | No | No | No | Yes (read-only past credentials) |

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

**Active code** = not revoked, not expired, and (`once` with `use_count = 0`) or (`multi` under `max_uses` if set). Plaintext is shown once at create; storage is hashed (HMAC). Optional email-binding on a site invite requires the redeem email to match.

Admin UI: **Training → Site invites** for site codes; course edit screen meta box for course codes. Capability: `enroll_bfc_courses` (administrator by default).

## Registration and verification

Learner registration goes through `Registration::register()` (Register block or `POST /burbank-lms/v1/register`). Required fields:

- site invite code
- email
- password (WordPress-native strength; the invite is never the password)
- legal / certificate name (`burbank_lms_legal_name`)
- display name

Atomic flow: validate active **site** invite → create user → store legal name → grant `burbank_lms_access` → increment invite use → send verification email via `Mail\Mailer`.

Default `wp-login.php?action=register` for learners is rejected (`registration_errors`). Admin `create_users` stays intact; staff may grant training access without a code.

Until email is verified: login is allowed; enroll, mapped quiz, and certificate history stay blocked. Changing email clears verification and pauses enroll/quiz until the learner verifies again.

Legal name is set at registration. Afterward only an admin with `enroll_bfc_courses` may correct it (audited). Certificate merge fields use legal name and never email.

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
| `course_revision_snapshot` | Revision string at enroll (audit only) |

Course policy is implicit:

- **Open** — no active course-scoped invites → any `can_learn` user may self-enroll (`source=self`).
- **Restricted** — one or more active course invites → self-enroll must redeem a valid code for that `course_id` in one transaction (`source=invite`).

Publication and archive checks apply to every enroll path. Archived courses (`catalog_state=archived`) reject new enrollments; existing active enrollments may finish unless withdrawn.

| Action | Who | Rules |
| --- | --- | --- |
| Self-enroll (open) | `can_learn` | Published, not archived, no active course invites |
| Self-enroll (restricted) | `can_learn` | Same + redeem course invite |
| Admin enroll | `enroll_bfc_courses` | Publication/archive checks; bypasses course codes (`source=admin`) |
| Withdraw | Learner (own) or admin | `status=withdrawn`; blocks quiz until re-enrolled |

Successful enroll fires `burbank_lms_enrolled` (Mailer sends confirmation).

## Progress and completion

1. Mapped `pressprimer_quiz_quiz_passed` → `Progress\Service` stamps `quiz_passed_at` once on the active enrollment.
2. PressPrimer Certificate issues via its existing `ppq_quiz` trigger (Phase 1 mapping still required).
3. `ppcert_issue_validation` requires active enrollment + `quiz_passed_at` for the mapped course.
4. `ppcert_certificate_issued` stores `credential_id`, sets `status=completed` / `completed_at`, fires `burbank_lms_course_completed` (Mailer sends completion mail).

Unmapped quizzes (no published `bfc_course` with that `pressprimer_quiz_id`) are not gated by Burbank.

## REST (`burbank-lms/v1`)

| Method | Route | Auth |
| --- | --- | --- |
| `POST` | `/register` | Public (rate-limited); site invite required |
| `GET` | `/enrollments` | Logged-in learner (`can_learn` for useful data) |
| `POST` | `/enrollments` | Logged-in `can_learn`; optional `course_invite` |
| `DELETE` | `/enrollments/{course_id}` | Self or `enroll_bfc_courses` (optional `user_id`) |
| `POST` | `/enrollments/admin` | `enroll_bfc_courses` |

Does not proxy PressPrimer attempt APIs. Service `WP_Error` codes map to REST responses.

## Blocks and FSE

| Block | Role |
| --- | --- |
| `burbank-lms/register` | Site invite + legal name + email/password/display name |
| `burbank-lms/profile` | Display name / email / password; read-only legal name |
| `burbank-lms/catalog` | Published, non-archived courses; enroll CTAs when `can_learn` |
| `burbank-lms/course` | Summary; open enroll or course-invite field; quiz link when enrolled |
| `burbank-lms/enroll` | Standalone enroll / course-invite redeem |
| `burbank-lms/my-courses` | Learner enrollments (`active` / `passed` / `completed`) |

`Learner\Templates` registers `archive-bfc_course` and `single-bfc_course` so FSE works without hand-placing blocks. Quiz taking stays in PressPrimer’s player.

## Mail and branding

`Mail\Mailer` sends filterable `wp_mail` messages for verification, welcome (on `burbank_lms_access_granted`), enrollment confirmation (on `burbank_lms_enrolled`), and completion (on `burbank_lms_course_completed`). Subject/message/headers filters are namespaced `burbank_lms_*_email_*`.

Optional branding: set option `burbank_lms_brand_pressprimer` to a truthy value. `Branding\PressPrimer` then overrides PressPrimer `plugin_name` / dashboard logo (and quiz shell branding) filters with Burbank-facing labels. Certificate upsell and mascot filters are not implemented.

## Capabilities

| Cap | Default |
| --- | --- |
| Phase 1 `*_bfc_courses` + `approve_bfc_courses` | Unchanged |
| `enroll_bfc_courses` | Administrator (invites, admin enroll, grant/revoke, suspend, legal-name correction) |
| `burbank_lms_access` | Learners after site invite or admin grant (meta mirror `burbank_lms_training_access`) |
| `pressprimer_quiz_take_quiz` | Synced to verified, non-suspended users with training access |

Active enrollment remains the source of truth for taking a specific course’s mapped quiz.

## Tests

`Access*Test`, `Enrollment*Test`, `ProgressPipelineTest`, `PressPrimerGateTest`, `LearnerRestTest`, `MailerTest`, and block registration coverage run with `npm run test:php`.
