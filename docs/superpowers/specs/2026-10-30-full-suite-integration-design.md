# Full suite integration — Burbank LMS × Pods × PressPrimer

## Intent and accepted decisions

Build the complete Burbank-owned learner and authoring suite on top of Phase 1 course catalog controls. PressPrimer Quiz 3.1.3 and Certificate 2.0.2 remain the engines for assessment and credentials. Pods remains the schema/UI layer for `bfc_course`. The public program matrix (`bc_level`, `bc_requirement`) stays owned by the `burbankcert-fse` theme; the LMS hardens the existing slug link only.

| Topic | Decision |
| --- | --- |
| Stack scope | Burbank-owned Pods + PressPrimer Quiz + Certificate. No LearnDash / LearnPress / Lifter / Tutor bridges. No Certificate upsell/mascot plugs. |
| Architecture | Domain modules + thin adapters (Approach 1). Business rules live in Burbank services; `Integrations/PressPrimer*` only translate hooks and model calls. |
| Delivery | One monolithic design and implementation plan/PR covering the full suite. |
| Site access | **Site invite required** to register a WordPress account and receive training access. No open self-registration into the training audience. Admins may create users and grant training access without a code. |
| Registration profile | **Legal/certificate name required at registration** (decision A). Display name, email, and password also collected. Email verification required before enroll / quiz / certificate history. |
| Enrollment | Stored course↔user rows. Only **verified** users with **training access** who are not suspended may enroll or take mapped quizzes. Courses are **open** (any such user may self-enroll) unless they have active **course** invite codes — then self-enroll requires a valid code for **that course**. Site invites never enroll someone into a restricted course. Admins may always enroll. |
| Invite codes | Two scopes: **site** (signup / training access) and **course** (per-course gate). Both support multi-use and one-time codes; optional max uses and expiry; revocable. |
| Completion | Track quiz passed and certificate issued. Course is **completed** when the certificate has been issued; quiz pass is the prerequisite milestone. |
| Learner UI | Gutenberg blocks primary (FSE). Block theme templates for `bfc_course` archive/single. Shortcodes only if something outside the site editor needs them later. |
| Program matrix | Path **A**: theme keeps `bc_level` / `bc_requirement`. LMS optionally picks/validates `matrix_requirement_slug` when those types exist. No local membership advancement. cert.vip remains the official volunteer record. Accounts are independent of cert.vip (no SSO/sync). |

Phase 1 remains authoritative for BFC identity, approval snapshots, publication gates, archive, and PressPrimer mapping validation. This suite adds site-invite registration / training access, course enrollment (open vs restricted), progress, merge fields, learner blocks, admin polish on Burbank surfaces, and branded/email/AI hooks that touch courses — without forking Quiz or Certificate.

## Out of scope

