# Course Editor Sidebar Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a Gutenberg Course `PluginSidebar` so course meta saves with publish, and Approval / Invites / Enrollments share one React + admin-REST experience.

**Architecture:** Thin `REST\Admin_Controller` wraps existing `Editorial`, `Invites`, and enrollment repository/services. `Courses\Editor` enqueues a dedicated webpack entry on `bfc_course` only, localizes picks/enums/caps, and hides Pods field UI. React registers one Course sidebar with four `PanelBody` sections; Details uses `useEntityProp` for post `meta`. PHP course meta boxes are removed.

**Tech Stack:** WordPress block editor (`@wordpress/scripts`, `@wordpress/plugins`, `@wordpress/edit-post`, `@wordpress/components`, `@wordpress/data`, `@wordpress/api-fetch`, `@wordpress/i18n`), PHP 8.2+, existing Pods meta storage, PHPUnit via `npm run test:php`.

**Spec:** `docs/superpowers/specs/2026-10-30-course-editor-sidebar-design.md`

## Global Constraints

- Namespace `Burbank_LMS`, text domain `burbank-lms`; hooks only via `Loader` / `Burbank_LMS.php`; no business logic in constructors; no hand-edits to `build/`.
- Do not change publication gates, BFC identity lock, approval invalidation, or PressPrimer contracts.
- Keep Pods as CPT/schema/storage; hide field UI only.
- Block editor only; no classic course panels.
- REST namespace `burbank-lms/v1`; map service `WP_Error` like `REST\Controller::to_rest_error`.
- Dedicated webpack entry `burbank-lms-course-editor`; enqueue only on `bfc_course` block editor screens.
- After each PHP task: `npm run test:php`, `composer run phpstan`, `composer run phpcs`. After JS tasks: `npm run build`, `npm run lint:js`.

## File map

| Path | Responsibility |
| --- | --- |
| `src/REST/Admin_Controller.php` | Admin course editorial / invites / enrollments-list routes |
| `src/Courses/Editor.php` | Enqueue course-editor assets, localize data, hide Pods metabox |
| `src/Burbank_LMS.php` | Wire `Admin_Controller`, `Editor`; remove course meta-box hooks |
| `src/Courses/Admin.php` | Drop `meta_box` / `action_form` registration (keep list columns + `dispatch` for tests/REST) |
| `src/Access/Admin.php` | Drop course invites meta box only; keep site invites + profile |
| `src/Enrollment/Admin.php` | Drop course enrollments meta box; keep `dispatch`/admin-post if still used elsewhere or remove course-only path |
| `webpack.config.js` | Add `burbank-lms-course-editor` entry |
| `resources/admin/js/course-editor/` | React Course sidebar plugin + panels |
| `resources/admin/scss/course-editor.scss` | Narrow-sidebar layout helpers |
| `tests/php/CourseEditorRestTest.php` | Admin REST coverage |
| `tests/php/CourseEditorialTest.php` | Adjust meta-box HTML assertions |
| `tests/php/AccessAdminTest.php` / enrollment admin tests | Drop course meta-box assertions |
| `docs/course-authoring.md`, `docs/learner-access.md`, `AGENTS.md` | Ops docs |

---

### Task 1: Admin REST — editorial

**Files:**
- Create: `src/REST/Admin_Controller.php`, `tests/php/CourseEditorRestTest.php`
- Modify: `src/Burbank_LMS.php` (register `rest_api_init` → `Admin_Controller::register_routes`)

**Interfaces:**
- Consumes: `Editorial::approve(int): WP_Error|bool`, `start_revision(int, string): WP_Error|bool`, `archive(int): WP_Error|bool`, `snapshots(int): array`, `matches_approval(int, array): bool`; `Validator::fields(int): array`
- Produces: routes under `burbank-lms/v1`:
  - `GET /courses/(?P<id>\d+)/editorial`
  - `POST /courses/(?P<id>\d+)/approve`
  - `POST /courses/(?P<id>\d+)/revision` body `{ "revision": string }`
  - `POST /courses/(?P<id>\d+)/archive`
- `GET` response shape: `{ "approved": bool, "approved_by": int|null, "approved_at": string|null, "can_approve": bool, "snapshots": array<snapshot> }`
- Permission: logged-in + `edit_post` on course for GET/revision/archive; approve also requires `approve_bfc_courses`. Reject non-`bfc_course` with 404.

