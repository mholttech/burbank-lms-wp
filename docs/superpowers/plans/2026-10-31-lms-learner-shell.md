# LMS Learner Shell Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give authenticated learners with training access one cohesive training chrome (hybrid subnav + shared UI system) across catalog, course, my-courses, and profile, while soft-gating guests and no-access users off the catalog and keeping register/verify public and outside the shell.

**Architecture:** Plugin-owned `Learner\Soft_Gate` and `Learner\Shell` drive presentation. Soft gate replaces catalog/course (and blocked profile/my-courses) content when `! Learner::can_learn`. Shell prints a once-per-request hybrid subnav for `can_learn` users. Shared SCSS tokens in `resources/frontend/scss/` fix control visibility and unify cards/buttons. Optional `burbank-lms/shell` block and updated FSE templates compose the same APIs. Domain/REST access rules stay unchanged.

**Tech Stack:** WordPress 7.1.3+, PHP 8.2+, Gutenberg blocks (`@wordpress/scripts`), PHPUnit via `npm run test:php`, PHPStan, PHPCS, frontend SCSS via `burbank-lms-frontend` webpack entry.

**Spec:** `docs/superpowers/specs/2026-10-31-lms-learner-shell-design.md`

## Global Constraints

- Namespace `Burbank_LMS`, text domain `burbank-lms`; register hooks only through `Loader` in `src/Burbank_LMS.php`; no business logic in constructors; do not hand-edit `build/`.
- Soft gate and shell are presentation only; REST enroll still requires `can_learn`.
- Register and verify stay public and **must not** render the LMS subnav.
- Soft-gate views **must not** render the LMS subnav and **must not** leak course lists, filters, or enroll CTAs.
- CSS uses `--wp--preset--color-*` first, then optional `--bc-surface-*` / `html[data-bc-theme]`, with solid fallbacks.
- Keep full CERT site header/footer; LMS strip sits under the header.
- After each PHP task: `npm run test:php -- --filter <TestClass>`, `composer run phpstan`, `composer run phpcs` on touched files; `npm run build` when assets/blocks change.
- Conventional Commits; do not hand-edit `PLUGIN_VERSION` / release markers.

---

## File map

| Path | Responsibility |
| --- | --- |
| `src/Learner/Soft_Gate.php` | Audience detection + soft-gate HTML (guest vs logged-in-no-access) |
| `src/Learner/Shell.php` | Nav items, contextual course actions, once-per-request chrome HTML |
| `src/Blocks/catalog/render.php` | Soft gate early return; otherwise shell chrome + catalog |
| `src/Blocks/course/render.php` | Soft gate early return; otherwise shell chrome + course |
| `src/Blocks/profile/render.php` | Soft gate for guest/no-access; shell for can_learn; H2 demote |
| `src/Blocks/my-courses/render.php` | Soft gate for guest/no-access; shell for can_learn; H2 demote |
| `src/Blocks/register/render.php` + `style.scss` | Shared control styles only (no shell) |
| `src/Blocks/*/style.scss` | Consume shared LMS control/card tokens |
| `resources/frontend/scss/app.scss` | Shared LMS tokens + shell + soft-gate styles |
| `resources/frontend/js/app.js` | Mobile Training disclosure behavior |
| `src/Blocks/shell/*` | Optional shell block wrapping InnerBlocks using `Shell` |
| `src/Learner/Templates.php` | Archive/single include shell composition + single H1 |
| `tests/php/LearnerSoftGateTest.php` | Soft gate markup / audience |
| `tests/php/LearnerShellTest.php` | Nav builder + chrome once + contextual actions |
| `tests/php/CatalogBlockTest.php` / course/profile/my-courses tests | Gate + shell integration |
| `tests/php/BlockRegistrationTest.php` | Add `burbank-lms/shell` to expected list when block ships |
| `docs/learner-access.md`, `AGENTS.md` | Document shell + soft gate |

---

### Task 1: Soft_Gate helper