- Foreign LMS bridges and Certificate upgrade/upsell surfaces.
- Moving `bc_level` / `bc_requirement` schema ownership into the LMS (follow-on cross-repo migration if desired later).
- Recording WordPress-side “requirement satisfied” for membership advancement.
- cert.vip API, SSO, or sync.
- Curricula / multi-course programs as first-class objects (levels remain theme matrix; courses remain single-assessment units).
- Replacing PressPrimer grading, attempt storage, PDF rendering, or verification pages.
- **VentraConnect** (and similar social/passwordless login plugins) for this pass — optional later as login-only polish; Burbank owns registration, invites, and profile policy with core WordPress auth.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│  Blocks / FSE templates / Admin meta boxes              │
└───────────────────────────┬─────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────┐
│  Domain                                                 │
│  Courses/* · Access/* (site invites + training flag)    │
│  Enrollment/* · Progress/* · Learner/* · Mail/*         │
└─────────────┬───────────────────────────┬───────────────┘
              │                           │
┌─────────────▼──────────┐   ┌────────────▼───────────────┐
│ Integrations/PressPrimer│   │ Pods (bfc_course + picks)  │
│ Quiz + Certificate hooks│   │ optional bc_requirement   │
└─────────────────────────┘   └───────────────────────────┘
```

Internal fan-out uses a small set of Burbank actions (for example `burbank_lms_course_completed`) so email and admin notices do not bind directly to PressPrimer hooks.

Hook registration continues through `Loader` in `src/Burbank_LMS.php`. Version pins and fail-closed dependency checks stay in `Integrations/PressPrimer`.

### Module map

| Module | Responsibility |
| --- | --- |
| `Courses/*` | Phase 1 schema, registry, validation, editorial, publication, admin list; extend picks and matrix soft-check |
| `Access/Registration` | Site-invite registration gate; email verification; grant/revoke training access; suspend/reactivate |
| `Access/Profile` | Learner profile fields (legal name, display name); admin name-correction audit |
| `Access/Invites` | Shared invite CRUD/hash/use-count for `scope=site` and `scope=course` |
| `Enrollment/Service` | Course enroll, withdraw, course-invite redeem, admin assign |
| `Enrollment/Repository` | Table access for enrollments |
| `Progress/Service` | `quiz_passed_at`, completion on certificate issued |
| `Integrations/PressPrimer` | Version pin, mapping validation (existing) |
| `Integrations/PressPrimer_Quiz` | `can_start`, `before_submit`, pass/fail/attempt hooks, embed chrome filters |
| `Integrations/PressPrimer_Certificate` | Merge fields, before_issue/validation, issued/revoked, branding filters that touch Burbank copy |
| `Learner/Catalog` | Queries for catalog/course blocks (published, not archived; gated by training access) |
| `Blocks/*` | Register, catalog, course, enroll, my-courses blocks + FSE templates |
| `Mail/*` | Site-invite welcome, enrollment, and completion emails |
| `Branding/*` | Optional filters for PP plugin_name/logo only when Burbank should own learner-facing strings |

## Data model

### Training access and profile (user)

| Key | Storage | Notes |
| --- | --- | --- |
| Training access | Cap `burbank_lms_access` (+ meta mirror `burbank_lms_training_access` for queries) | Granted on site-invite redeem or admin grant |
| Site invite audit | Meta `burbank_lms_site_invite_id` | Optional |
| Suspended | Meta `burbank_lms_suspended` = `1` | Blocks enroll / quiz; past certs remain viewable to the learner |
| Legal / certificate name | Meta `burbank_lms_legal_name` | **Required at registration**; used on PDFs / merge fields |
| Display name | WP `display_name` | Learner-editable; UI only |
| Email verified | WP flow + meta/timestamp as needed | Unverified → no enroll / quiz / cert history |

Users without `burbank_lms_access`, or who are suspended / unverified, cannot self-enroll or start mapped quizzes — even if logged in.

### Course (`bfc_course`)

Keep Phase 1 assessed and editorial fields. Course invite policy is **implicit**:

- **Open course** — no active **course-scoped** invite codes → any user with training access may self-enroll.
- **Restricted course** — one or more active course-scoped invite codes → self-enroll requires redeeming a valid code for **that** `course_id`. Site invites do not satisfy this gate.

No mode enum on the course.

### `{prefix}bfc_enrollments`

| Column | Notes |
| --- | --- |
| `id` | PK |
| `course_id`, `user_id` | Unique pair |
| `status` | `active` \| `withdrawn` \| `completed` |
| `source` | `self` \| `admin` \| `invite` |
| `invite_code_id` | Nullable (course invite used, if any) |
| `enrolled_at`, `completed_at` | |
| `quiz_passed_at` | First successful pass while enrolled |
| `credential_id` | PressPrimer certificate credential id from issuance |
| `course_revision_snapshot` | Revision string at enroll (audit only; completion is not revision-bound) |

### `{prefix}bfc_invite_codes`

One table, two scopes:

| Column | Notes |
| --- | --- |
| `id` | PK |
| `scope` | `site` \| `course` |
| `course_id` | Required when `scope=course`; **NULL** when `scope=site` |
| `code_hash` | Store hash; plaintext shown once at create |
| `label` | Admin note |
| `kind` | `multi` \| `once` |
| `max_uses` | Null = unlimited (multi only) |
| `use_count` | |
| `expires_at`, `revoked_at` | Nullable |
| `created_by`, `created_at` | |

**Active code** = not revoked, not expired, and (`once` with `use_count = 0`) or (`multi` under `max_uses` if set).

Site codes and course codes are never interchangeable: redeeming a site code cannot enroll into a course; redeeming a course code cannot grant site registration / training access.

Install tables with versioned options (same pattern as the BFC registry). Uninstall retains enrollment and invite tables (Phase 1 `Uninstallor` is a no-op today); training history must not vanish on accidental delete.

## Access ladder

```
Site invite (or admin grant)
        │
        ▼
WordPress user + training access
        │
        ├── Open course ──────────► self-enroll (no course code)
        │
        └── Restricted course ────► course invite (or admin enroll)
                │
                ▼
        Active enrollment → quiz → pass → certificate → completed
```

## Registration and volunteer profiles

### Registration flow

1. Volunteer uses the **Register** block (not open public `wp-login.php?action=register` — alternate learner registration paths are rejected; admin `create_users` stays intact).
2. Required fields: site invite code, email, password (WP-native strength/reset — invite is never the password), **legal/certificate name**, display name.
3. Atomic redeem: validate active **site** invite → create user → store legal name → grant `burbank_lms_access` → increment invite use → send email verification.
4. Until email verified: login allowed; account shows verify prompt; **no** enroll, mapped quiz, or certificate history.
5. After verify: full learner ladder (open vs restricted courses).
6. Hardening: hashed invites, rate limits, non-enumerating errors, audited issue/revoke, no plaintext codes in logs. Optional email-binding on a site invite (redeem email must match) when staff set it.

### Access matrix

| State | Catalog CTAs | Self-enroll | Mapped quiz | Own cert history |
| --- | --- | --- | --- | --- |
| No training access | No | No | No | No |
| Access, email unverified | No | No | No | No |
| Verified + access | Yes | Per course policy | With enrollment | Yes |
| Suspended | No | No | No | Yes (read-only past credentials) |

### Profile management

| Field | Who edits | Rules |
| --- | --- | --- |
| Display name | Learner | UI label only |
| Email | Learner | Change forces re-verification; quiz/enroll pause until verified again |
| Password | Learner | WP lost-password / account forms |
| Legal / certificate name | Learner at registration; afterward **admin correction only** (audited) | Drives certificate merge fields; never put email/DOB/volunteer ID on public verify pages |
| Training access / suspend | Admin (`enroll_bfc_courses`) | Grant, revoke, suspend, reactivate |
| Lost invite / duplicate account | Admin | Revoke old site invite; issue new code or resolve duplicate by policy — no silent dual learner identities |

`burbank-lms/profile` block: learner view/edit for display name, email, password; read-only legal name with “contact training admin to correct” copy.

### Admin lifecycle

Issue/revoke site and course invites; grant/revoke access; suspend/reactivate; audited legal-name correction; export/deletion requests with retention of already-issued credentials for verification. Accounts remain independent of cert.vip.

## Enrollment and course access

| Action | Who | Rules |
| --- | --- | --- |
| Self-enroll (open course) | Verified, non-suspended user with `burbank_lms_access` | Course published and `catalog_state ≠ archived`; no active course-scoped invites on that course. `source=self`. |
| Self-enroll (restricted) | Same | Same publication/archive checks + redeem valid **course** invite for that `course_id`. `source=invite`. |
| Admin enroll | `enroll_bfc_courses` | Publication/archive checks; bypasses course codes; may enroll users who lack training access only if admin also grants training access in the same flow (or user already has it). `source=admin`. |
| Withdraw | Learner (own) or admin | `withdrawn`; blocks quiz until re-enrolled. |
| Redeem site invite | During registration | Transaction: validate site code + create user + legal name + training access + increment use count. |
| Redeem course invite | During self-enroll on restricted course | Transaction: validate course code + insert enrollment + increment use count. |

### Quiz gates

| Hook | Behavior |
| --- | --- |
| `pressprimer_quiz_can_start` | `burbank_lms_access` + email verified + not suspended + mapped published `bfc_course` + active enrollment. |
| `pressprimer_quiz_before_submit` | Same checks again. |

Assign PressPrimer `pressprimer_quiz_take_quiz` only to verified, non-suspended learners with `burbank_lms_access`. Unmapped quizzes (no published Burbank course) are not gated by Burbank.

### Completion pipeline

1. `pressprimer_quiz_quiz_passed` → set `quiz_passed_at` on active enrollment (once).
2. Existing Certificate `ppq_quiz` trigger issues (Phase 1 publish mapping still required).
3. `ppcert_before_issue` / `ppcert_issue_validation` → require active enrollment + `quiz_passed_at` for the course tied to that quiz/template.
4. `ppcert_certificate_issued` → store credential; `status=completed`; `completed_at`; fire `burbank_lms_course_completed`.
5. Attempt started/submitted hooks may write audit meta on the enrollment when useful; they do not alone complete the course.

Archived courses leave the catalog. Existing active enrollments may finish unless withdrawn.

## Authoring and PressPrimer wiring

### Pods picks

| Field | Target | Notes |
| --- | --- | --- |
| `pressprimer_quiz_id` | Published quizzes | Exclude draft/archived |
| `certificate_template_id` | Published templates | Publish still runs full Phase 1 trigger validation |
| `matrix_requirement_slug` | Published `bc_requirement` when type exists | Persist **slug**, not ID; fall back to text input if type absent |

### Publish gates

Phase 1 gates unchanged. Add: if `bc_requirement` exists and slug is non-empty but no published match → block publish with an actionable error. If the type is absent → do not fail (matrix optional).

### Sync adapters

Listen to quiz/template lifecycle hooks (created/updated/published/deleted/trashed/duplicated/issuer changed). Refresh admin notices and invalidate caches. Do not auto-mutate published assessed course fields.

### Merge fields

Register and fill at least: `bfc_code`, course title, `course_revision`, `course_outcome`, `subject_category`, `matrix_requirement_slug`, resolvable requirement title, learner **legal/certificate name** (not email).

## Learner UI (blocks + FSE)

| Surface | Role |
| --- | --- |
| `burbank-lms/register` | Site invite + legal name + email/password/display name; creates user + training access; starts email verification |
| `burbank-lms/profile` | Learner account: display name, email, password; read-only legal name |
| `burbank-lms/catalog` | Discoverable published courses; enroll CTAs only when verified + access |
| `burbank-lms/course` | Summary; open-course enroll CTA **or** course-invite field when restricted; quiz link when enrolled |
| `burbank-lms/enroll` | Standalone enroll / course-invite redeem for a selected course |
| `burbank-lms/my-courses` | Learner’s enrollments and status (active / passed / completed) |
| Block templates | `archive-bfc_course`, `single-bfc_course` so FSE works without hand-placing blocks |

Quiz taking continues through PressPrimer’s player; Burbank wraps via before/after quiz and results hooks for course chrome (title, status, back-to-course). Certificate history/verify remain PP shortcodes/blocks embedded or linked from My Courses when enrolled/completed.

REST: Burbank-owned routes for site-invite registration (or authenticated grant), enroll/withdraw, and course-invite redeem. Do not proxy PressPrimer attempt mutation through Burbank REST; gates stay on PP hooks.

## Capabilities, mail, branding, admin polish, AI

### Capabilities

| Cap | Default |
| --- | --- |
| Phase 1 `*_bfc_courses` + `approve_bfc_courses` | Unchanged |
| `enroll_bfc_courses` | Administrator (site + course invite CRUD, admin enroll, grant/revoke access, suspend, legal-name correction) |
| `burbank_lms_access` | Learners after site invite or admin grant (meta mirror for queries) |
| `pressprimer_quiz_take_quiz` | Only verified, non-suspended users with `burbank_lms_access` |

**Active enrollment** remains the source of truth for taking a specific course’s quiz, on top of the caps above.

Bump `burbank_lms_course_capabilities_version` when granting new defaults (same idempotent pattern as Phase 1). Do not give learners `edit_posts`, `manage_options`, or PressPrimer management caps.

### Mail

Send Burbank-owned emails for: email verification, site-invite welcome / training access granted, self/admin enrollment confirmation, course-invite redemption success, course completed (after certificate issued). Prefer `wp_mail` with filterable templates. Optionally append course context onto PP certificate email content filters when the issuance is for a mapped course — do not replace PP’s certificate mail pipeline.

### Branding

Where PP exposes plugin_name / logo filters on learner-facing chrome that Burbank wraps, allow optional Burbank branding overrides. Do not implement Certificate upsell or mascot filters.

### Admin polish

Global admin: site invite manager. Course admin: enrollments list, **course** invite manager, progress columns. User profile (wp-admin): training access grant/revoke, suspend/reactivate, audited legal-name correction, site-invite audit. Align list filters with catalog state. Surface PP admin notices only when they affect mapped courses.

### AI

Do not build a parallel AI stack. If PressPrimer AI is used while authoring a quiz linked to a course, optional filters may pass course title/outcome as context. No requirement to ship AI settings UI inside Burbank.

## Error handling

- Fail closed on unsupported PressPrimer versions (existing).
- Enrollment/invite operations return `WP_Error` with machine codes for REST and admin notices.
- Quiz gate denials use PP’s expected filter/return shapes so the player shows a clear blocked state.
- Issue validation errors surface through `ppcert_issue_validation` so staff see course-level reasons.
- Matrix type missing never breaks enroll, quiz, or certificate paths.

## Testing

Extend the isolated PHPUnit environment (Pods present; PressPrimer fixtures as today):

- Site invite required for registration; legal name required; site vs course codes not interchangeable.
- Unverified and suspended users blocked from enroll / `can_start`; suspended users retain read-only cert history.
- Open course (no course codes) vs restricted course (course codes on); multi vs once; expiry; revoke; max uses; admin bypass.
- Alternate WP registration paths rejected for learners; admin `create_users` still works.
- `can_start` / `before_submit` for enrolled, withdrawn, archived, unmapped quiz.
- Legal-name correction is admin-audited; merge fields use legal name.
- Pass → issue → complete pipeline; idempotent `quiz_passed_at`; completion only after issued.
- Matrix slug validate when `bc_requirement` stub exists; ignore when absent.
- Caps for enroll admin vs editor vs learner with/without training access.
- Block registration and asset presence (existing block registration guard pattern).
- PHPStan / PHPCS / existing CI.

## Documentation

- Update `docs/course-authoring.md` and add learner/enrollment ops docs.
- Update `docs/pressprimer-compatibility.md` with gate, merge, and issue hooks actually consumed.
- Update `AGENTS.md` feature quick reference for the full suite.
- Research inventories under `docs/superpowers/research/` remain the hook/API catalog; the playbook’s “defer” list is superseded by this spec’s out-of-scope list.

## Implementation boundary note

Ship as one plan and one integration PR series on the LMS repo. Theme changes are **not** required for Path A. If a later project moves matrix ownership into the LMS, treat it as a separate design with an explicit dual-writer cutover.
