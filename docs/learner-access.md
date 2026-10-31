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
| Learner REST register / enroll / withdraw | `REST\Controller` (`burbank-lms/v1`) |
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

`Learner::can_learn( $user_id )` is true when the user has `burbank_lms_access` and is not suspended. Self-enroll and mapped quiz start/submit require it. Administrators receive `burbank_lms_access` from the capabilities installer, so they can enroll and take courses without a separate grant.

| State | Catalog CTAs | Self-enroll | Mapped quiz | Own cert history |
| --- | --- | --- | --- | --- |
| No training access | No | No | No | No |
| Training access | Yes | Per course policy | With enrollment | Yes |
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

**Active code** = not revoked, not expired, and (`once` with `use_count = 0`) or (`multi` under `max_uses` if set). Plaintext is shown once at create; storage is hashed (HMAC). Optional `bound_email` on a **site** invite requires the registration email to match; on a **course** invite, `Enrollment\Service` compares it to the learner’s `user_email` before redeem.

Admin UI: **Training → Site invites** for site codes (PHP admin screens); course codes are managed in the Course sidebar **Invites** panel on the block editor (admin REST). Capability: `enroll_bfc_courses` (administrator by default). Site invite admin and user profile Training Access stay PHP for now.

## Registration

Learner registration goes through `Registration::register()` (Register block or `POST /burbank-lms/v1/register`). Required fields:

- site invite code
- email
- password (WordPress-native strength; the invite is never the password)
- legal / certificate name (`burbank_lms_legal_name`)
- display name

Flow: validate active **site** invite → create user → store legal name → grant `burbank_lms_access` → increment invite use. Granting access enables `can_learn` immediately (and fires the welcome email via `Mail\Mailer`). There is no separate email-verification step.

**Compensating transaction (not a single DB transaction):** `wp_insert_user` cannot share a MySQL transaction with invite redeem across WordPress’s user tables reliably. On failure after user create, `Registration::register` compensates by revoking training access (when granted) and deleting the user before returning a `WP_Error`. Course restricted enroll uses a real shared transaction for enrollment insert + invite redeem.

Default `wp-login.php?action=register` for learners is rejected (`registration_errors`). Admin `create_users` stays intact; staff may grant training access without a code.

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
| Admin enroll | `enroll_bfc_courses` | Publication/archive checks; bypasses course codes (`source=admin`). Course sidebar Enrollments panel or `POST /enrollments/admin` |
| Withdraw | Learner (own) or admin | `status=withdrawn`; blocks quiz until re-enrolled |

Successful enroll fires `burbank_lms_enrolled` (Mailer sends confirmation). The Course sidebar Enrollments panel lists enrollments for the open course via `GET /courses/{id}/enrollments` (`REST\Admin_Controller`).

## Progress and completion

1. Mapped `pressprimer_quiz_quiz_passed` → `Progress\Service` stamps `quiz_passed_at` once on the active enrollment.
2. PressPrimer Certificate issues via its existing `ppq_quiz` trigger (Phase 1 mapping still required).
3. `ppcert_issue_validation` requires active enrollment + `quiz_passed_at` for the mapped course.
4. `ppcert_certificate_issued` stores `credential_id`, sets `status=completed` / `completed_at`, fires `burbank_lms_course_completed` (Mailer sends completion mail).

Unmapped quizzes (no published `bfc_course` with that `pressprimer_quiz_id`) are not gated by Burbank.

## REST (`burbank-lms/v1`)

### Learner (`REST\Controller`)

| Method | Route | Auth |
| --- | --- | --- |
| `POST` | `/register` | Public (rate-limited); site invite required |
| `GET` | `/enrollments` | Logged-in learner (`can_learn` for useful data) |
| `POST` | `/enrollments` | Logged-in `can_learn`; optional `course_invite` |
| `DELETE` | `/enrollments/{course_id}` | Self or `enroll_bfc_courses` (optional `user_id`) |
| `POST` | `/enrollments/admin` | `enroll_bfc_courses` |

### Course editor admin (`REST\Admin_Controller`)

Cookie + REST nonce. Used by the Course sidebar Invites / Enrollments / Approval panels. Full editorial route table is in [course-authoring.md](course-authoring.md). Course-invite and enrollment-list routes:

| Method | Route | Auth |
| --- | --- | --- |
| `GET`/`POST` | `/courses/{id}/invites` | `enroll_bfc_courses` + edit course |
| `DELETE` | `/invites/{id}` | `enroll_bfc_courses` (course-scoped invite) |
| `GET` | `/courses/{id}/enrollments` | `enroll_bfc_courses` + edit course |

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

`Mail\Mailer` sends filterable `wp_mail` messages for welcome (on `burbank_lms_access_granted`), enrollment confirmation (on `burbank_lms_enrolled`), and completion (on `burbank_lms_course_completed`). Subject/message/headers filters are namespaced `burbank_lms_*_email_*`.

Optional branding: set option `burbank_lms_brand_pressprimer` to a truthy value. `Branding\PressPrimer` then overrides PressPrimer `plugin_name` / dashboard logo (and quiz shell branding) filters with Burbank-facing labels. Certificate upsell and mascot filters are not implemented.

## Capabilities

| Cap | Default |
| --- | --- |
| Phase 1 `*_bfc_courses` + `approve_bfc_courses` | Unchanged |
| `enroll_bfc_courses` | Administrator (invites, admin enroll, grant/revoke, suspend, legal-name correction) |
| `burbank_lms_access` | Administrator role; learners after site invite or admin grant (meta mirror `burbank_lms_training_access`) |
| `pressprimer_quiz_take_quiz` | Synced to non-suspended users with training access |

Active enrollment remains the source of truth for taking a specific course’s mapped quiz.

## Tests

`Access*Test`, `Enrollment*Test`, `ProgressPipelineTest`, `PressPrimerGateTest`, `LearnerRestTest`, `CourseEditorRestTest`, `MailerTest`, and block registration coverage run with `npm run test:php`.