**Files:**
- Create: `src/Learner/Soft_Gate.php`
- Create: `tests/php/LearnerSoftGateTest.php`

**Interfaces:**
- Consumes: `Burbank_LMS\Access\Learner::can_learn( int $user_id ): bool`
- Produces:
  - `Soft_Gate::audience( int $user_id ): string` — `'learner' | 'guest' | 'no_access'`
  - `Soft_Gate::should_block( int $user_id ): bool` — true unless audience is `learner`
  - `Soft_Gate::render( string $audience ): string` — escaped HTML card (empty string if `learner`)

- [ ] **Step 1: Write the failing test**

```php
<?php
/**
 * Soft gate audience and markup.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Registration;
use Burbank_LMS\Access\Tables as AccessTables;
use Burbank_LMS\Learner\Soft_Gate;

class LearnerSoftGateTest extends WP_UnitTestCase {
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB
		delete_option( AccessTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB
		parent::tear_down();
	}

	private function learner_with_access(): int {
		$created = ( new Invites() )->create( 'site', array( 'label' => 'Batch', 'kind' => 'multi' ) );
		$this->assertIsArray( $created );
		$user_id = ( new Registration() )->register(
			array(
				'invite'       => $created['plaintext'],
				'email'        => 'softgate@example.com',
				'password'     => 'CorrectHorseBatteryStaple1!',
				'legal_name'   => 'Soft Gate',
				'display_name' => 'Soft',
			)
		);
		$this->assertIsInt( $user_id );
		return $user_id;
	}

	public function test_audience_guest_no_access_and_learner(): void {
		$gate = new Soft_Gate();
		$this->assertSame( 'guest', $gate->audience( 0 ) );
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->assertSame( 'no_access', $gate->audience( $user_id ) );
		$learner = $this->learner_with_access();
		$this->assertSame( 'learner', $gate->audience( $learner ) );
		$this->assertFalse( $gate->should_block( $learner ) );
		$this->assertTrue( $gate->should_block( 0 ) );
	}

	public function test_render_guest_and_no_access_differ_and_omit_course_list(): void {
		$gate  = new Soft_Gate();
		$guest = $gate->render( 'guest' );
		$blocked = $gate->render( 'no_access' );
		$this->assertStringContainsString( 'bfc-soft-gate', $guest );
		$this->assertStringContainsString( 'register', strtolower( $guest ) );
		$this->assertStringNotContainsString( 'bfc-catalog__list', $guest );
		$this->assertStringContainsString( 'bfc-soft-gate', $blocked );
		$this->assertStringContainsString( 'administrator', strtolower( $blocked ) );
		$this->assertNotSame( $guest, $blocked );
		$this->assertSame( '', $gate->render( 'learner' ) );
	}
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm run test:php -- --filter LearnerSoftGateTest`  
Expected: FAIL (class not found)

- [ ] **Step 3: Implement `Soft_Gate`**

