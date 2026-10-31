# FSE Learner UI Integration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Polish learner blocks and PressPrimer skinning inside `burbank-lms`, add quiz course chrome, then wire thin FSE templates/patterns/nav in `burbankcert-fse` so the CERT theme fully hosts the learner suite.

**Architecture:** Plugin owns block markup/SCSS, quiz chrome hooks, and token-mapped PressPrimer CSS (migrated from the theme). Theme owns header/footer course templates, page patterns for `/register/` `/my-courses/` `/profile/`, and nav links — Volunteer Login stays `/member-portal/`. Domain/REST/gates unchanged.

**Tech Stack:** WordPress 7.1.3+, PHP 8.2+, Gutenberg blocks (`@wordpress/scripts`), PressPrimer Quiz 3.1.3 / Certificate 2.0.2, PHPUnit via `npm run test:php`, PHPStan, PHPCS. Theme: Neve FSE child `burbankcert-fse`.

**Spec:** `docs/superpowers/specs/2026-10-30-fse-learner-ui-integration-design.md`

## Global Constraints

- Namespace `Burbank_LMS`, text domain `burbank-lms`; register hooks only through `Loader` in `src/Burbank_LMS.php`; no business logic in constructors; do not hand-edit `build/`.
- Plugin must work without `burbankcert-fse`. CSS uses `--wp--preset--color-*` first, then optional `--bc-surface-*` / `html[data-bc-theme]` with solid fallbacks.
- Do not auto-create WordPress pages. Do not change Volunteer Login → `/member-portal/`.
- Do not revive email verification. Register success must not mention verifying email.
- Do not implement Certificate upsell/mascot, cert.vip SSO, or matrix ownership moves.
- After each plugin task: `npm run test:php -- --filter <TestClass>`, `composer run phpstan`, `composer run phpcs` on touched files; `npm run build` when assets/blocks change.
- Theme work lives under `/Users/mwholt/GIT/mholttech/burbankcert.org/site/web/app/themes/burbankcert-fse` (separate git repo / PR after plugin).

---

## File map

| Path | Responsibility |
| --- | --- |
| `src/Learner/Templates.php` | Plugin fallback archive/single block markup (add post content + featured image) |
| `src/Learner/Presentation.php` | Humanized labels + learner enrollment status projection for chrome |
| `src/Blocks/course/render.php` + `style.scss` + `view.js` | Course meta/status/actions/quiz region chrome |
| `src/Blocks/catalog/*` | Search/filter UI + status chips + polish |
| `src/Blocks/my-courses/*` | Cert shortcode embed + empty CTA + polish |
| `src/Blocks/register/view.js` + `style.scss` | Success copy fix + form polish |
| `src/Blocks/profile/style.scss`, `src/Blocks/enroll/style.scss` | Form polish consistency |
| `src/Integrations/PressPrimer_Quiz.php` | Quiz chrome + H1→H2 demotion |
| `src/Integrations/PressPrimer_Assets.php` | Conditional enqueue of migrated PP CSS |
| `resources/frontend/scss/pressprimer.scss` | Token-mapped PP/Certificate skin (from theme) |
| `webpack.config.js` | Entry `burbank-lms-pressprimer` |
| `src/Burbank_LMS.php` | Wire new hooks/enqueue |
| `tests/php/LearnerTemplatesTest.php`, `LearnerPresentationTest.php`, `CourseBlockRenderTest.php`, `CatalogBlockTest.php`, `PressPrimerChromeTest.php`, `PressPrimerAssetsTest.php` | Coverage |
| `docs/learner-access.md`, `AGENTS.md` | Ops docs |
| Theme `templates/single-bfc_course.html`, `archive-bfc_course.html` | CERT FSE shells |
| Theme `patterns/lms-*.php` | Register / my-courses / profile patterns |
| Theme `parts/header.html`, `parts/footer.html` | Courses / My Courses / Register nav |
| Theme `inc/pressprimer.php`, `assets/css/pressprimer.css` | Remove after migration |
| Theme `docs/lms-learner.md` | Create pages once + nav notes |

---

### Task 1: Plugin fallback single/archive templates include course body

**Files:**
- Modify: `src/Learner/Templates.php`
- Test: `tests/php/LearnerTemplatesTest.php`

