# FSE learner UI integration — plugin polish + theme shells

## Intent and accepted decisions

Core learner flows already work (site invite registration, open/restricted enroll, mapped quiz gate, pass → certificate → completed). The course single in a dark FSE theme currently shows bare LMS metadata and a “Take quiz” link beside a polished PressPrimer quiz card. Plugin FSE fallbacks omit course body content; block styles are layout-only; catalog/my-courses lack search/status polish and cert history; design-promised quiz course chrome was never built. PressPrimer skinning lives in `burbankcert-fse` instead of the LMS plugin.

**Accepted decisions**

- **Scope:** Full learner suite this pass, including quiz/certificate chrome (Approach C from planning).
- **Repos:** Plugin + theme (`burbankcert-fse` at `burbankcert.org/site/web/app/themes/burbankcert-fse`).
- **UI ownership (Approach 2):** Plugin owns polished learner block markup/SCSS and PressPrimer CSS skinning (moved from the theme). Theme owns thin FSE shells only (templates with header/footer, page patterns, nav links).
- **IA:** New LMS paths — course archive (CPT), `/my-courses/`, `/register/`, `/profile/`. Header adds Courses / My Courses / Register. **Volunteer Login stays `/member-portal/`** (membership platform, not LMS).
- **Pages:** Theme ships patterns + nav targets; plugin does not auto-create WordPress pages. Site Editor or deploy creates pages once.
- **Skinning rule:** Plugin CSS uses `--wp--preset--color-*` first, then optional CERT vars (`--bc-surface-*`, `html[data-bc-theme]`) when present, with solid fallbacks so other block themes remain coherent.
- Email verification is already removed on `main`; register success copy must not mention verifying email.

## Approaches

**Selected:** Plugin-polished UI + theme CSS/shell thinness; migrate existing PressPrimer theme skinning into `burbank-lms`. Theme still adds `single-bfc_course` / `archive-bfc_course` and learner page patterns so CERT header/footer wrap LMS blocks.

**Considered:** Split ownership with theme-owned LMS visual language (Approach 1) — rejected in favor of consolidating PP + learner polish in the plugin. Theme-overrides-everything (Approach 3) — rejected; leaves non-CERT block themes bare and drifts status/chrome logic.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  Theme (thin): header/footer templates, patterns, nav       │
└────────────────────────────┬────────────────────────────────┘
                             │ composes
┌────────────────────────────▼────────────────────────────────┐
│  Plugin blocks + fallback FSE templates + quiz chrome       │
│  catalog / course / enroll / my-courses / register / profile│
└───────────────┬─────────────────────────────┬───────────────┘
                │                             │
┌───────────────▼──────────┐   ┌──────────────▼───────────────┐
│ Domain (unchanged)       │   │ Branding + PP CSS enqueue    │
│ Access / Enrollment /    │   │ token-mapped --ppq/--ppcert  │
│ Progress / Learner       │   │ (moved from theme)           │
└──────────────────────────┘   └──────────────────────────────┘
```

Domain rules, REST, and PressPrimer access gates stay as in `docs/learner-access.md`. This design is presentation, composition, and chrome only.

### Repo split

| Repo | Deliverables |
| --- | --- |
| `burbank-lms-wp` | Block markup/SCSS; catalog filters; course status chrome; quiz before/results chrome; H1→H2 demotion; PP CSS + enqueue; richer plugin fallback templates; tests; `docs/learner-access.md` |
| `burbankcert-fse` | `templates/single-bfc_course.html`, `archive-bfc_course.html`; patterns for register / my-courses / profile; header/footer nav; remove `assets/css/pressprimer.css` and theme quiz-heading filter / enqueue |

Plugin remains usable without the CERT theme.

## Course single

### Theme template

`templates/single-bfc_course.html` (model: `single-osec_event.html`):

1. Header template part  
2. Constrained main: post title → featured image (if set) → **post content** → `burbank-lms/course`  
3. Footer template part  

### Plugin fallback

`Learner\Templates::single_content` uses the same block order (title, featured image, content, course block) without theme parts so materials and chrome appear on any block theme.

### Course block chrome

Replace bare paragraphs with structured chrome:

1. **Meta row** — BFC code; humanized outcome/subject (not raw slug alone); revision when present.  
2. **Status** — not enrolled / enrolled / quiz passed / completed (credential hint when completed).  
3. **Actions** — existing enroll / invite / gate logic. When enrolled with a mapped quiz: one primary CTA that scrolls to `#bfc-course-quiz` (no competing standalone “Take quiz” link beside the embed).  
4. **Quiz region** — `#bfc-course-quiz` embeds `[pressprimer_quiz]` only when enrolled.

Buttons use `.wp-element-button`-compatible classes; forms follow existing CERT/AFB height and radius language via plugin SCSS + tokens.

## Catalog, My Courses, register, profile

### Archive