```php
<?php
/**
 * Soft-gate presentation for blocked learner surfaces.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Learner;

use Burbank_LMS\Access\Learner;

/** Builds invite-only / no-access cards without leaking catalog content. */
class Soft_Gate {
	public const AUDIENCE_LEARNER   = 'learner';
	public const AUDIENCE_GUEST     = 'guest';
	public const AUDIENCE_NO_ACCESS = 'no_access';

	public function audience( int $user_id ): string {
		if ( $user_id < 1 ) {
			return self::AUDIENCE_GUEST;
		}
		if ( ( new Learner() )->can_learn( $user_id ) ) {
			return self::AUDIENCE_LEARNER;
		}
		return self::AUDIENCE_NO_ACCESS;
	}

	public function should_block( int $user_id ): bool {
		return self::AUDIENCE_LEARNER !== $this->audience( $user_id );
	}

	public function render( string $audience ): string {
		if ( self::AUDIENCE_LEARNER === $audience ) {
			return '';
		}
		if ( self::AUDIENCE_GUEST === $audience ) {
			$title = __( 'Training access required', 'burbank-lms' );
			$body  = __( 'Course training is invite-only. Log in with a training account or create one with a site invite code.', 'burbank-lms' );
			$login = wp_login_url( get_permalink() ?: home_url( '/' ) );
			$register = home_url( '/register/' );
			$actions = sprintf(
				'<p class="bfc-soft-gate__actions"><a class="wp-element-button" href="%1$s">%2$s</a> <a class="wp-element-button is-secondary" href="%3$s">%4$s</a></p>',
				esc_url( $login ),
				esc_html__( 'Log in', 'burbank-lms' ),
				esc_url( $register ),
				esc_html__( 'Register', 'burbank-lms' )
			);
		} else {
			$title = __( 'Training access required', 'burbank-lms' );
			$body  = __( 'Your account does not have training access. Contact a training administrator to be invited or reactivated.', 'burbank-lms' );
			$actions = '';
		}

		return sprintf(
			'<div class="bfc-soft-gate"><h2 class="bfc-soft-gate__title">%1$s</h2><p class="bfc-soft-gate__body">%2$s</p>%3$s</div>',
			esc_html( $title ),
			esc_html( $body ),
			$actions // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built with esc_url/esc_html above.
		);
	}
}
```

Use `is-secondary` only if theme supports it; otherwise a plain second link styled via `.bfc-soft-gate__actions a` in SCSS later is fine—keep the two CTAs for guests.

- [ ] **Step 4: Run tests + phpstan/phpcs on touched files — expect PASS**

- [ ] **Step 5: Commit**

```bash
git add src/Learner/Soft_Gate.php tests/php/LearnerSoftGateTest.php
git commit -m "$(cat <<'EOF'
feat(learner): add Soft_Gate helper for invite-only training surfaces

EOF
)"
```

---

### Task 2: Soft-gate catalog and course blocks

**Files:**
- Modify: `src/Blocks/catalog/render.php`
- Modify: `src/Blocks/course/render.php`
- Modify: `tests/php/CatalogBlockTest.php`
- Modify: `tests/php/CourseBlockRenderTest.php`

**Interfaces:**
- Consumes: `Soft_Gate::should_block`, `Soft_Gate::audience`, `Soft_Gate::render`
- Produces: blocked renders contain `bfc-soft-gate` and never `bfc-catalog__list` / enroll buttons

- [ ] **Step 1: Write the failing integration test**

Extend `CatalogBlockTest` / `CourseBlockRenderTest` using their existing `published_course()` SQL-bypass fixtures (do not invent a weaker factory-only publish). Prefer the same render invocation those tests already use (`include` of `render.php` or `do_blocks`).

```php
public function test_catalog_render_soft_gates_guest(): void {
	$this->published_course( 'Gated Course', 'BFC-901', 'communications' );
	wp_set_current_user( 0 );
	$block = do_blocks( '<!-- wp:burbank-lms/catalog /-->' );
	$this->assertStringContainsString( 'bfc-soft-gate', $block );
	$this->assertStringNotContainsString( 'bfc-catalog__list', $block );
	$this->assertStringNotContainsString( 'BFC-901', $block );
}
```

Add a sibling assertion for the course block with guest (and optionally subscriber without access).

- [ ] **Step 2: Run test — expect FAIL (catalog still lists courses for guests)**

- [ ] **Step 3: Gate at top of `catalog/render.php`**

After computing `$user_id`, before querying is optional—still OK to query but **do not output list** when blocked:

```php
$gate = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	printf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-catalog' ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$gate->render( $gate->audience( $user_id ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Soft_Gate escapes.
	);
	return;
}
```

Prefer skipping `Catalog::query()` when blocked for performance:

```php
$user_id = get_current_user_id();
$gate    = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	// soft gate return as above
	return;
}
$courses = ( new Catalog() )->query();
// ... existing render
```