- [ ] **Step 1: Write failing tests** in `tests/php/CourseEditorRestTest.php` mirroring `LearnerRestTest` setup (`rest_api_init`, caps). Cover: unauthenticated 401; editor without approve gets 403 on approve; approver can approve a valid saved course (reuse publication fixture helpers from `CoursePublicationTest` / `CourseEditorialTest`); revision + archive update meta; GET returns snapshots after approve.

```php
public function test_approve_requires_approver_cap(): void {
	$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
	wp_set_current_user( $editor );
	$id = /* draft course with full valid fields, not yet approved */;
	$request = new WP_REST_Request( 'POST', '/burbank-lms/v1/courses/' . $id . '/approve' );
	$response = rest_get_server()->dispatch( $request );
	$this->assertSame( 403, $response->get_status() );
}
```

- [ ] **Step 2: Run** `npm run test:php -- --filter CourseEditorRestTest`  
  Expected: FAIL (routes missing / class missing).

- [ ] **Step 3: Implement** `Admin_Controller` with shared `to_rest_error` / `unauthorized` / `require_course( int $id ): WP_Post|WP_Error` helpers (copy pattern from `REST\Controller`, do not make learner controller depend on admin). Wire in `Burbank_LMS.php`:

```php
$admin_rest = new REST\Admin_Controller();
$this->loader->add_action( 'rest_api_init', $admin_rest, 'register_routes' );
```

On success, approve/revision/archive return `GET`-shaped editorial payload (or `{ "ok": true }` plus client re-fetches — prefer returning editorial payload to avoid an extra round-trip).

- [ ] **Step 4: Re-run** `CourseEditorRestTest`, `composer run phpstan`, `composer run phpcs`.  
  Expected: PASS.

- [ ] **Step 5: Commit** `feat: add course editorial admin REST routes`

---

### Task 2: Admin REST — course invites + enrollments list

**Files:**
- Modify: `src/REST/Admin_Controller.php`, `tests/php/CourseEditorRestTest.php`

**Interfaces:**
- Consumes: `Invites::create('course', array): array{id,plaintext}|WP_Error`, `list('course', int): array`, `revoke(int): bool|WP_Error`; `Enrollment\Repository::list_for_course(int): array`; existing `POST /enrollments/admin`
- Produces:
  - `GET /courses/(?P<id>\d+)/invites`
  - `POST /courses/(?P<id>\d+)/invites` body: `label`, `kind`, `max_uses?`, `expires_at?`, `bound_email?` → `{ "id": int, "plaintext": string }`
  - `DELETE /invites/(?P<invite_id>\d+)`
  - `GET /courses/(?P<id>\d+)/enrollments` → `{ "enrollments": [ { "user_id", "display_name", "status", "source", "enrolled_at", "quiz_passed_at", "completed_at" } ] }`
- Permission: `enroll_bfc_courses` + course exists as `bfc_course` (and for create/list, prefer also `edit_post` on that course). Do **not** require REST nonce beyond cookie auth WordPress already uses.

- [ ] **Step 1: Write failing tests** for create (returns plaintext once), list, revoke, enrollments list empty then after `Enrollment\Service::admin_enroll`, and 403 without `enroll_bfc_courses`.

- [ ] **Step 2: Run filter** — Expected: FAIL on new routes.

- [ ] **Step 3: Implement routes** calling services directly (do not require `admin-post` nonces). Serialize invite rows without `code_hash`.

- [ ] **Step 4: Re-run tests + phpstan + phpcs.** Expected: PASS.

- [ ] **Step 5: Commit** `feat: add course invite and enrollment list admin REST`

---

### Task 3: Editor enqueue, localize, hide Pods UI

**Files:**
- Create: `src/Courses/Editor.php`
- Modify: `src/Burbank_LMS.php`, `webpack.config.js`
- Create stub: `resources/admin/js/course-editor/index.js` (empty registerPlugin later), `resources/admin/scss/course-editor.scss`

**Interfaces:**
- Produces: `Editor::enqueue( string $hook ): void` on `enqueue_block_editor_assets` (preferred) or `admin_enqueue_scripts` when `$hook` is `post.php`/`post-new.php` and post type is `bfc_course`.
- Localized object `burbankLmsCourseEditor` (via `wp_localize_script` / `wp_add_inline_script` with `wp_json_encode`):

```js
{
  subjects: { /* slug: label from Validator::SUBJECTS */ },
  outcomes: { independent_study: '…' },
  catalogStates: { draft: '…', published: '…', archived: '…' },
  quizzes: { /* id: title from Picks::quiz_data() */ },
  templates: { /* id: title from Picks::template_data() */ },
  requirements: { /* slug: title or {} */ },
  hasRequirements: bool,
  caps: { approve: bool, enroll: bool },
  restNamespace: 'burbank-lms/v1'
}
```