**Interfaces:**
- Consumes: `register_block_template` (WP 6.7+)
- Produces: single content string containing `wp:post-featured-image`, `wp:post-content`, and `wp:burbank-lms/course`

- [ ] **Step 1: Write the failing test**

```php
public function test_single_template_includes_post_content(): void {
	if ( ! function_exists( 'register_block_template' ) ) {
		$this->markTestSkipped( 'register_block_template requires WordPress 6.7+.' );
	}
	$templates = new \Burbank_LMS\Learner\Templates();
	$ref = new ReflectionClass( $templates );
	$method = $ref->getMethod( 'single_content' );
	$method->setAccessible( true );
	$content = (string) $method->invoke( $templates );
	$this->assertStringContainsString( 'wp:post-content', $content );
	$this->assertStringContainsString( 'wp:post-featured-image', $content );
	$this->assertStringContainsString( 'wp:burbank-lms/course', $content );
	$this->assertStringContainsString( 'wp:post-title', $content );
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm run test:php -- --filter LearnerTemplatesTest::test_single_template_includes_post_content`  
Expected: FAIL (post-content / featured-image missing)

- [ ] **Step 3: Implement `single_content` (and keep archive as heading + catalog)**

```php
private function single_content(): string {
	return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:post-title {"level":1} /-->
	<!-- wp:post-featured-image /-->
	<!-- wp:post-content /-->
	<!-- wp:burbank-lms/course /-->
</div>
<!-- /wp:group -->';
}
```

Remove `wp:post-excerpt` from the single (body is in post content; chrome owns meta). Keep archive markup with H1 + `burbank-lms/catalog`.

- [ ] **Step 4: Run tests + phpstan/phpcs on touched files — expect PASS**

- [ ] **Step 5: Commit**

```bash
git add src/Learner/Templates.php tests/php/LearnerTemplatesTest.php
git commit -m "$(cat <<'EOF'
feat(learner): include post content in course FSE single template

EOF
)"
```

---

### Task 2: Learner presentation helpers (labels + status)

**Files:**
- Create: `src/Learner/Presentation.php`
- Modify: `src/Enrollment/Service.php` — add `get( int $user_id, int $course_id ): ?object` delegating to `Repository::get`
- Test: `tests/php/LearnerPresentationTest.php`

**Interfaces:**
- Consumes: `Enrollment\Service::get`, course post meta (`bfc_code`, `course_revision`, `subject_category`, `course_outcome`)
- Produces:
  - `Presentation::label( string $slug ): string` — `independent_study` → `Independent study`
  - `Presentation::enrollment_status( ?object $row ): string` — `none|enrolled|passed|completed|withdrawn`
  - `Presentation::status_label( string $status ): string` — translated UI string
  - `Service::get( int $user_id, int $course_id ): ?object`

Status rules:

| Row | Result |
| --- | --- |
| null | `none` |
| `status=withdrawn` | `withdrawn` |
| `status=completed` | `completed` |
| `status=active` and `quiz_passed_at` set | `passed` |
| `status=active` | `enrolled` |

- [ ] **Step 1: Write failing tests**

```php
public function test_label_humanizes_slug(): void {
	$p = new \Burbank_LMS\Learner\Presentation();
	$this->assertSame( 'Independent study', $p->label( 'independent_study' ) );
	$this->assertSame( 'Communications', $p->label( 'communications' ) );
}

public function test_enrollment_status_projection(): void {
	$p = new \Burbank_LMS\Learner\Presentation();
	$this->assertSame( 'none', $p->enrollment_status( null ) );
	$this->assertSame( 'enrolled', $p->enrollment_status( (object) array( 'status' => 'active', 'quiz_passed_at' => null ) ) );
	$this->assertSame( 'passed', $p->enrollment_status( (object) array( 'status' => 'active', 'quiz_passed_at' => '2026-01-01 00:00:00' ) ) );
	$this->assertSame( 'completed', $p->enrollment_status( (object) array( 'status' => 'completed', 'quiz_passed_at' => '2026-01-01 00:00:00' ) ) );
	$this->assertSame( 'withdrawn', $p->enrollment_status( (object) array( 'status' => 'withdrawn', 'quiz_passed_at' => null ) ) );
}
```