For `course/render.php`, after validating the course post exists, if `$gate->should_block( $user_id )`, return wrapper + soft gate **without** meta/enroll/quiz chrome.

- [ ] **Step 4: Run tests + phpstan/phpcs — expect PASS**

- [ ] **Step 5: Commit**

```bash
git add src/Blocks/catalog/render.php src/Blocks/course/render.php tests/php/
git commit -m "$(cat <<'EOF'
feat(learner): soft-gate catalog and course for non-learners

EOF
)"
```

---

### Task 3: Soft-gate profile and my-courses for non-learners

**Files:**
- Modify: `src/Blocks/profile/render.php`
- Modify: `src/Blocks/my-courses/render.php`
- Test: `tests/php/LearnerSoftGateTest.php` or new `ProfileMyCoursesSoftGateTest.php`

**Interfaces:**
- Consumes: `Soft_Gate`
- Produces: guest / no_access see soft-gate card (distinct copy); can_learn unchanged forms/lists

- [ ] **Step 1: Failing tests** for guest profile/my-courses HTML containing `bfc-soft-gate` and guest CTAs; subscriber without access containing administrator messaging; learner still sees form/list markup.

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Replace plain “Sign in…” paragraphs with Soft_Gate**

```php
$user_id = get_current_user_id();
$gate    = new Soft_Gate();
if ( $gate->should_block( $user_id ) ) {
	printf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'bfc-profile' ) ), // phpcs:ignore ...
		$gate->render( $gate->audience( $user_id ) ) // phpcs:ignore ...
	);
	return;
}
```

Same pattern for my-courses with class `bfc-my-courses`.

- [ ] **Step 4: Run tests + phpstan/phpcs — PASS**

- [ ] **Step 5: Commit**

```bash
git commit -m "$(cat <<'EOF'
feat(learner): soft-gate profile and my-courses for non-learners

EOF
)"
```

---

### Task 4: Shell nav builder

**Files:**
- Create: `src/Learner/Shell.php`
- Create: `tests/php/LearnerShellTest.php`

**Interfaces:**
- Consumes: `Soft_Gate` / `Learner::can_learn`, `get_post_type_archive_link( 'bfc_course' )`, page URLs for my-courses/profile, enrollment status helpers
- Produces:
  - `Shell::should_show( int $user_id ): bool`
  - `Shell::nav_items( string $current = '' ): array<int, array{id:string,label:string,url:string,current:bool}>`
  - `Shell::contextual_actions( int $course_id, int $user_id ): array<int, array{id:string,label:string,url:string,kind:string}>`
  - `Shell::render_chrome( array $args = array() ): string` — empty unless should_show; includes nav + optional actions
  - `Shell::reset_chrome_printed(): void` — for tests
  - Internal once-per-request flag so multiple blocks do not duplicate chrome

Nav item ids: `catalog`, `my-courses`, `profile`.  
Contextual action kinds: `enroll` | `continue` | `withdraw` (URLs: course permalink with hash `#bfc-course-quiz` for continue; enroll may be `#bfc-course-enroll` or same page button target; withdraw links to my-courses with course query if no in-place withdraw—prefer course page enroll region anchors that already exist).

Resolve my-courses / profile URLs via `get_page_by_path( 'my-courses' )` / `profile` permalinks when pages exist; fall back to `home_url( '/my-courses/' )` and `home_url( '/profile/' )`.

- [ ] **Step 1: Write failing tests**

```php
public function test_nav_items_mark_current_catalog(): void {
	$shell = new \Burbank_LMS\Learner\Shell();
	$items = $shell->nav_items( 'catalog' );
	$ids   = wp_list_pluck( $items, 'id' );
	$this->assertSame( array( 'catalog', 'my-courses', 'profile' ), $ids );
	foreach ( $items as $item ) {
		if ( 'catalog' === $item['id'] ) {
			$this->assertTrue( $item['current'] );
		} else {
			$this->assertFalse( $item['current'] );
		}
	}
}

public function test_should_show_only_for_learners(): void {
	$shell = new \Burbank_LMS\Learner\Shell();
	$this->assertFalse( $shell->should_show( 0 ) );
}

public function test_render_chrome_once_per_request(): void {
	// Grant can_learn user, set current user, call render_chrome twice.
	// First non-empty with bfc-shell; second empty string.
}
```