- Hide Pods fields box: on `add_meta_boxes_bfc_course` priority 99, `remove_meta_box` for Pods course meta box id(s) used by installed Pods (assert in a PHPUnit that after `Editor::hide_pods_fields()` / hooked action, the box is absent — or document the exact box id discovered from Pods 3.3.x, commonly `pods-meta-bfc_course` / `pods-meta-more-fields`). Do not delete Pods field definitions.

- [ ] **Step 1: Add webpack entry**

```js
'burbank-lms-course-editor': [
  path.resolve( __dirname, 'resources/admin/js/course-editor/index.js' ),
  path.resolve( __dirname, 'resources/admin/scss/course-editor.scss' ),
],
```

Stub `index.js` with `console` removed — empty file exporting nothing yet is fine; build must succeed.

- [ ] **Step 2: Implement `Editor`** enqueue using existing `Burbank_LMS::enqueue_entrypoint` pattern **or** duplicate the asset.php load locally so the script only loads on course editor (do not leave `burbank-lms-admin` loading React editor deps globally). Prefer moving course-editor enqueue fully into `Editor` with the same asset.php logic copied/adapted as a private method, or add an optional `$entry` enqueue helper on `Burbank_LMS` used by `Editor`.

- [ ] **Step 3: Wire** `enqueue_block_editor_assets` → `Editor::enqueue` and meta-box removal hook via Loader.

- [ ] **Step 4: Run** `npm run build` — Expected: `build/burbank-lms-course-editor.js` (+ `.asset.php`) exists. Spot-check PHPStan/PHPCS on `Editor.php`.

- [ ] **Step 5: Commit** `feat: enqueue course editor assets and hide Pods fields UI`

---

### Task 4: React Course sidebar shell + Details panel

**Files:**
- Create: `resources/admin/js/course-editor/index.js`, `sidebar.js`, `panels/details.js`, `components/notice.js` (optional), `resources/admin/scss/course-editor.scss`
- Depends on: Task 3 localization keys

**Interfaces:**
- Plugin name: `burbank-lms-course`
- Sidebar name: `course` (sidebar id becomes `burbank-lms-course/course`)
- Details meta keys written via:

```js
const [ meta, setMeta ] = useEntityProp( 'postType', 'bfc_course', 'meta' );
const update = ( key, value ) => setMeta( { ...meta, [ key ]: value } );
```

Keys: `bfc_code`, `course_revision`, `subject_category`, `course_outcome`, `pressprimer_quiz_id`, `certificate_template_id`, `matrix_requirement_slug`, `catalog_state`, `source_revision`. Coerce quiz/template select values to string/number consistent with registered meta type (`integer` for number fields in `Schema`).

- [ ] **Step 1: Implement sidebar chrome** — `PluginSidebarMoreMenuItem` + `PluginSidebar` titled “Course”, four `PanelBody` placeholders; Details fully implemented with `TextControl` / `SelectControl` and Schema help text. Read options from `window.burbankLmsCourseEditor` (or whatever localize handle name `Editor` sets — keep handle and JS global in sync).

- [ ] **Step 2: Auto-open** once per load:

```js
import { dispatch, select } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
// after registerPlugin / domReady:
if ( select( editPostStore ).getActiveGeneralSidebarName() !== 'burbank-lms-course/course' ) {
  dispatch( editPostStore ).openGeneralSidebar( 'burbank-lms-course/course' );
}
```

- [ ] **Step 3: Run** `npm run build` && `npm run lint:js`. Fix lint issues.

- [ ] **Step 4: Manual check** (wp-env): open course editor — Course sidebar opens; change `bfc_code`; Save draft; reload; value persists; Pods “More Fields” absent. Publish still requires other gates (expected).

- [ ] **Step 5: Commit** `feat: add Course sidebar Details panel bound to post meta`

---

### Task 5: React Approval panel

**Files:**
- Create: `resources/admin/js/course-editor/panels/approval.js`
- Modify: `sidebar.js` to mount Approval

**Interfaces:**
- Consumes: `GET/POST …/editorial|approve|revision|archive` from Task 1; `caps.approve` from localize; post ID from `select('core/editor').getCurrentPostId()`; disable when auto-draft (`getEditedPostAttribute('status') === 'auto-draft'` or id falsy).
- Uses `@wordpress/api-fetch` with path `/burbank-lms/v1/courses/${id}/…`.