- [ ] **Step 2: Run — expect FAIL (class missing)**

- [ ] **Step 3: Implement `Presentation` + `Service::get`**

```php
namespace Burbank_LMS\Learner;

class Presentation {
	public function label( string $slug ): string {
		$slug = trim( $slug );
		if ( '' === $slug ) {
			return '';
		}
		$words = array_map(
			static fn( string $w ): string => $w === '' ? '' : ( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( mb_substr( $w, 0, 1 ) ) . mb_substr( $w, 1 ) : ucfirst( $w ) ),
			explode( '_', $slug )
		);
		return implode( ' ', array_filter( $words, static fn( string $w ): bool => '' !== $w ) );
	}

	public function enrollment_status( ?object $row ): string {
		if ( ! $row ) {
			return 'none';
		}
		$status = isset( $row->status ) ? (string) $row->status : '';
		if ( 'withdrawn' === $status ) {
			return 'withdrawn';
		}
		if ( 'completed' === $status ) {
			return 'completed';
		}
		if ( 'active' === $status && ! empty( $row->quiz_passed_at ) ) {
			return 'passed';
		}
		if ( 'active' === $status ) {
			return 'enrolled';
		}
		return 'none';
	}

	public function status_label( string $status ): string {
		return match ( $status ) {
			'enrolled' => __( 'You are enrolled in this course.', 'burbank-lms' ),
			'passed' => __( 'Quiz passed — certificate pending.', 'burbank-lms' ),
			'completed' => __( 'Course completed.', 'burbank-lms' ),
			'withdrawn' => __( 'You withdrew from this course.', 'burbank-lms' ),
			default => '',
		};
	}
}
```

Add on `Enrollment\Service`:

```php
public function get( int $user_id, int $course_id ): ?object {
	return $this->repository()->get( $user_id, $course_id );
}
```

- [ ] **Step 4: Run tests + phpstan/phpcs — expect PASS**

- [ ] **Step 5: Commit** `feat(learner): add presentation labels and enrollment status`

---

### Task 3: Course block chrome

**Files:**
- Modify: `src/Blocks/course/render.php`, `src/Blocks/course/style.scss`, `src/Blocks/course/view.js` (only if enroll handlers need button class updates)
- Test: `tests/php/CourseBlockRenderTest.php` (new)

**Interfaces:**
- Consumes: `Presentation`, `Enrollment\Service::get` / `get_active`, `Invites`, `Learner`, `Block_Context::quiz_url`
- Produces: markup with classes `bfc-course__meta`, `bfc-course__status`, `bfc-course__quiz-cta` (anchor to `#bfc-course-quiz`), `bfc-course__quiz-embed`

- [ ] **Step 1: Write failing render test**

Create a published `bfc_course` with meta `bfc_code=BFC-998`, `course_outcome=independent_study`, `subject_category=communications`, `course_revision=1.0`, `pressprimer_quiz_id=1`. Grant learner access, insert active enrollment. `do_blocks( '<!-- wp:burbank-lms/course {"courseId":ID} /-->' )` (or include `render.php` via `render_block`) and assert:

- Contains `BFC-998`, `Independent study`, `Communications`, revision `1.0`
- Contains enrolled status text
- Contains `#bfc-course-quiz` CTA
- Does **not** contain a bare standalone “Take quiz” paragraph link as the only quiz affordance without the CTA class (prefer assert presence of `bfc-course__quiz-cta` and `bfc-course__quiz-embed`)

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Rewrite `render.php` chrome**

Structure:

```php
$presentation = new \Burbank_LMS\Learner\Presentation();
$row          = $user_id > 0 ? ( new Enrollment_Service() )->get( $user_id, $course_id ) : null;
$status_key   = $presentation->enrollment_status( $row );
$active       = $user_id > 0 ? ( new Enrollment_Service() )->get_active( $user_id, $course_id ) : null;
// meta: code, subject label, outcome label, revision
// status paragraph when status_key !== none
// actions: invite form / enroll / gates (existing)
// if $active && quiz_id: <a class="bfc-course__quiz-cta wp-element-button" href="#bfc-course-quiz">…</a> then embed shortcode in #bfc-course-quiz
```