Add contextual action test: enrolled with quiz → `continue`; not enrolled + can_learn → `enroll`.

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Implement `Shell`**

Key sketch:

```php
namespace Burbank_LMS\Learner;

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;

class Shell {
	private static bool $chrome_printed = false;

	public static function reset_chrome_printed(): void {
		self::$chrome_printed = false;
	}

	public function should_show( int $user_id ): bool {
		return $user_id > 0 && ( new Learner() )->can_learn( $user_id );
	}

	public function detect_current(): string {
		if ( is_post_type_archive( 'bfc_course' ) || is_singular( 'bfc_course' ) ) {
			return is_singular( 'bfc_course' ) ? 'course' : 'catalog';
		}
		if ( is_page( 'my-courses' ) ) {
			return 'my-courses';
		}
		if ( is_page( 'profile' ) ) {
			return 'profile';
		}
		return '';
	}

	/** @return array<int, array{id:string,label:string,url:string,current:bool}> */
	public function nav_items( string $current = '' ): array { /* ... */ }

	/** @return array<int, array{id:string,label:string,url:string,kind:string}> */
	public function contextual_actions( int $course_id, int $user_id ): array { /* ... */ }

	/**
	 * @param array{current?:string,course_id?:int,force?:bool} $args
	 */
	public function render_chrome( array $args = array() ): string {
		$user_id = get_current_user_id();
		if ( ! $this->should_show( $user_id ) ) {
			return '';
		}
		if ( self::$chrome_printed && empty( $args['force'] ) ) {
			return '';
		}
		self::$chrome_printed = true;
		$current = $args['current'] ?? $this->detect_current();
		$items   = $this->nav_items( 'course' === $current ? 'catalog' : $current );
		$actions = array();
		if ( ! empty( $args['course_id'] ) ) {
			$actions = $this->contextual_actions( (int) $args['course_id'], $user_id );
		} elseif ( 'course' === $current ) {
			$actions = $this->contextual_actions( (int) get_the_ID(), $user_id );
		}
		// Build <nav class="bfc-shell" aria-label="..."> with desktop list + mobile details/summary "Training".
		return $html;
	}
}
```

Mark catalog current when `$current` is `course` as well (learner is in catalog IA).

- [ ] **Step 4: Tests + phpstan/phpcs — PASS**

- [ ] **Step 5: Commit**

```bash
git commit -m "$(cat <<'EOF'
feat(learner): add Shell nav builder and once-per-request chrome

EOF
)"
```

---

### Task 5: Print shell chrome from learner blocks

**Files:**
- Modify: `src/Blocks/catalog/render.php`, `course/render.php`, `profile/render.php`, `my-courses/render.php`
- Extend: `tests/php/LearnerShellTest.php`

**Interfaces:**
- Consumes: `Shell::render_chrome`
- Produces: learner pages include exactly one `.bfc-shell` when can_learn

- [ ] **Step 1: Test** that after rendering catalog then course as learner, only one `bfc-shell` appears when concatenating outputs (reset flag in `set_up`).

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: After passing soft-gate check, echo chrome**

```php
$shell = new Shell();
$chrome = $shell->render_chrome(
	array(
		'current'   => 'catalog', // or course/profile/my-courses
		'course_id' => $course_id ?? 0,
	)
);
echo $chrome; // phpcs:ignore — Shell escapes.
```

Register block must **not** call Shell.

- [ ] **Step 4: PASS + commit**

```bash
git commit -m "$(cat <<'EOF'
feat(learner): render LMS shell chrome on learner blocks

EOF
)"
```

---

