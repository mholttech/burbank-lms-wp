# Learner course pages redesign + self-service legal name

## Intent

Live QA on `cert.responseforge.app/my-courses/` found the enrolled-course area reads as loose text, not a record: a title stranded at the left, a hollow status pill at the far right, and a primary-red "Browse course catalog" button dangling below. Iterative fixes (bordered card, no panel shadow, more padding, meta line + action) improved the row but not the overall page layout, which the site owner rejected. This spec replaces the My Courses page layout wholesale, gives the course catalog (`/courses/`) the same treatment, and lets learners update their own legal/certificate name.

**Portal chrome is out of scope.** `Learner\Shell` (Training kicker, display-name H1, lede, Catalog / My Courses / Profile pill track) stays exactly as-is. The redesign covers everything the `burbank-lms/my-courses` and `burbank-lms/catalog` blocks render below the shell.

**Domain rules** in `docs/learner-access.md` stay authoritative except for the one deliberate reversal in this spec: legal-name self-service (below). No schema, enrollment, or certificate-issuance changes: the page redesigns consume data that already exists; the legal-name change adds one REST route.

## Accepted decisions

| Topic | Decision |
| --- | --- |
| Layout | Option A structure on a tile grid: portal header → summary strip → "Enrolled courses" section → "Certificates" section. **No right sidebar** (explicitly rejected). |
| Course presentation | Tiles in a responsive grid (2 columns on desktop, 1 on phones), replacing full-width rows. Tile: status badge, title, date meta, divider, footer actions. |
| Summary strip | Four stat tiles: Enrolled, In progress, Completed, Certificates. Counts computed client-side (see Data). |
| Catalog CTA | Standalone red button under the list is removed. Catalog appears only as a quiet "Browse course catalog →" link in the Enrolled courses section header. |
| Certificates | PressPrimer's rendered list is re-gridded to the same tile columns and stretched to full page width (today it renders as a centered ~800px column that misaligns with the course list). PressPrimer's internal card contents (title, chip, dates, Download/Verify) are not restructured. |
| Status colors | Active/Passed: green tint badge; Completed: solid green badge; Withdrawn: muted outline badge + tile content at reduced opacity. Same `data-status` mechanism as the current card. |
| Actions per tile | Active/Passed: green "Continue course" + quiet "Withdraw" text button in the footer. Completed/Withdrawn: outline "View course". Withdraw success updates the tile in place (badge → Withdrawn, action → View course, tile muted). |
| Progress rail | Dropped with the sidebar. The stats strip carries the summary role; no progress bar in v1. |
| Catalog (`/courses/`) | Same treatment: compact filter toolbar (search + subject, behavior unchanged), then courses as tiles in the same responsive grid. Tile: subject badge, title, code · outcome meta, excerpt when present, divider, footer with state + action. Enrolled tiles gain a "Continue course" link (today they show only an "Enrolled" chip); invite-required and open-enrollment (red Enroll button) states carry over. Client-side filtering over the existing `data-bfc-*` attributes is unchanged. |
| Catalog H1 | The theme's "Course catalog" `core/query-title` H1 above the portal header is suppressed on the `bfc_course` archive via `Portal_Headings` (new `render_block_core/query-title` hook), so the portal H1 is the only H1. |
| Legal / certificate name | **Option A: freely editable by the learner, audited.** The Profile form's read-only field becomes an input; saving posts the new name to a new `POST burbank-lms/v1/profile/legal-name` route. Every change appends to the existing audit log (`from`, `to`, `by` = the learner, `at`). Usermeta guards stay: the name can still only change through `Profile`. Admin correction (`enroll_bfc_courses`) and registration initial-set are unchanged. Already-issued certificates keep the name they were issued with; the change affects future certificates only. `docs/learner-access.md` and the `Profile` docblocks/tests that assert the lock are updated to the new rule. |

## Approaches

**Selected: A-sections × tile grid**