Use translated CTA: `Start quiz` or `Continue to quiz` (enrolled/passed). Remove the old plain `<a>Take quiz</a>` paragraph.

SCSS: grid chrome, meta row, status chip, button styles using:

```scss
.bfc-course {
	display: grid;
	gap: 1rem;
	padding: clamp(1rem, 2vw, 1.5rem);
	border: 1px solid var(--wp--preset--color--content-border, #c3c4c7);
	border-radius: 6px;
	background: var(--bc-surface-card, var(--wp--preset--color--content-background, #fff));
	color: var(--wp--preset--color--content-text, inherit);

	&__quiz-cta {
		display: inline-block;
		// rely on .wp-element-button when theme provides it
	}
	&__notice.is-error { color: var(--wp--preset--color--action-red, #b32d2e); }
}
```

- [ ] **Step 4: `npm run build` then run CourseBlockRenderTest + phpstan/phpcs — PASS**

- [ ] **Step 5: Commit** `feat(blocks): polish course learner chrome`

---

### Task 4: Catalog search, filters, status chips

**Files:**
- Modify: `src/Blocks/catalog/render.php`, `view.js`, `style.scss`
- Test: `tests/php/CatalogBlockTest.php` (new)

**Interfaces:**
- Consumes: `Catalog::query`, `Presentation`, enrollment/invite services
- Produces: filter controls + `data-bfc-title`, `data-bfc-code`, `data-bfc-subject` on each item for client filter

- [ ] **Step 1: Write failing test** that rendered catalog for two courses includes search input `data-bfc-catalog-search`, subject select `data-bfc-catalog-subject`, and item `data-bfc-code` attributes.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Implement render + view.js filter**

Render extras per item: code, subject/outcome labels via `Presentation::label`, status chip (`Enrolled` / `Invite required` / Enroll button). Collect unique subjects for `<select>`.

`view.js` filter logic:

```javascript
function applyFilters( root ) {
	const q = ( root.querySelector( '[data-bfc-catalog-search]' )?.value || '' ).toLowerCase().trim();
	const subject = root.querySelector( '[data-bfc-catalog-subject]' )?.value || '';
	let visible = 0;
	root.querySelectorAll( '.bfc-catalog__item' ).forEach( ( item ) => {
		const title = ( item.dataset.bfcTitle || '' ).toLowerCase();
		const code = ( item.dataset.bfcCode || '' ).toLowerCase();
		const subj = item.dataset.bfcSubject || '';
		const matchQ = ! q || title.includes( q ) || code.includes( q );
		const matchS = ! subject || subj === subject;
		const show = matchQ && matchS;
		item.hidden = ! show;
		if ( show ) visible += 1;
	} );
	const empty = root.querySelector( '[data-bfc-catalog-empty-filter]' );
	if ( empty ) empty.hidden = visible > 0;
}
```

Empty-filter message: “No courses match your filters.”

Polish SCSS similarly to course chrome (list/cards, chips, buttons).

- [ ] **Step 4: Build + tests + phpstan/phpcs — PASS**

- [ ] **Step 5: Commit** `feat(blocks): catalog search filters and status chips`

---

### Task 5: Register / profile / enroll form polish + register copy fix

**Files:**
- Modify: `src/Blocks/register/view.js`, `style.scss`; `src/Blocks/profile/style.scss`; `src/Blocks/enroll/style.scss`
- Test: grep-style unit not required for JS — add `tests/php` only if render strings change; for JS, assert in a small Node-less check by documenting manual QA, **and** add a PHPUnit that reads `view.js` source for the success string (simple file assert) or a dedicated `RegisterBlockCopyTest.php`:

```php
public function test_register_success_copy_omits_email_verification(): void {
	$js = file_get_contents( dirname( __DIR__, 2 ) . '/src/Blocks/register/view.js' );
	$this->assertIsString( $js );
	$this->assertStringNotContainsString( 'verify before enrolling', $js );
	$this->assertStringContainsString( 'Account created', $js );
}
```

- [ ] **Step 1: Write failing copy test — expect FAIL** (current string mentions verify)

- [ ] **Step 2: Change success message**

