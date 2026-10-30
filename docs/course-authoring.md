# Course authoring and editorial controls

Operational guide for the Phase 1 course catalog: how Burbank LMS provisions courses, who may edit, approve and publish them, and which rules protect course identity and approval history. Design source: `docs/superpowers/specs/2026-10-30-course-catalog-design.md`. PressPrimer requirements are in [pressprimer-compatibility.md](pressprimer-compatibility.md).

Phase 1 supplies authoring, approval and admin list controls only. It creates no learner catalog, curricula, quiz questions, certificates, program approvals or learner progress, and changes no membership eligibility.

## Code map

| Concern | Class |
| --- | --- |
| Pods schema, post meta registration, dependency/conflict notices | `Burbank_LMS\Courses\Schema` |
| Editorial capabilities | `Burbank_LMS\Courses\Capabilities` |
| Permanent BFC reservations (`{prefix}bfc_course_registry`) | `Burbank_LMS\Courses\Registry` |
| Field validation and write guards on every save path | `Burbank_LMS\Courses\Validator` |
| Publication gates | `Burbank_LMS\Courses\Publication` |
| Approval, material revisions, snapshots, archive, delete guard | `Burbank_LMS\Courses\Editorial` |
| Meta box, admin actions, list columns, notices | `Burbank_LMS\Courses\Admin` |
| PressPrimer contract checks | `Burbank_LMS\Integrations\PressPrimer` |

All hooks are registered through `Loader` in `src/Burbank_LMS.php`.

## Pods provisioning and schema ownership

Pods is a required dependency (`Requires Plugins: pods`). On `init` (priority 12) `Schema::register()` provisions the `bfc_course` pod through the Pods API:

- Post type `bfc_course` with meta storage, REST enabled, Gutenberg editor, title, excerpt, featured image, revisions and custom fields.
- Custom capability type `bfc_course`, so the post type uses the `*_bfc_courses` capabilities below.
- The fields in `Schema::FIELDS`, saved one at a time. Each field is also registered as single-valued post meta exposed in REST.

Provisioning is versioned and idempotent:

| Option | Meaning |
| --- | --- |
| `burbank_lms_course_schema_owned` | Burbank LMS created this pod and may update it. |
| `burbank_lms_course_schema_version` | Data schema version (currently `1`). Provisioning reruns only when the code version is higher or the pod is missing. |
| `burbank_lms_course_editorial_ui_version` | UI-only field settings (length limits, number formats, help text, hidden audit fields). Changing these does not bump the data schema version. |
| `burbank_lms_course_registry_version` | Registry table installed and existing codes backfilled. |

Repeated provisioning never deletes course posts or metadata.

### Failure and conflict behavior

Course initialization stops, and administrators (`manage_options`) see an error notice, when:

- **Pods is missing or inactive.** Notice: *Burbank LMS requires Pods…* Existing course posts and metadata are left alone.
- **An incompatible `bfc_course` exists.** This covers a `bfc_course` pod that Burbank LMS did not create, a pod that is not a meta-stored post type, or a registered `bfc_course` post type that does not use `edit_bfc_courses`. Burbank LMS reports the conflict and does not replace the definition. Remove or rename the conflicting type, then reload an admin page.
- **Pods rejects a save.** Notice: *could not configure its Pods course schema*. The version option is not advanced, so the next request retries.

Registry installation runs on activation and on `init`. It requires InnoDB. If installation or backfill fails, administrators see a notice and the version option is not set.

## Capabilities

`Capabilities::install()` runs on every `init` and only adds capabilities that are missing. It does not create or replace roles.

| Role | Capabilities |
| --- | --- |
| Editor (Training Editor) | `edit_bfc_courses`, `edit_others_bfc_courses`, `edit_private_bfc_courses`, `edit_published_bfc_courses`, `publish_bfc_courses`, `read_private_bfc_courses`, `delete_bfc_courses`, `delete_others_bfc_courses`, `delete_private_bfc_courses`, `delete_published_bfc_courses` |
| Administrator (Training Administrator) | All of the above plus `approve_bfc_courses` |