Full-width sections with headed groups; courses and certificates as equal tiles. Scales from one enrollment to many without the page feeling empty or sparse, and keeps certificates aligned with courses.

**Rejected**

- A with full-width rows (previous direction) — one-course pages look empty; the row was the original complaint.
- B dashboard with right sidebar (progress / find-more-training / certificates rail) — rejected by the owner; also starves the course column and would squeeze PressPrimer's wide certificate markup.
- C pure grid without A's section/stats structure — lonelier for single-course learners and loses the summary.

## Architecture

```
src/Blocks/my-courses/render.php   Section scaffolding: stats strip (JS-populated),
                                   Enrolled courses header + list, Certificates
                                   section wrapper; secondary catalog link moves
                                   into the section header; old CTA <p> removed
src/Blocks/my-courses/view.js      Renders tiles (main + footer actions), computes
                                   stats + certificate count, withdraw in place
src/Blocks/my-courses/style.scss   Stats strip, section heads, tile grid, tile
                                   states, certificates re-grid + full-width stretch
src/Blocks/catalog/render.php      "Course catalog" section header + course count;
                                   tile markup (badge/meta/excerpt/footer) around
                                   the existing state logic; enrolled tiles link
                                   Continue course; filters markup unchanged
src/Blocks/catalog/style.scss      Filters become a compact toolbar card; list
                                   becomes the tile grid; tile states
src/Learner/Portal_Headings.php    Suppress core/query-title on bfc_course archive
src/Burbank_LMS.php                Register render_block_core/query-title filter
src/REST/Controller.php            POST /profile/legal-name (login + can_learn)
src/Access/Profile.php             Self-service legal-name change with audit
src/Blocks/profile/render.php      Legal-name input editable; hint text updated
src/Blocks/profile/view.js         Posts legal_name to the new route on save
tests/php/MyCoursesBlockTest.php   Render assertions follow new scaffolding
tests/php/CatalogBlockTest.php     Tile markup + Continue-link assertions
tests/php/LearnerPortalHeadingsTest.php  Query-title suppression on archive
tests/php/AccessLearnerTest.php    Lock assertions replaced: learner can change
                                   own name (audited); guards still block raw meta
tests/php/LearnerRestTest.php      Legal-name route: auth, gate, validation, audit
docs/learner-access.md             My Courses/catalog rows + legal-name rule updated
```

No changes to `src/Enrollment/`, `src/Progress/`, `src/Learner/Shell.php`, or `resources/frontend/scss/pressprimer.scss`. The catalog's enroll endpoint usage and `src/Blocks/catalog/view.js` filter/enroll logic are unchanged except for any selectors the tile markup rename touches (data attributes `data-bfc-*` stay stable).

### Catalog render

- Section header: "Course catalog" (`<h2>`, same pattern as My Courses sections) + "N courses available" count (singular/plural via `_n()`), above the existing filters.
- Filters keep their current fields, ids, and `data-bfc-catalog-*` hooks; only presentation changes (compact toolbar card).
- Each `bfc-catalog__item` becomes a tile: subject label as the top badge (rendered only when a subject exists), title, meta row (code · outcome), excerpt, footer divider with the state chip on the left and the action on the right. State logic is the existing render.php logic verbatim, plus one addition: the `$active` (enrolled) branch gains a "Continue course" link to the permalink next to the Enrolled chip.
- Empty catalog ("No courses are available yet.") and no-filter-match states are unchanged in copy.

### Legal-name self-service