```javascript
setStatus(
	status,
	'Account created. You can sign in and enroll in courses.',
	false
);
```

- [ ] **Step 3: Shared form SCSS pattern** on register/profile/enroll: label/input gap, `min-height: 46px` inputs, `border-radius: 6px`, submit with `wp-element-button` class in render.php buttons where missing.

- [ ] **Step 4: Build + tests — PASS**

- [ ] **Step 5: Commit** `fix(blocks): drop email-verify register copy and polish forms`

---

### Task 6: My Courses certificate embed + empty CTA

**Files:**
- Modify: `src/Blocks/my-courses/render.php`, `view.js`, `style.scss`
- Test: `tests/php/MyCoursesBlockTest.php` (new)

**Interfaces:**
- Consumes: `shortcode_exists( 'ppcert_my_certificates' )`, `get_post_type_archive_link( 'bfc_course' )`
- Produces: empty-state link to catalog; optional `.bfc-my-courses__certificates` with shortcode

- [ ] **Step 1: Failing test** — logged-in render includes catalog archive link with class `bfc-my-courses__catalog-cta`. When shortcode exists (stub `add_shortcode( 'ppcert_my_certificates', fn() => '<div class="ppcert-stub"></div>' )`), render includes `bfc-my-courses__certificates`.

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Implement**

```php
$archive = get_post_type_archive_link( 'bfc_course' );
// in empty paragraph area / always show catalog CTA:
if ( is_string( $archive ) && $archive ) {
	printf( '<p class="bfc-my-courses__catalog-cta"><a class="wp-element-button" href="%s">%s</a></p>', esc_url( $archive ), esc_html__( 'Browse course catalog', 'burbank-lms' ) );
}
if ( shortcode_exists( 'ppcert_my_certificates' ) ) {
	echo '<div class="bfc-my-courses__certificates">';
	echo do_shortcode( '[ppcert_my_certificates]' );
	echo '</div>';
}
```

Polish list/badge SCSS. Keep withdraw behavior.

- [ ] **Step 4: Tests + build — PASS**

- [ ] **Step 5: Commit** `feat(blocks): my-courses catalog CTA and certificate history`

---

### Task 7: Quiz course chrome + H1 demotion

**Files:**
- Modify: `src/Integrations/PressPrimer_Quiz.php`, `src/Burbank_LMS.php`
- Test: `tests/php/PressPrimerChromeTest.php` (new)

**Interfaces:**
- Consumes: existing `published_course_id_for_quiz` (promote to protected/public for tests or keep private and test via actions), `Presentation`, `Enrollment\Service::get`
- Produces: HTML echoed on hooks; filter `render_block_pressprimer-quiz/quiz` for H1→H2

Make `published_course_id_for_quiz` **protected** so tests can subclass, **or** add public `course_id_for_quiz( int $quiz_id ): ?int` wrapper.

Hook methods:

```php
public function before_quiz_meta( $quiz ): void {
	$this->render_chrome( $quiz );
}

public function before_results( $attempt_or_quiz ): void {
	$this->render_chrome_from_context( $attempt_or_quiz );
}

public function after_results( $attempt_or_quiz ): void {
	$this->render_chrome_from_context( $attempt_or_quiz );
}

public function demote_quiz_title( string $html ): string {
	// port theme burbankcert_quiz_heading regex for h1.ppq-quiz-title → h2
	return preg_replace_callback( ... ) ?? $html;
}
```

Chrome markup:

```php
echo '<div class="bfc-quiz-chrome">';
printf( '<a class="bfc-quiz-chrome__back" href="%s">%s</a>', esc_url( get_permalink( $course_id ) ), esc_html( sprintf( /* translators: %s course title */ __( 'Back to %s', 'burbank-lms' ), get_the_title( $course_id ) ) ) );
$status = ( new Presentation() )->enrollment_status( $this->enrollment()->get( get_current_user_id(), $course_id ) );
$label  = ( new Presentation() )->status_label( $status );
if ( $label ) {
	printf( '<p class="bfc-quiz-chrome__status">%s</p>', esc_html( $label ) );
}
echo '</div>';
```

Unmapped quiz: `render_chrome` returns immediately.

Wire in `Burbank_LMS.php`:

