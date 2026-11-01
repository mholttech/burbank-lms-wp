# My Courses redesign — summary strip, tile grid, no sidebar

## Intent

Live QA on `cert.responseforge.app/my-courses/` found the enrolled-course area reads as loose text, not a record: a title stranded at the left, a hollow status pill at the far right, and a primary-red "Browse course catalog" button dangling below. Iterative fixes (bordered card, no panel shadow, more padding, meta line + action) improved the row but not the overall page layout, which the site owner rejected. This spec replaces the My Courses page layout wholesale.

**Portal chrome is out of scope.** `Learner\Shell` (Training kicker, display-name H1, lede, Catalog / My Courses / Profile pill track) stays exactly as-is. The redesign covers everything the `burbank-lms/my-courses` block renders below the shell.

**Domain rules** in `docs/learner-access.md` stay authoritative. No REST, schema, enrollment, or certificate-issuance changes: the redesign consumes data that already exists.

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
tests/php/MyCoursesBlockTest.php   Render assertions follow new scaffolding
docs/learner-access.md             My Courses row in block table updated
```

No changes to `src/REST/`, `src/Enrollment/`, `src/Progress/`, `src/Learner/Shell.php`, or `resources/frontend/scss/pressprimer.scss`.

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

- REST failure: existing status line error ("Could not load courses."), stats strip stays hidden, section headers still render.
- No enrollments: list hidden; empty tile (message + catalog link) shows; stats show Enrolled 0 / In progress 0 / Completed 0 (strip visible — it is accurate), Certificates stat per the counting rule.
- Certificates shortcode absent: no certificates section, no Certificates stat.

## Testing

- `MyCoursesBlockTest`: logged-in render contains stats scaffolding (`data-bfc-stat` hooks), section headers, catalog link in the courses header, and **not** the old `bfc-my-courses__catalog-cta` standalone button; certificate embed test keeps passing with the new wrapper/header.
- Existing soft-gate tests (`ProfileMyCoursesSoftGateTest`, `LearnerBlockTitleTest`) must still pass: gated render contains none of the new scaffolding.
- JS has no unit harness in this repo; view.js changes are covered by lint (`wp-scripts lint-js`) and manual browser QA.
- Manual QA on `cert.responseforge.app` (or wp-env) after build: light + dark, desktop + ~390px; completed, active, withdrawn tiles; withdraw in place; empty-enrollment user if available.

## Out of scope

- Portal shell, catalog/course/profile pages, PressPrimer certificate internals, REST changes, progress percentages, course images/excerpts on tiles (the enrollments endpoint returns none today).