- `Profile::set_legal_name()` gains a learner path: when the current user **is** the target user and the request comes through the new REST route, a non-empty sanitized name is accepted and audited. Implementation keeps the existing signature safe for current callers: add a dedicated method (e.g. `set_legal_name_self_service( int $user_id, string $name )`) that verifies `get_current_user_id() === $user_id`, writes via the same allow-flag mechanism, and always appends an audit entry when the name changes (including the initial set? No — audit only on change from a non-empty current value, same as admin corrections; the entry's `by` is the learner's own ID, which is how audit readers distinguish self-changes from admin ones).
- Route: `POST /wp-json/burbank-lms/v1/profile/legal-name`, body `{ "legal_name": "…" }`. Permission: logged in **and** `Learner::can_learn` (suspended/no-access users get 403, consistent with other learner routes' error mapping). Empty/whitespace name → 400 `burbank_profile_legal_name_required`. Response: `{ "legal_name": "<stored>" }` 200. Follows the existing Controller patterns for `WP_Error` → REST mapping.
- Direct usermeta writes/deletes stay blocked (`guard_legal_name_meta` / delete guards untouched), and the admin wp-admin correction flow is untouched.
- Profile block: legal-name input loses `readonly disabled`, gains `name="legal_name"`, hint becomes "This name appears on your certificates. Changes are recorded." The form's existing submit handler first saves via `wp/v2/users/me` as today, then posts the legal name when it differs from the server-rendered value (compare against a `data-bfc-legal-name` attribute on the input to avoid no-op audit entries). Failures surface in the existing status line ("Profile saved, but legal name failed: …" if the first save succeeded — implementation chooses one status message per outcome; partial success must not read as full success).
- Copy sweep: any learner-facing "Contact a training administrator to correct your legal name." text (profile hint; check register/profile/mail copy via search) is updated or removed. Admin-facing "Correct legal name" UI is unchanged.

### Server render (render.php)

Below the existing `Block_Title` handling, the block renders:

1. **Summary strip** — `<ul class="bfc-my-courses__stats">` with four items, each a value span (`data-bfc-stat="enrolled|in-progress|completed|certificates"`) and a label. Values start as `–` and are filled by JS; the certificates item is rendered only when the `ppcert_my_certificates` shortcode exists. The whole strip is `hidden` until JS populates it, so a REST failure never shows wrong zeros.
2. **Enrolled courses section** — section header as `<h2>` (portal owns the H1; when the block renders standalone and `Block_Title` also prints its "My courses" H2, sibling H2s are an acceptable outline) with the catalog archive link on the right when an archive URL exists. Existing loading / list / empty / status elements move inside this section. The empty state becomes a single wide tile: "You are not enrolled in any courses yet." + the catalog link as its action.
3. **Certificates section** — existing `bfc-my-courses__certificates` wrapper gains a section header ("Certificates" + valid-count label `data-bfc-cert-valid`, filled by JS, hidden when the shortcode output has no items). The shortcode output itself is unchanged.

Soft-gate behavior is unchanged: when `Soft_Gate::should_block`, none of this scaffolding renders.

### Client render (view.js)

From the existing single `GET /enrollments` response:

- **Tile** per enrollment, in response order:
  - `<li class="bfc-my-courses__item" data-status="…">`
  - badge (`data-status` on the badge as today), title link/span, meta line (`Completed <date> · Enrolled <date>` for completed; `Enrolled <date>` otherwise; omitted when no parseable date), divider footer with status-appropriate actions (existing Continue/View/Withdraw logic and in-place withdraw updates carry over).
- **Stats**: Enrolled = enrollments excluding `withdrawn`; In progress = `active` + `passed`; Completed = `completed`. Withdrawn enrollments still appear as tiles (muted) but do not count as Enrolled.
- **Certificates count**: after render, count `.ppcert-my-certificates__item` elements inside the certificates wrapper for the Certificates stat, and count items carrying PressPrimer's valid status chip for the "N valid" label. Rule: when the certificates wrapper contains a `.ppcert-my-certificates` root, the stat shows the counted number (a true 0 included — PressPrimer renders its own empty state then); when no such root exists, the stat and label stay hidden. Selectors come from the inspected PressPrimer Certificate 2.0.2 markup and are confirmed against live markup during implementation.

### Styles (style.scss)

- All tokens reuse the existing `--bfc-*` variables (control bg/border/text/muted, pill active green, panel radius). Light + dark (`html[data-bc-theme="dark"]`) come from those tokens; no new hard-coded colors.
- `.bfc-my-courses__list` becomes `grid-template-columns: repeat(auto-fill, minmax(min(20rem, 100%), 1fr))`, so one enrollment still gets a bounded-width tile rather than a page-wide row.
- Tile is a bordered card, `box-shadow: none` (panel shadow stays removed), padding `clamp(1.125rem, 2.5vw, 1.5rem)`.
- Certificates re-grid scoped under `.bfc-my-courses__certificates`: the PressPrimer list becomes the same auto-fill grid and its container's max-width/centering is overridden to full width. Overrides must outrank PressPrimer's own styles without `!important` where feasible; scoped under `.wp-site-blocks main .bfc-my-courses__certificates` following the existing specificity pattern in this file and `app.scss`.
- Theme plain-link paint (`.wp-site-blocks main a:not(.wp-block-button__link):not(.has-text-color)`, 0,3,2) must stay off tile titles and actions — the existing scoped overrides carry over and extend to any new link classes.
- Stats strip: 4 columns ≥600px, 2×2 below; tiles 1 column below ~480px. Footer actions wrap within the tile.

## Error and empty states

- My Courses REST failure: existing status line error ("Could not load courses."), stats strip stays hidden, section headers still render.
- No enrollments: list hidden; empty tile (message + catalog link) shows; stats show Enrolled 0 / In progress 0 / Completed 0 (strip visible — it is accurate), Certificates stat per the counting rule.
- Certificates shortcode absent: no certificates section, no Certificates stat.
- Legal name: empty submission → inline error from the route message; unchanged name → no request, no audit entry; route failure after a successful core profile save → status reads as partial ("Profile saved, but your legal name was not updated.") — never a bare "Profile saved."

## Testing

- `MyCoursesBlockTest`: logged-in render contains stats scaffolding (`data-bfc-stat` hooks), section headers, catalog link in the courses header, and **not** the old `bfc-my-courses__catalog-cta` standalone button; certificate embed test keeps passing with the new wrapper/header.
- `CatalogBlockTest` (existing file; extend): render contains tile markup, course count, enrolled tile with Continue-course link, invite-required state intact.
- `LearnerPortalHeadingsTest`: `core/query-title` output is dropped on the `bfc_course` archive and kept on a non-course archive.
- `AccessLearnerTest`: the legal-name lock tests are rewritten — learner **can** change their own name via the self-service method and an audit entry (`by` = self) is appended; raw `update_user_meta` / delete bypasses still fail; setting another user's name without `enroll_bfc_courses` still fails.
- `LearnerRestTest`: legal-name route — guest 401/403, no-access learner 403, empty name 400, success 200 with stored name + audit entry, other user's name cannot be targeted (route always acts on the current user; no user id parameter exists).
- Existing soft-gate tests (`ProfileMyCoursesSoftGateTest`, `LearnerBlockTitleTest`, catalog gate coverage) must still pass: gated renders contain none of the new scaffolding.
- JS has no unit harness in this repo; view.js changes are covered by lint (`wp-scripts lint-js`) and manual browser QA.
- Manual QA on `cert.responseforge.app` (or wp-env) after build: light + dark, desktop + ~390px; my-courses completed/active/withdrawn tiles, withdraw in place, empty-enrollment user if available; catalog filter/search over tiles, Enroll from a tile, enrolled tile Continue link; profile legal-name edit → save → certificate merge field shows the new name on the next issued certificate; duplicate H1 gone on /courses/.

## Out of scope

- Portal shell, single-course page, PressPrimer certificate internals, progress percentages, course images on tiles (neither endpoint supplies them today), renaming already-issued certificates, admin legal-name UI changes.