```php
$this->loader->add_action( 'pressprimer_quiz_before_quiz_meta', $pressprimer_quiz, 'before_quiz_meta', 10, 1 );
$this->loader->add_action( 'pressprimer_quiz_before_results', $pressprimer_quiz, 'before_results', 10, 1 );
$this->loader->add_action( 'pressprimer_quiz_after_results', $pressprimer_quiz, 'after_results', 10, 1 );
$this->loader->add_filter( 'render_block_pressprimer-quiz/quiz', $pressprimer_quiz, 'demote_quiz_title', 10, 1 );
```

Add minimal `.bfc-quiz-chrome` rules to `resources/frontend/scss/app.scss` or course style (frontend entry is fine).

- [ ] **Step 1: Failing tests** — mapped quiz echoes `bfc-quiz-chrome`; unmapped echoes nothing; `demote_quiz_title` turns `<h1 class="ppq-quiz-title">X</h1>` into `h2`.

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Implement + wire hooks**

- [ ] **Step 4: Tests + phpstan/phpcs — PASS**

- [ ] **Step 5: Commit** `feat(integrations): quiz course chrome and title demotion`

---

### Task 8: Migrate PressPrimer CSS into the plugin

**Files:**
- Create: `resources/frontend/scss/pressprimer.scss` (content from theme `assets/css/pressprimer.css`, plus fallbacks for `--bc-surface-card` / `--bc-surface-subtle`)
- Modify: `webpack.config.js` — add entry `burbank-lms-pressprimer`
- Create: `src/Integrations/PressPrimer_Assets.php`
- Modify: `src/Burbank_LMS.php` — enqueue on `wp_enqueue_scripts`
- Test: `tests/php/PressPrimerAssetsTest.php`

**Fallback pattern:** when porting theme rules, replace bare `var(--bc-surface-card)` / `var(--bc-surface-subtle)` with nested fallbacks (do not self-reference):

```scss
var(--bc-surface-card, var(--wp--preset--color--content-background, #fff))
var(--bc-surface-subtle, var(--wp--preset--color--content-background, #f6f7f7))
```

Keep `html[data-bc-theme="dark"]` selectors — no-ops when attribute absent.

Enqueue when any of: `is_singular( 'bfc_course' )`, `is_post_type_archive( 'bfc_course' )`, has block `burbank-lms/course` / `burbank-lms/my-courses`, or PP quiz/certificate plugins active on singular content. Practical v1:

```php
public function enqueue(): void {
	$need = is_singular( 'bfc_course' )
		|| is_post_type_archive( 'bfc_course' )
		|| has_block( 'burbank-lms/course' )
		|| has_block( 'burbank-lms/my-courses' )
		|| ( is_singular() && (
			file_exists( WP_PLUGIN_DIR . '/pressprimer-quiz/pressprimer-quiz.php' )
			|| file_exists( WP_PLUGIN_DIR . '/pressprimer-certificate/pressprimer-certificate.php' )
		) );
	if ( ! $need ) {
		return;
	}
	$path = BURBANK_LMS_PATH . 'build/burbank-lms-pressprimer.css'; // use actual constant from constants.php
	if ( ! file_exists( $path ) ) {
		return;
	}
	wp_enqueue_style( 'burbank-lms-pressprimer', /* url */, array(), (string) filemtime( $path ) );
}
```

Use the same path/url helpers as `Burbank_LMS::enqueue_entrypoint`.

- [ ] **Step 1: Failing test** that `PressPrimer_Assets` registers style handle when `is_singular( 'bfc_course' )` after `enqueue` (use `$this->go_to( get_permalink( $course_id ) )`).

- [ ] **Step 2: Implement SCSS + webpack entry + class + wire**

- [ ] **Step 3: `npm run build` — confirm `build/burbank-lms-pressprimer.css` exists**

- [ ] **Step 4: Tests + phpstan/phpcs — PASS**

- [ ] **Step 5: Commit** `feat(branding): ship PressPrimer front-end skin from LMS plugin`

---

### Task 9: Plugin docs

**Files:**
- Modify: `docs/learner-access.md` (Blocks and FSE + Mail/branding sections), `AGENTS.md` feature quick reference if needed