- [ ] **Step 1: Implement panel** — status text, Approve button (hidden/disabled without cap), revision `TextControl` + Start material revision, Archive, `snapshots` list in `<details>`. Show `Notice` on error/success. After action, replace local state from response payload.

- [ ] **Step 2: Build + lint.** Manual: save complete valid course → approve as admin → publish succeeds when PressPrimer mapping valid (same as today’s gates).

- [ ] **Step 3: Commit** `feat: add Course sidebar Approval panel`

---

### Task 6: React Invites + Enrollments panels

**Files:**
- Create: `resources/admin/js/course-editor/panels/invites.js`, `panels/enrollments.js`
- Modify: `sidebar.js`, `course-editor.scss` (table scroll)

**Interfaces:**
- Consumes Task 2 routes + existing `POST /burbank-lms/v1/enrollments/admin` with `{ user_id, course_id }`.
- Gate UI on `caps.enroll`; disable on auto-draft.

- [ ] **Step 1: Invites panel** — create form fields matching current admin box; on create show plaintext once in a `Notice`; list + revoke buttons; refresh list after mutations.

- [ ] **Step 2: Enrollments panel** — user ID number + Enroll; compact table of list endpoint rows; horizontal scroll OK.

- [ ] **Step 3: Build + lint.** Manual smoke for create invite + admin enroll.

- [ ] **Step 4: Commit** `feat: add Course sidebar Invites and Enrollments panels`

---

### Task 7: Remove PHP course meta boxes + fix tests

**Files:**
- Modify: `src/Burbank_LMS.php` (remove `add_meta_boxes_bfc_course` for editorial/invites/enrollments; remove `admin_footer` action_form if unused)
- Modify: `src/Courses/Admin.php` — keep `dispatch`, `columns`, `column`, `notice` as needed; remove or no-op `meta_box` / `controls` / `action_form` if nothing calls them
- Modify: `src/Access/Admin.php` — remove `register_meta_box` / course box render usage from Loader only; keep site invite UI
- Modify: `src/Enrollment/Admin.php` — remove meta box registration from Loader
- Modify tests that scrape meta box HTML (`CourseEditorialTest` form fields, Access/Enrollment admin course box tests)

- [ ] **Step 1: Update tests first** that assert meta box markup — point them at `Admin::dispatch` / REST instead, or delete obsolete UI assertions.

- [ ] **Step 2: Run** `npm run test:php` — Expected: FAIL on removed UI assertions or still-registered boxes.

- [ ] **Step 3: Remove Loader hooks and dead render paths** for the three course meta boxes. Keep `admin_post_bfc_invite_*` for **site** invites menu; keep profile handlers. Course invite create/revoke from PHP course UI is gone (REST only). If `admin_post_bfc_enrollment_admin_enroll` is only used by the removed box, remove that hook too (REST admin enroll remains).

- [ ] **Step 4: Full** `npm run test:php`, phpstan, phpcs. Expected: PASS.

- [ ] **Step 5: Commit** `feat: remove course PHP meta boxes in favor of Course sidebar`

---

### Task 8: Docs + final verification

**Files:**
- Modify: `docs/course-authoring.md`, `docs/learner-access.md`, `AGENTS.md`

- [ ] Document Course sidebar panels, meta-via-REST save/publish, admin REST table, Pods UI hidden, block-editor-only authoring.
- [ ] Update AGENTS.md Courses / Access / Enrollment bullets to mention the editor sidebar + `REST\Admin_Controller`.
- [ ] Run final gate: `npm run test:php`, `composer run phpstan`, `composer run phpcs`, `npm run build`, `npm run lint:js`.
- [ ] Commit `docs: document Course editor sidebar and admin REST`

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| Details `useEntityProp` meta | 4 |
| All editable meta fields | 4 |
| Approval / Invites / Enrollments in one Course sidebar | 4–6 |
| Auto-open sidebar | 4 |
| Admin REST editorial | 1 |
| Admin REST invites + enrollments list | 2 |
| Localize picks/enums/caps | 3 |
| Hide Pods More Fields | 3 |
| Remove PHP course meta boxes | 7 |
| Block editor only | 7 |
| Dedicated `burbank-lms-course-editor` entry | 3 |
| Docs / AGENTS | 8 |
| No publication rule changes | (all tasks — do not touch Validator gates) |

## Out of scope (do not implement)

- Enrollments modal / under-editor wide panel  
- Classic editor panels  
- Removing Pods dependency  
- Site invite admin / user profile Training Access migration to React  
- Client-side publish preflight beyond optional future hints  