Theme `templates/archive-bfc_course.html`: header → “Course catalog” heading → `burbank-lms/catalog` → footer. Plugin fallback keeps heading + catalog block.

### Catalog block

- Client-side search/filter: title + BFC code text match; optional subject dropdown from distinct published `subject_category` values on the rendered list (no new REST for v1; catalog size stays full publish set).  
- Each item: title link, excerpt, BFC code, subject/outcome labels, status chip (`Enrolled` / `Invite required` / open Enroll CTA) under existing access rules.  
- Polished list/card layout in plugin SCSS.

### My Courses

- Keep REST-driven Active / Passed / Completed / Withdrawn list and withdraw.  
- When PressPrimer Certificate is active, embed `[ppcert_my_certificates]` below the list (learner-owned history; PP enforces visibility).  
- Empty state CTA links to the course catalog archive URL.

### Register / profile

- Polished forms; register success must not mention email verification.  
- Theme patterns for `/register/`, `/profile/`, `/my-courses/` (header/footer + block). Pages are created once in Site Editor or deploy — not by the plugin.

### Navigation

Theme header/footer: add Courses → `bfc_course` archive URL, My Courses → `/my-courses/`, Register → `/register/` (logged-out). Do not change Volunteer Login → `/member-portal/`.

## Quiz / certificate chrome and PressPrimer skinning

### Quiz chrome (mapped quizzes only)

Extend `Integrations\PressPrimer_Quiz` (hooks registered via `Loader`) to print Burbank chrome on:

- `pressprimer_quiz_before_quiz_meta` — “Back to course” title link + enrollment status chip  
- `pressprimer_quiz_before_results` / `pressprimer_quiz_after_results` — same status + link after attempt  

Unmapped quizzes: no chrome.  
Move theme `burbankcert_quiz_heading` behavior into the plugin: demote `.ppq-quiz-title` H1 → H2 when the quiz renders under a course page H1 / course embed context.

### Certificate

My Courses embeds `[ppcert_my_certificates]` when the Certificate plugin is active. No new Burbank certificate UI; verification stays on PressPrimer.

### Skinning migration

- Move `burbankcert-fse/assets/css/pressprimer.css` into the plugin (frontend build entry or dedicated enqueue when PP Quiz/Certificate is present on the front).  
- Map `--ppq-*` / `--ppcert-*` to WordPress presets; honor CERT `--bc-surface-*` and `html[data-bc-theme="dark"]` when defined.  
- Theme: stop enqueueing PressPrimer CSS and remove the quiz-heading filter from `inc/pressprimer.php` (delete file if empty).  
- Enqueue more broadly than the theme’s former `is_singular()` gate: also when LMS course / my-courses blocks or PP certificate shortcodes render.

### Out of scope

Certificate upsell/mascot; replacing PP player markup; cert.vip SSO; auto-creating WP pages; changing membership portal; moving `bc_level` / `bc_requirement` ownership; multi-course curricula.

## Error handling

- Quiz gate denials remain PressPrimer filter shapes (unchanged).  
- Chrome hooks no-op when course cannot be resolved for a quiz.  
- Certificate shortcode absent → My Courses skips embed without fatals.  
- Catalog client filter with empty results shows an empty-state message, not a blank list with no explanation.

## Testing

### Plugin (PHPUnit)

- Course / catalog / my-courses render: status chips, invite vs open CTAs, quiz embed only when enrolled; register success copy has no email-verify string.  
- Quiz chrome: mapped quiz prints back-link + status on before_meta / results hooks; unmapped prints nothing.  
- H1→H2 demotion for `.ppq-quiz-title`.  
- `LearnerTemplatesTest`: single template markup includes post content (and featured image).  
- Existing access / enroll / progress / gate tests stay green.

### Theme (manual)

- Course single / archive show header/footer + LMS blocks and post content.  
- Patterns exist; nav hits `/my-courses/`, `/register/`, catalog; Volunteer Login still `/member-portal/`.  
- Theme no longer enqueues PressPrimer CSS; dark mode still correct via CERT tokens consumed by plugin CSS.

## Docs and delivery

- Update `docs/learner-access.md` (blocks/FSE composition, quiz chrome, PP skinning ownership).  
- Short theme-side note for LMS templates/patterns/nav and “create these pages once.”  
- Two coordinated PRs: **plugin first** (UI + chrome + PP CSS), then **theme** (templates/patterns/nav + remove PP skinning).

## Success criteria

1. Enrolled learner on a dark CERT course single sees authored course content, coherent LMS chrome, and a token-skinned PressPrimer player with back-to-course + status — not bare meta + a lone “Take quiz” link.  
2. Catalog is searchable/filterable with status chips; My Courses shows enrollments plus certificate history when PP Certificate is active.  
3. Register / profile / my-courses patterns and nav exist in the theme without touching Volunteer Login.  
4. Plugin works without `burbankcert-fse`; CERT theme no longer owns PressPrimer CSS.