- [ ] **Step 1: Update learner-access** — document post-content in single template, quiz chrome hooks, PP CSS owned by plugin, theme shells optional, register success without email verify, catalog filters, my-courses cert embed.

- [ ] **Step 2: Commit** `docs: document FSE learner UI and PressPrimer skinning ownership`

---

### Task 10: Theme course templates

**Files (theme repo):**
- Create: `templates/single-bfc_course.html`
- Create: `templates/archive-bfc_course.html`

**Model:** `templates/single-osec_event.html`

- [ ] **Step 1: Add single**

```html
<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main","className":"bc-bfc-course-main","layout":{"type":"constrained"}} -->
<main class="wp-block-group bc-bfc-course-main">
	<!-- wp:post-title {"level":1} /-->
	<!-- wp:post-featured-image /-->
	<!-- wp:post-content {"layout":{"type":"constrained"}} /-->
	<!-- wp:burbank-lms/course /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
```

- [ ] **Step 2: Add archive**

```html
<!-- wp:template-part {"slug":"header","tagName":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main","className":"bc-bfc-catalog-main","layout":{"type":"constrained"}} -->
<main class="wp-block-group bc-bfc-catalog-main">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">Course catalog</h1>
	<!-- /wp:heading -->
	<!-- wp:burbank-lms/catalog /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","tagName":"footer","area":"footer"} /-->
```

- [ ] **Step 3: Commit in theme repo** `feat(theme): FSE templates for bfc_course single and archive`

---

### Task 11: Theme patterns, nav, remove PP skinning

**Files (theme repo):**
- Create: `patterns/lms-register.php`, `patterns/lms-my-courses.php`, `patterns/lms-profile.php`
- Create: `docs/lms-learner.md`
- Modify: `parts/header.html`, `parts/footer.html`
- Modify: `functions.php` — remove `require_once .../inc/pressprimer.php`
- Delete: `inc/pressprimer.php`, `assets/css/pressprimer.css`

- [ ] **Step 1: Patterns** (each returns constrained main with heading + block), e.g. register:

```php
<?php
/**
 * Title: LMS Register
 * slug: burbankcert/lms-register
 * Categories: burbankcert
 */
?>
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">Create training account</h1>
	<!-- /wp:heading -->
	<!-- wp:burbank-lms/register /-->
</main>
<!-- /wp:group -->
```

Mirror for my-courses (“My courses” + `burbank-lms/my-courses`) and profile (“Profile” + `burbank-lms/profile`).

- [ ] **Step 2: Nav** — in `parts/header.html` and `parts/footer.html`, after Training link add:

```html
<!-- wp:navigation-link {"label":"Courses","url":"/bfc_course/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"My Courses","url":"/my-courses/","kind":"custom"} /-->
<!-- wp:navigation-link {"label":"Register","url":"/register/","kind":"custom"} /-->
```

Confirm actual `bfc_course` archive slug from CPT registration (`has_archive`); if rewrite slug differs (e.g. `courses`), use that URL. **Do not** change Volunteer Login hrefs.

- [ ] **Step 3: Remove theme PressPrimer integration** — delete CSS + `inc/pressprimer.php`; drop require from `functions.php`.

- [ ] **Step 4: Write `docs/lms-learner.md`** — create pages `/register/`, `/my-courses/`, `/profile/` once from patterns; plugin owns PP CSS; Volunteer Login is membership.

- [ ] **Step 5: Commit** `feat(theme): LMS patterns, nav, and drop PressPrimer CSS`

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| Single includes post content + featured image (plugin + theme) | 1, 10 |
| Course meta/status/quiz CTA chrome | 2, 3 |
| Catalog search/filter + chips | 4 |
| Register copy + form polish | 5 |
| My Courses cert + catalog CTA | 6 |
| Quiz chrome hooks + H1 demotion | 7 |
| PP CSS migration + broader enqueue | 8, 11 |
| Theme templates/patterns/nav; keep Volunteer Login | 10, 11 |
| Docs | 9, 11 |
| Plugin works without CERT theme | 1–8 (fallbacks + token CSS) |

## Execution order note

Ship **plugin PR** (Tasks 1–9) first and merge/deploy before theme PR (Tasks 10–11) so removing theme PP CSS never leaves the site unskinned.