### Task 6: Shared LMS SCSS (tokens, shell, soft-gate, controls)

**Files:**
- Modify: `resources/frontend/scss/app.scss`
- Modify: `resources/frontend/js/app.js` (mobile disclosure if using `<details>` native, JS optional)
- Modify block `style.scss` files to rely on shared classes / nested selectors under `.bfc-catalog`, `.bfc-profile`, etc.

**Interfaces:**
- Produces: CSS variables and rules for `.bfc-shell`, `.bfc-soft-gate`, and shared control selectors

- [ ] **Step 1: Add shared tokens + control rules to `app.scss`**

```scss
:root {
	--bfc-control-bg: var(--bc-surface-card, var(--wp--preset--color--content-background, #fff));
	--bfc-control-text: var(--wp--preset--color--content-text, #1c1f23);
	--bfc-control-border: var(--wp--preset--color--content-border, #c3c4c7);
	--bfc-radius: 6px;
}

html[data-bc-theme="dark"] {
	--bfc-control-bg: var(--bc-surface-card, #292c2a);
	--bfc-control-text: var(--wp--preset--color--content-text, #f6f8fc);
	--bfc-control-border: var(--wp--preset--color--content-border, #414b45);
}

.bfc-catalog,
.bfc-course,
.bfc-profile,
.bfc-register,
.bfc-my-courses,
.bfc-soft-gate,
.bfc-shell {
	input[type="text"],
	input[type="email"],
	input[type="password"],
	input[type="search"],
	select,
	textarea {
		box-sizing: border-box;
		width: 100%;
		min-height: 46px;
		padding: 0.5rem 0.75rem;
		border: 1px solid var(--bfc-control-border);
		border-radius: var(--bfc-radius);
		background: var(--bfc-control-bg);
		color: var(--bfc-control-text);
	}
}

.bfc-shell {
	display: flex;
	flex-wrap: wrap;
	gap: 0.75rem 1.25rem;
	align-items: center;
	justify-content: space-between;
	margin: 0 0 1.25rem;
	padding: 0.75rem 0;
	border-bottom: 1px solid var(--bfc-control-border);

	&__list {
		display: flex;
		flex-wrap: wrap;
		gap: 0.75rem 1rem;
		list-style: none;
		margin: 0;
		padding: 0;
	}

	&__link.is-current {
		font-weight: 600;
		text-decoration: underline;
	}

	&__mobile {
		display: none;
	}

	@media (max-width: 781px) {
		&__desktop {
			display: none;
		}
		&__mobile {
			display: block;
			width: 100%;
		}
	}
}

.bfc-soft-gate {
	display: grid;
	gap: 0.75rem;
	max-width: 36rem;
	padding: clamp(1rem, 2vw, 1.5rem);
	border: 1px solid var(--bfc-control-border);
	border-radius: var(--bfc-radius);
	background: var(--bfc-control-bg);
}
```

Ensure `burbank-lms-frontend` is enqueued on learner fronts (confirm existing enqueue in `Burbank_LMS` / assets class; wire if missing on these templates).

- [ ] **Step 2: `npm run build` and spot-check compiled CSS contains `.bfc-shell`**

- [ ] **Step 3: Trim redundant min-height-only rules in profile/register SCSS so shared rules win; keep BEM wrappers**

- [ ] **Step 4: Commit**

```bash
git commit -m "$(cat <<'EOF'
feat(learner): add shared LMS shell, gate, and control styles

EOF
)"
```

---

### Task 7: Title demotion (single H1)

**Files:**
- Modify: `src/Blocks/profile/render.php`, `my-courses/render.php`, `register/render.php` if they output H1
- Modify: `src/Learner/Templates.php` as needed so archive keeps one H1
- Test: assert render output does not contain two `<h1` for profile/my-courses when wrapped

Inspect current markup: pages already have theme/page H1 from title; blocks should use `<h2 class="…">` for “Profile” / “My courses” / “Create training account” **or** omit the inner title when a page title exists. Prefer demoting block headings to H2.

