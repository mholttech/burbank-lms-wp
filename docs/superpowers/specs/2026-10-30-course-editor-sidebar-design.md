# Course editor sidebar (Gutenberg)

## Intent and accepted decisions

Course authors hit *Complete the required course field: bfc_code* (and related gates) when publishing from Gutenberg even after filling Pods “More Fields.” Classic Pods meta boxes under the editor are not part of the block editor REST save; Publish validates **stored** meta first, fails, and the meta-box POST never runs. Approval, Invites, and Enrollments already appear as PHP meta boxes but feel inconsistent with each other and with Pods fields in the main column.

**Accepted decisions**

- Replace Pods authoring UI with a React document experience; keep Pods for CPT / schema / meta storage.
- One dedicated Course `PluginSidebar` containing four panels: Details, Approval, Invites, Enrollments (not four separate sidebars; not document-setting panels only).
- Details owns all editable course meta (publish gates plus `catalog_state`, `matrix_requirement_slug`, `source_revision`). Audit fields stay server-owned.
- Migrate Approval, Invites, and Enrollments into that same sidebar for one consistent injected UI.
- New admin REST under `burbank-lms/v1` wrapping existing services for editorial / invite / enrollment panel actions; in-panel feedback, no redirect reload.
- Block editor only; remove PHP course meta boxes; no classic-editor fallback panels.
- Auto-open the Course sidebar on `bfc_course` edit; user may close it.
- Enrollments stay a compact scrollable table in the sidebar (no modal in this design).
- Prefer localizing pick lists and enums into the editor script over a separate picks REST.

## Approaches

**Selected:** One Course `PluginSidebar` + shared admin REST. Details uses `useEntityProp` → registered post `meta` on the same Gutenberg save/publish. Action panels call new REST routes that delegate to `Editorial`, `Invites`, and `Enrollment\Service`. Hide Pods field UI; unregister course PHP meta boxes.

**Considered:** All four panels as `PluginDocumentSettingPanel`s only — rejected because Enrollments is cramped and the Course “home” is weaker. Split layout (sidebar + wide Enrollments under the editor) — deferred; revisit if the compact table is painful. Thin React shell over `admin-post` redirects — rejected; reloads and fights a native sidebar. Modal for Enrollments — deferred. Keeping PHP boxes as classic fallback — rejected (block editor only).

## Behavior

### Course sidebar

On `bfc_course` block editor screens, enqueue a Course editor script that `registerPlugin`s `burbank-lms-course` with `PluginSidebar` + `PluginSidebarMoreMenuItem` labeled Course. Four `PanelBody` sections share spacing, controls, notices, busy/disabled states, and “Save a draft first” when the post is an auto-draft or has no stable ID.

**Details.** Bind editable keys via `useEntityProp( 'postType', 'bfc_course', 'meta' )`: `bfc_code`, `course_revision`, `subject_category` (select from `Validator::SUBJECTS`), `course_outcome` (select; only `independent_study`), `pressprimer_quiz_id` and `certificate_template_id` (selects from localized published picks), `matrix_requirement_slug` (select when `bc_requirement` picks exist, else text), `catalog_state` (`draft` / `published` / `archived`), `source_revision` (text). Help text mirrors Schema descriptions. Server validation and publication gates remain authoritative; client soft-hints are optional and out of scope for v1.

**Approval.** Parity with the current editorial meta box: approval status, Approve saved course (`approve_bfc_courses`), new revision label + Start material revision, Archive, collapsible approval history. Actions apply to the **saved** course. Hydrate from REST; refresh after each action.

**Invites.** Parity with the course invites box: create (label, kind, max uses, expires UTC, bound email), list, revoke. Requires `enroll_bfc_courses`. Show the plaintext token once on create, matching today’s create behavior.

**Enrollments.** Parity with the enrollments box: admin enroll by user ID; list user, status, source, enrolled / passed / completed. Compact and scrollable in the narrow sidebar. Requires `enroll_bfc_courses`. Reuse existing `POST /enrollments/admin` for enroll; add a course-scoped list route if none exists.

### Admin REST

Namespace remains `burbank-lms/v1`. Cookie + REST nonce auth. Permission callbacks mirror current caps and course edit checks. Map service `WP_Error` codes to REST responses the same way as the learner controller.

| Method / route | Behavior |
| --- | --- |
| `POST /courses/{id}/approve` | `Editorial::approve` |
| `POST /courses/{id}/revision` | `Editorial::start_revision` (revision label in body) |
| `POST /courses/{id}/archive` | `Editorial::archive` |
| `GET /courses/{id}/editorial` | Approval state + snapshots for panel hydrate |
| `GET /courses/{id}/invites` | List course invites |
| `POST /courses/{id}/invites` | Create course invite |
| `DELETE /invites/{id}` | Revoke invite |
| `GET /courses/{id}/enrollments` | List enrollments for the course |
| `POST /enrollments/admin` | Existing admin enroll (unchanged contract) |

Localize on enqueue: quiz/template/requirement pick maps, subject and outcome enums, capability flags useful to the UI. Do not add a picks REST unless localization becomes too large later.

`admin-post` handlers for course editorial / invites / enrollment may remain for non-editor callers during a transition, but the course editor must not use them. Prefer deleting course-only `admin-post` + meta box wiring once REST coverage and tests land.

### Teardown

- Stop registering meta boxes `bfc-course-editorial`, `bfc-course-invites`, `bfc-course-enrollments`.
- Remove editor-only action forms and notices that existed solely for those boxes, once REST replaces them.
- Hide Pods “More Fields” / course field UI in the block editor via the supported Pods path (field hidden / metabox removal) without deleting field definitions or meta storage.
- Keep `register_post_meta` for `Schema::FIELDS` with `show_in_rest` so Details can read/write meta.

Publication gates, BFC identity lock, approval invalidation, and PressPrimer mapping rules do not change.

## Implementation boundaries

- Business logic stays in existing `src/Courses`, `src/Access`, `src/Enrollment` services. New code is editor enqueue/localize, React sidebar UI, and thin REST adapters.
- Register hooks through `Loader` / `Burbank_LMS` as today. Do not register hooks in constructors.
- Assets: source under `resources/admin/` with a dedicated webpack entry `burbank-lms-course-editor` (enqueue only on `bfc_course` block editor screens so React editor deps are not loaded site-wide). Keep `burbank-lms-admin` for non-editor admin scripts. Do not hand-edit `build/`.
- Do not support classic editor panels in this work.
- Do not remove the Pods dependency or change learner-facing REST, blocks, or PressPrimer adapters except as needed to enqueue/hide UI.
- Site invite admin screens and user profile Training Access UI stay PHP for now.
- Enrollments modal / wide under-editor panel is out of scope.

## Validation

- PHPUnit: admin REST happy paths, capability denials, and `WP_Error` mapping for approve / revision / archive / invites / course enrollments list; keep existing service and publication tests green.
- Update or remove tests that assert removed meta box HTML or course-screen `admin_post` flows.
- Build admin assets; ESLint on new JS.
- Manual: set Details meta → Save draft → Approve → Publish without empty-`bfc_code` when meta is stored; confirm Pods More Fields absent; Approval / Invites / Enrollments work in the Course sidebar.
- Document in `docs/course-authoring.md` and `docs/learner-access.md`; add a high-level `AGENTS.md` pointer.

## Subsequent work

After this design is approved, produce an implementation plan, then implement with tests. Optional follow-ups: Enrollments modal or wide panel, remove leftover `admin-post` entirely, classic editor support (explicitly not planned).