Editors can draft, edit and publish courses, but publication still requires a stored approval from a user who currently holds `approve_bfc_courses` (see [Publication gates](#publication-gates)). Learner capabilities are not part of this phase. Phase 2 owns learner access.

To make another user an approver, grant `approve_bfc_courses` to that user or role. If you remove the capability from a past approver, their approvals no longer satisfy the publication gate. Courses they approved that are already published stay published. Any later save that keeps such a course published fails (REST) or is held as a draft (classic), and an unpublished course cannot be published, until a current approver re-approves it.

## Fields

| Field | Type | Limit | Required to publish | Assessed |
| --- | --- | --- | --- | --- |
| `bfc_code` | text | 7 | yes | yes |
| `course_revision` | text | 32 | yes | yes |
| `subject_category` | text | 64 | yes | yes |
| `course_outcome` | text | 32 | yes | yes |
| `pressprimer_quiz_id` | positive integer | — | yes | yes |
| `certificate_template_id` | positive integer | — | yes | yes |
| `matrix_requirement_slug` | text | 200 | no | no |
| `catalog_state` | text | 16 | no | no |
| `source_revision` | text | 191 | no | no |
| `approved_by` | integer (audit) | — | set by approval | — |
| `approved_at` | text (audit) | 32 | set by approval | — |
| `approval_notes` | paragraph (audit) | 2000 | no | — |

Limits are `Validator::LIMITS` and count characters, not bytes. Pods enforces them in the field UI, and `Validator` enforces them on every write path.

Value rules:

- `bfc_code`: `BFC-100` through `BFC-999`, three digits, no revision suffix.
- `subject_category`: must match the first digit of the code (`Validator::SUBJECTS`): 1 `program_administration_membership`, 2 `safety_field_awareness`, 3 `communications`, 4 `field_operations_documentation`, 5 `rehabilitation_logistics`, 6 `incident_management_coordination`, 7 `leadership_program_operations`, 8 `community_engagement_public_education`, 9 `reserved`.
- `course_outcome`: only `independent_study`.
- `catalog_state`: `draft`, `published` or `archived`. WordPress post status controls publication. This field controls catalog visibility.
- `matrix_requirement_slug`: a stable slug of an existing membership requirement. It is stored as text and not checked against the matrix, so a missing matrix record never breaks a course.

**Assessed fields** are `Validator::PUBLISHED_GATES`: code, revision, subject, outcome, quiz and certificate template. Changing any of them invalidates approval. All other fields, plus the title, body, excerpt and image, are editorial and keep the current approval.

**Audit fields** (`approved_by`, `approved_at`, `approval_notes`) are hidden in the Pods UI and blocked for REST, Pods, `meta_input` and direct metadata writes. Only the approval workflow writes them.

### Write paths covered

The same validation runs for Pods saves (`pods_api_field_validation`), REST (`rest_pre_insert_bfc_course`), classic and programmatic saves (`wp_insert_post_empty_content`, `wp_insert_post_data`), direct metadata APIs (`add/update/delete_post_metadata`) and metadata-by-ID APIs. An invalid Pods or REST batch is rejected as a whole. Metadata-by-ID updates of owned fields are always refused. Use the normal single-field APIs. `bfc_code` can never be deleted, and bulk deletion of owned keys is refused.

## Lifecycle

### 1. Save a draft first

A new course must be saved as a draft before approval or publication. A creation request that asks for `publish` or `future` is stored as a draft and reports *Save this course as a draft before requesting approval and publication.* Approval is tied to a stable saved course ID. Programmatic importers must create a draft, have it approved, then publish.

Drafts may be incomplete. Every value that is present must still be valid.

### 2. Approve

A Training Administrator approves the **saved** course with **Approve saved course** in the *Course approval and history* meta box. Approval:

1. Requires `approve_bfc_courses` and `edit_post` on the course, and a saved course that is not an auto-draft or in the trash.
2. Runs the full publication candidate check: complete valid metadata, identity availability, the PressPrimer mapping and a unique quiz assignment. Approval itself is not required for this check.
3. Rejects the request with `burbank_course_revision_required` when this course has an earlier snapshot with the same `course_revision` but any different assessed value.
4. Does nothing (and succeeds) when the current configuration already matches the latest snapshot and its approver still holds `approve_bfc_courses`.
5. Otherwise appends an immutable snapshot and writes `approved_by`/`approved_at` in one database transaction. If any step fails, the transaction is rolled back and the earlier approval stays in place.

Save edits before approving. The meta box acts on the stored course, not on unsaved editor state.

### 3. Publish

See [Publication gates](#publication-gates). First publication permanently binds the BFC code to the course.

### 4. Invalidation

Whenever an assessed field is changed or deleted and no longer matches the latest snapshot, `Editorial::metadata_changed()`:

- deletes `approved_by` and `approved_at`, and leaves every snapshot in place;
- moves a `publish` or `future` course back to `draft` with `wp_update_post()`.

Title, content, excerpt, image, `matrix_requirement_slug`, `source_revision` and `catalog_state` edits keep approval. Treat these as editorial corrections only under the administrator's policy. Anything that changes what is assessed needs a material revision.

What each surface does with an assessed change to a **published** course:

| Surface | Result |
| --- | --- |
| REST update that keeps the course published | Rejected with `400` and *Obtain course approval before publishing.* Nothing is stored. Save it as a draft instead. |
| Classic or programmatic save with publish status | Saved, held as draft, and the actionable notice is shown after redirect. |
| Pods save, or direct `update_post_meta` with a valid value | Stored, approval removed, and the course returned to draft. |
| Any write whose new candidate fails validation or the PressPrimer mapping | Rejected. The stored value and approval are unchanged. |

### 5. Material revision

Use **Start material revision** with a new revision label to change assessed content. `start_revision()`:

- requires `edit_post` (editors can start revisions, but only approvers can approve them);
- trims surrounding whitespace, then rejects a label that is empty, longer than 32 characters, altered by `sanitize_text_field` (beyond that trim), equal to the current revision, or used by **any** earlier approval snapshot of this course;
- removes current approval, returns a live course to draft, and then stores the new `course_revision`.

Then edit the assessed fields, save, have a Training Administrator approve, and publish again. Every earlier snapshot is kept.

### Revision labels identify one configuration

Within a course, a revision label stands for exactly one assessed configuration (code, revision, subject, outcome, quiz and template). A historical label cannot be reused for a different configuration. `start_revision()` refuses labels that already appear in history. `approve()` refuses a configuration that differs from a historical snapshot with the same label. The one case that works is returning to an earlier label with *exactly* its earlier assessed configuration; approval then records another snapshot for it. To correct a configuration, start a new revision label.

### Approval snapshots

Snapshots are stored as repeated `_bfc_approval_snapshot` post meta rows, in append order. Each row records `title`, `bfc_code`, `course_revision`, `subject_category`, `course_outcome`, `pressprimer_quiz_id`, `certificate_template_id`, `approved_by` and `approved_at` (UTC). They describe the course configuration that was approved, not learner completions, so later title or mapping changes do not alter which configuration a past credential belongs to.

Snapshot rows cannot be updated, deleted or renamed through any metadata API, including bulk and by-ID operations. Only `approve()` can append them. Read them with `( new Editorial() )->snapshots( $course_id )`. The meta box lists them under *Approval history*.

## Permanent BFC reservations

`{prefix}bfc_course_registry` (InnoDB, unique `code`, primary key `course_id`) is the authoritative identity store. Post meta is never treated as the source of truth.

- **Reserving.** Setting `bfc_code` reserves the code in a transaction under a row lock, so the database decides between concurrent requests. A code that another course holds, whether that course is a draft, published, archived, trashed or deleted, is rejected: *This BFC code is already reserved…*
- **Draft moves.** While a course has never been published, changing its code moves its reservation and releases the old code.
- **Sealing.** First publication marks the reservation `published = 1` in the same transaction that makes the post visible as published. From then on the code is immutable. There is no unpublish operation, and moving the course back to draft does not release the code.
- **Tombstones.** Deleting a course never removes its registry row, including a draft that was never published. The code stays reserved permanently. Do not delete a course to free a code. Choose another code instead.
- **Migration.** Installing the registry backfills existing `bfc_code` values and seals already-published courses.

## Archive and retirement

Archiving is the supported way to retire a course. **Archive** in the meta box, or setting `catalog_state` to `archived`, keeps the post status, BFC reservation, approval evidence and snapshots, and it works even when a PressPrimer dependency is unhealthy. Archived courses still count for quiz-assignment and code uniqueness. Learner discovery that honors `catalog_state` arrives in Phase 3. Until then, archiving only records state.

`catalog_state` is not assessed, so an editor can un-archive a course by setting it back to `published` or `draft` through the Pods field or REST. Doing so does not require re-approval.

### Deletion

- A course with any approval snapshot **cannot be permanently deleted**. `pre_delete_post` refuses it, and `wp_delete_post()` returns `false`.
- Such a course can still be moved to the trash. Permanent deletion fails with WordPress’s generic *Error in deleting the item.* screen and stops an **Empty Trash** batch at that course (later trash items in the same request are not purged). The daily `wp_scheduled_delete` cron retries the blocked delete and fails harmlessly each time. Restore the course from the trash and archive it instead.
- A course with no snapshots deletes completely, including its post meta, and does not trigger approval invalidation. Its BFC reservation stays as a tombstone.

## Publication gates

`Publication::validate()` requires all of the following:

1. Complete, valid metadata and an available or already-bound BFC code.
2. A saved `bfc_course` post (no publication on creation).
3. The supported PressPrimer releases, a published quiz, a published certificate template, and exactly one active matching quiz trigger. See [pressprimer-compatibility.md](pressprimer-compatibility.md).
4. No other non-auto-draft course (including drafts, trash and archived courses) assigned the same quiz.
5. Stored `approved_by`/`approved_at` written by a user who currently holds `approve_bfc_courses`, matching the latest snapshot's assessed configuration.

How each surface reports a failure:

- **REST / block editor:** the request fails with HTTP `400` and the specific error codes and messages. Nothing is stored.
- **Classic or programmatic `wp_insert_post` / `wp_update_post`:** the post is saved as a draft. After redirect the editor shows *Draft saved* (not *Post published*) and an error notice listing every failure.
- **`wp_publish_post`, scheduled publication and untrash:** WordPress writes `publish` before any hook can intervene. Burbank LMS reverts an invalid course to draft immediately, at the earliest `transition_post_status` priority. A concurrent reader could briefly see `publish`, and transition listeners registered after Burbank LMS still receive the rejected status.
- **Scheduled (`future`):** checked both when scheduled and again when the scheduled publish runs.

Assessed-field edits to an already published course are checked against the candidate configuration before they are stored. Archiving and other non-assessed edits stay possible when dependencies fail.

## Admin controls

The *Course approval and history* side meta box (block editor compatible) shows:

- the current approval state;
- **Approve saved course** (approvers only);
- **New revision** text field and **Start material revision**;
- **Archive**;
- the approval history.

Actions post to `admin-post.php?action=bfc_course_editorial` through two separate forms printed in the admin footer, so they never nest inside the post form. Pressing Enter in the revision field starts a revision and cannot submit an approval. Each action carries its own nonce, `bfc_course_{approve|revision|archive}_{ID}`. An expired or missing nonce, or a missing capability, ends with HTTP 403. Other results redirect back to the editor with a one-time success or error notice. The services check permissions again regardless of which form submitted them.

The course list adds **BFC code**, **Revision**, **Catalog state** (an em dash when empty) and **Approval** columns. **Approval** shows *Approved* only when the stored evidence matches the latest snapshot and the approver still holds `approve_bfc_courses`.

## Maintainer notes

- **Invalidation from metadata hooks.** `Editorial::invalidate()` can call `wp_update_post()` from inside `added/updated/deleted_post_meta`, which can happen in the middle of another save (for example a Pods batch). Current tests cover a live published Pods save and direct metadata writes, and no bug is known. This nesting is a known coupling, so test any new save path or hook priority against a published course.
- **Nested transactions.** Registry reservations and approval persistence each open a MySQL transaction. MySQL does not nest transactions: `START TRANSACTION` inside an open transaction commits the outer one. Do not call these services from code that already holds its own transaction.
- **Validation in one place.** Add new course fields to `Schema::FIELDS`, `Validator::LIMITS` (if text) and, if they are assessed, `Validator::PUBLISHED_GATES`. For UI-only Pods field changes, raise the editorial UI version that `Schema::register()` compares against `burbank_lms_course_editorial_ui_version`. Leave the data schema version unchanged.

## Tests

`tests/php/CourseSchemaTest.php`, `CourseIdentityTest.php`, `CoursePublicationTest.php` and `CourseEditorialTest.php` run against real Pods 3.3.9.2 and the pinned PressPrimer releases in `.wp-env.test.json`. Run them with `npm run test:php`.