- [ ] **Step 1: Failing test or manual assertion in render unit test for heading level**
- [ ] **Step 2: Implement demotion**
- [ ] **Step 3: Commit**

```bash
git commit -m "$(cat <<'EOF'
fix(learner): demote block titles to avoid duplicate H1s

EOF
)"
```

---

### Task 8: Optional shell block + FSE templates

**Files:**
- Create: `src/Blocks/shell/` via `npm run create-block shell` (follow existing block conventions: `block.json`, `render.php`, `style.scss`, `edit.js`)
- Modify: `src/Learner/Templates.php`
- Modify: `tests/php/BlockRegistrationTest.php` — add `'burbank-lms/shell'` to `EXPECTED_LEARNER_BLOCKS`
- Modify: `tests/php/LearnerTemplatesTest.php`

**Interfaces:**
- Shell block `render.php` calls `Shell::render_chrome` then `InnerBlocks` content (`$content`)
- Templates: wrap heading + catalog/course in shell block markup if using block template syntax; **or** keep PHP chrome injection and only add shell block for editor composition—prefer templates that still work if chrome already printed (Shell once-flag prevents duplicates)

Archive template sketch:

```php
return '<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">' . esc_html__( 'Course catalog', 'burbank-lms' ) . '</h1>
	<!-- /wp:heading -->
	<!-- wp:burbank-lms/catalog /-->
</div>
<!-- /wp:group -->';
```

Keep chrome injection from Task 5 as the reliable path; shell block is for Site Editor composition and must call the same `Shell` API without double-printing.

- [ ] **Steps: create-block → implement render → update expected blocks list → build → test → commit**

```bash
git commit -m "$(cat <<'EOF'
feat(learner): add shell block and align course FSE templates

EOF
)"
```

If create-block is heavy, defer the block and document chrome-injection-only in AGENTS.md—but the spec prefers a shell block; implement it unless blocked by tooling.

---

### Task 9: Docs

**Files:**
- Modify: `docs/learner-access.md`
- Modify: `AGENTS.md` (Feature Quick Reference — shell + soft gate)

- [ ] **Step 1: Document soft gate audiences, shell nav, register/verify exclusion, CSS entry**
- [ ] **Step 2: Commit**

```bash
git commit -m "$(cat <<'EOF'
docs: document LMS learner shell and soft gates

EOF
)"
```

---

### Task 10: Verification sweep

- [ ] **Step 1: `npm run build && npm run test:php && composer run phpstan && composer run phpcs`**
- [ ] **Step 2: Manual browser (cert.responseforge.app or wp-env):** light/dark on catalog, course, my-courses, profile, register, verify; guest catalog soft gate; learner shell once; mobile Training disclosure
- [ ] **Step 3: Fix any residuals; commit as `fix(learner): …` if needed**

---

## Spec coverage self-check

| Spec requirement | Task |
| --- | --- |
| Hybrid subnav desktop/mobile | 4, 5, 6 |
| Shell only for can_learn | 4, 5 |
| Guests cannot browse catalog | 1, 2 |
| Distinct soft-gate copy guest vs no_access | 1, 2, 3 |
| Register/verify outside shell | 5 (explicit non-call), 6 (styles only) |
| Full site header/footer kept | N/A (no theme chrome removal) |
| Contextual course actions | 4, 5 |
| Shared control borders light/dark | 6 |
| Single H1 | 7 |
| Plugin ownership | all |
| PHPUnit soft gate + shell | 1–5 |
| Manual light/dark | 10 |
| Docs | 9 |

## Placeholder / consistency scan

- Soft_Gate audience strings: `learner` | `guest` | `no_access` throughout.
- Shell nav ids: `catalog` | `my-courses` | `profile`; current `course` maps catalog highlight.
- CSS prefixes: `bfc-shell`, `bfc-soft-gate`, existing `bfc-*` blocks.
