# Learner Portal UI Polish Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix learner portal contrast (light text on red CTAs), keep the LMS pill nav expanded on mobile, clear third-party float widgets with portal padding, and harden heading ownership (portal H1 + quiz title H1→H2).

**Architecture:** Plugin-only. CTA tokens and PressPrimer inverse text live in frontend SCSS; shell markup drops the mobile `<details>` duplicate; `Portal_Headings` also suppresses `core/post-title` on learner pages; `PressPrimer_Quiz::demote_quiz_title` matches `.ppq-quiz-title` more reliably. No domain/REST changes. Quiz Submit hang is out of scope.

**Tech Stack:** PHP 8.2+, WordPress 7.1.3+, SCSS via `@wordpress/scripts` (`burbank-lms-frontend` / `burbank-lms-pressprimer`), PHPUnit via `npm run test:php`.

**Spec:** `docs/superpowers/specs/2026-10-31-learner-portal-ui-polish-design.md`

## Global Constraints

- Namespace `Burbank_LMS`, text domain `burbank-lms`; register hooks only through `Loader` in `src/Burbank_LMS.php`.
- Do not hand-edit `build/`; run `npm run build` after SCSS changes.
- Do not edit theme or WP Accessibility / GTranslate plugins — only LMS safe-area padding.
- Keep `action-red` as PressPrimer primary; force inverse (white) text — do not remap to green.
- Scope CTA overrides under LMS / PressPrimer roots; leave theme footer “Join CERT” alone.
- Conventional Commits; do not hand-edit `PLUGIN_VERSION` / release markers.
- After each PHP task: `npm run test:php -- --filter <TestClass>`; `composer run phpstan` / `phpcs` on touched PHP when practical.

## File map

| Path | Responsibility |
| --- | --- |
| `src/Learner/Shell.php` | Always-expanded pill nav markup (no `bfc-shell__mobile`) |
| `resources/frontend/scss/app.scss` | CTA tokens, remove mobile collapse CSS, safe-area padding, filter wrap helpers |
| `resources/frontend/scss/pressprimer.scss` | Inverse text on primary CTAs + certificate filter pills |
| `src/Learner/Portal_Headings.php` | Suppress competing H1s including `core/post-title` |
| `src/Burbank_LMS.php` | Register `render_block_core/post-title` filter |
| `src/Integrations/PressPrimer_Quiz.php` | Harden `demote_quiz_title` |
| `tests/php/LearnerShellTest.php` | Assert no mobile details markup |
| `tests/php/LearnerPortalHeadingsTest.php` | `core/post-title` suppression |
| `tests/php/PressPrimerChromeTest.php` | Alternate demotion shapes |
| `docs/learner-access.md` | Nav always expanded; CTA inverse; heading ownership |

---

### Task 1: Shell always-expanded nav (TDD)

**Files:**
- Modify: `tests/php/LearnerShellTest.php`
- Modify: `src/Learner/Shell.php` (nav sprintf ~line 180–186)
- Modify: `resources/frontend/scss/app.scss` (`.bfc-shell` desktop/mobile swap)

- [ ] **Step 1: Write the failing test**

In `tests/php/LearnerShellTest.php`, add after `test_render_chrome_once_per_request`:

```php
/** Chrome always exposes the pill list; never a mobile details disclosure. */
public function test_render_chrome_has_no_mobile_disclosure(): void {
	$user_id = $this->verified_learner( 'shell-nav@example.com' );
	wp_set_current_user( $user_id );
	$shell = new Shell();

	$html = $shell->render_chrome(
		array(
			'current' => 'catalog',
			'force'   => true,
		)
	);

	$this->assertStringContainsString( 'bfc-shell__list', $html );
	$this->assertStringContainsString( 'Catalog', $html );
	$this->assertStringNotContainsString( 'bfc-shell__mobile', $html );
	$this->assertStringNotContainsString( '<details', $html );
	$this->assertStringNotContainsString( 'bfc-shell__desktop', $html );
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm run test:php -- --filter LearnerShellTest::test_render_chrome_has_no_mobile_disclosure`

Expected: FAIL — current markup still contains `bfc-shell__mobile` / `<details` / `bfc-shell__desktop`.

- [ ] **Step 3: Update Shell markup**

In `src/Learner/Shell.php`, replace the nav sprintf so it renders a single structure (drop `$aria` fourth Training summary arg usage for mobile). Change from:

```php
$nav = sprintf(
	'<nav class="bfc-shell" aria-label="%1$s"><div class="bfc-shell__desktop">%2$s%3$s</div><details class="bfc-shell__mobile"><summary>%4$s</summary>%2$s%3$s</details></nav>',
	$aria,
	$nav_list,
	$actions_html,
	esc_html__( 'Training', 'burbank-lms' )
);
```

to:

```php
$nav = sprintf(
	'<nav class="bfc-shell" aria-label="%1$s">%2$s%3$s</nav>',
	$aria,
	$nav_list,
	$actions_html
);
```

Keep `$nav_list` / `$actions_html` builders unchanged. Ensure `$actions_html` still renders when present (course contextual actions sit as siblings of the list inside `.bfc-shell`).

- [ ] **Step 4: Update `.bfc-shell` SCSS**

In `resources/frontend/scss/app.scss`, rewrite `.bfc-shell` so:

1. Root is a wrapping flex/grid that always shows the pill track + actions (no hide/show breakpoint).
2. Remove `&__desktop` / `&__mobile` rules and the `@media (max-width: 781px)` swap entirely.
3. Keep `&__list` pill-track styling, `&__link` min-height 44px, and `&__actions` / action link styles.

Suggested structure:

```scss
.bfc-shell {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 0.75rem 1rem;

	&__list,
	&__actions {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 0.35rem;
		list-style: none;
		margin: 0;
		padding: 0;
	}

	&__list {
		gap: 0.35rem;
		padding: 0.3rem;
		border: 0;
		border-radius: 20px;
		background: var(--bfc-pill-track);
	}

	/* keep &__item, &__link, is-current, &__action*, enroll/continue as today */
}
```

Preserve existing `&__link` / active / action styles when collapsing the desktop wrapper (copy those rules under the new root; do not leave orphan selectors that depended on `&__desktop`).

- [ ] **Step 5: Run tests to verify they pass**

Run: `npm run test:php -- --filter LearnerShellTest`

Expected: PASS (including new test and existing chrome/once/actions cases).

- [ ] **Step 6: Commit**

```bash
git add src/Learner/Shell.php resources/frontend/scss/app.scss tests/php/LearnerShellTest.php
git commit -m "$(cat <<'EOF'
fix(learner): keep training pill nav expanded on mobile

EOF
)"
```

---

### Task 2: CTA tokens + PressPrimer inverse text

**Files:**
- Modify: `resources/frontend/scss/app.scss` (`:root` + dark block)
- Modify: `resources/frontend/scss/pressprimer.scss`

- [ ] **Step 1: Add CTA tokens to `app.scss`**

Inside `:root` after existing `--bfc-shell-bg`:

```scss
	--bfc-cta-bg: var(--wp--preset--color--action-red, #c8102e);
	--bfc-cta-text: #fff;
```

Inside `html[data-bc-theme="dark"]` after `--bfc-shell-bg`:

```scss
	--bfc-cta-bg: var(--wp--preset--color--action-red, #c8102e);
	--bfc-cta-text: #fff;
```

- [ ] **Step 2: Map PressPrimer primary text to inverse**

In `resources/frontend/scss/pressprimer.scss`, after the existing `--ppq-primary*` assignments (~lines 17–21), ensure inverse is solid white and primary buttons cannot inherit dark link color:

```scss
	--ppq-primary: var(--bfc-cta-bg, var(--wp--preset--color--action-red));
	--ppq-primary-dark: var(--bfc-cta-bg, var(--wp--preset--color--action-red));
	--ppq-primary-hover: var(--bfc-cta-bg, var(--wp--preset--color--action-red));
	--ppq-text-inverse: var(--bfc-cta-text, #fff);
```

Do the same for certificate roots (`--ppcert-primary*`) using `--bfc-cta-bg` / keep secondary text vars as they are.

Add scoped overrides (still under `.wp-site-blocks main`) so primary buttons and active filter chips force inverse text:

```scss
.wp-site-blocks main :is(.ppq-quiz-landing, .ppq-quiz-interface, .ppq-results-container, .ppq-shell) {
	:is(.ppq-btn-primary, .ppq-button-primary, button.ppq-btn-primary, a.ppq-btn-primary) {
		background-color: var(--bfc-cta-bg, var(--wp--preset--color--action-red));
		color: var(--bfc-cta-text, #fff);

		&:hover,
		&:focus-visible {
			color: var(--bfc-cta-text, #fff);
		}
	}
}

.wp-site-blocks main :is(.ppcert-verify, .ppcert-view, .ppcert-my-certificates) {
	:is(.ppcert-btn-primary, .ppcert-button, a.ppcert-btn-primary, button.ppcert-btn-primary) {
		background-color: var(--bfc-cta-bg, var(--wp--preset--color--action-red));
		color: var(--bfc-cta-text, #fff);

		&:hover,
		&:focus-visible {
			color: var(--bfc-cta-text, #fff);
		}
	}

	/* Active / selected filter pills (Show / Sort) — force readable inverse text. */
	:is([aria-pressed="true"], .is-active, .ppcert-filter.is-active, .ppcert-chip.is-active) {
		background-color: var(--bfc-cta-bg, var(--wp--preset--color--action-red));
		color: var(--bfc-cta-text, #fff);
	}
}
```

If live markup uses different class names than `ppq-btn-primary` / `ppcert-btn-primary`, inspect built PressPrimer CSS or the live DOM once and adjust selectors to the real primary button classes — keep the same color contract (`--bfc-cta-text` on `--bfc-cta-bg`). Prefer also setting any `--ppq-*-on-primary` / button text CSS variables if present in PressPrimer’s root.

Also add wrap helpers for certificate filter rows if a stable wrapper exists (e.g. `.ppcert-my-certificates` filter bar):

```scss
.wp-site-blocks main .ppcert-my-certificates :is(.ppcert-filters, .ppcert-filter-bar, .ppcert-toolbar) {
	display: flex;
	flex-wrap: wrap;
	gap: 0.5rem 0.75rem;
	align-items: center;
}
```

Omit the wrap rule if no matching selector exists after a quick grep of PressPrimer front-end classes in the environment; the inverse-text rules remain required.

- [ ] **Step 3: Build assets**

Run: `npm run build`

Expected: `build/burbank-lms-frontend.css` and `build/burbank-lms-pressprimer.css` update; no webpack errors.

- [ ] **Step 4: Commit**

```bash
git add resources/frontend/scss/app.scss resources/frontend/scss/pressprimer.scss build/burbank-lms-frontend.css build/burbank-lms-pressprimer.css build/burbank-lms-frontend.asset.php build/burbank-lms-pressprimer.asset.php
git commit -m "$(cat <<'EOF'
fix(learner): force light text on PressPrimer red CTAs

EOF
)"
```

(Include whichever `build/*` asset files the build actually changes.)

---

### Task 3: Portal safe-area padding for third-party floats

**Files:**
- Modify: `resources/frontend/scss/app.scss` (`.bfc-portal` + narrow media query)

- [ ] **Step 1: Add safe padding**

Update `.bfc-portal` base padding and add a narrow-viewport block:

```scss
.bfc-portal {
	display: grid;
	gap: 1.25rem;
	max-width: var(--wp--style--global--wide-size, 1280px);
	margin: 0 0 1.5rem;
	padding: 1.5rem 1.25rem 1.25rem;
	/* Clear WP Accessibility left toolbar + GTranslate bottom float (approx.). */
	padding-left: max(1.25rem, env(safe-area-inset-left, 0px));
	padding-bottom: max(1.25rem, calc(env(safe-area-inset-bottom, 0px) + 0.5rem));
	border-radius: var(--bfc-radius-shell);
	background: var(--bfc-shell-bg);
	color: var(--bfc-control-text);

	@media (max-width: 781px) {
		padding-left: max(2.75rem, calc(env(safe-area-inset-left, 0px) + 2.5rem));
		padding-right: 1rem;
		padding-bottom: max(4.5rem, calc(env(safe-area-inset-bottom, 0px) + 4rem));
	}
}
```

Also give `.bfc-quiz-chrome` a matching bottom padding on narrow viewports so “Start quiz” / status is not covered by the EN float:

```scss
.bfc-quiz-chrome {
	/* existing rules… */

	@media (max-width: 781px) {
		padding-bottom: 3.5rem;
	}
}
```

- [ ] **Step 2: Build**

Run: `npm run build`

Expected: success; frontend CSS includes the new padding rules.

- [ ] **Step 3: Commit**

```bash
git add resources/frontend/scss/app.scss build/burbank-lms-frontend.css build/burbank-lms-frontend.asset.php
git commit -m "$(cat <<'EOF'
fix(learner): pad portal clear of a11y and language floats

EOF
)"
```

---

### Task 4: Portal_Headings suppress `core/post-title` (TDD)

**Files:**
- Modify: `tests/php/LearnerPortalHeadingsTest.php`
- Modify: `src/Learner/Portal_Headings.php`
- Modify: `src/Burbank_LMS.php` (Loader registration ~line 61)

- [ ] **Step 1: Write the failing test**

In `tests/php/LearnerPortalHeadingsTest.php`, add:

```php
/** core/post-title output is suppressed on my-courses so portal owns the H1. */
public function test_suppresses_my_courses_post_title(): void {
	$page_id = self::factory()->post->create(
		array(
			'post_type'   => 'page',
			'post_name'   => 'my-courses',
			'post_status' => 'publish',
			'post_title'  => 'My Courses',
		)
	);
	$this->go_to( get_permalink( $page_id ) );

	$headings = new Portal_Headings();
	$block    = array(
		'blockName' => 'core/post-title',
		'attrs'     => array(),
	);
	$this->assertSame( '', $headings->suppress_page_h1( '<h1 class="wp-block-post-title">My Courses</h1>', $block ) );
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `npm run test:php -- --filter LearnerPortalHeadingsTest::test_suppresses_my_courses_post_title`

Expected: FAIL — current `suppress_page_h1` returns the H1 unchanged because it only blanks `level === 1` heading attrs (post-title has no level / defaults to 2).

- [ ] **Step 3: Implement suppression for post-title**

Update `src/Learner/Portal_Headings.php`:

```php
/**
 * Drop competing page titles on profile and my-courses.
 *
 * Handles core/heading level 1 and core/post-title (always H1 in themes).
 *
 * @param string               $block_content Block HTML.
 * @param array<string, mixed> $block         Parsed block.
 */
public function suppress_page_h1( string $block_content, array $block ): string {
	if ( '' === $block_content || is_admin() ) {
		return $block_content;
	}
	if ( ! is_page( self::PAGE_SLUGS ) ) {
		return $block_content;
	}

	$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
	if ( 'core/post-title' === $name ) {
		return '';
	}
	if ( 'core/heading' === $name || '' === $name ) {
		$level = isset( $block['attrs']['level'] ) ? (int) $block['attrs']['level'] : 2;
		if ( 1 === $level ) {
			return '';
		}
	}

	return $block_content;
}
```

Note: existing tests pass `$block` without `blockName` for headings — keep the `'' === $name` path so level-1 still suppresses when callers omit `blockName` (matches current test fixtures).

- [ ] **Step 4: Register the post-title filter**

In `src/Burbank_LMS.php` near the existing heading filter:

```php
$portal_headings = new Learner\Portal_Headings();
$this->loader->add_filter( 'render_block_core/heading', $portal_headings, 'suppress_page_h1', 10, 2 );
$this->loader->add_filter( 'render_block_core/post-title', $portal_headings, 'suppress_page_h1', 10, 2 );
```

(Replace the previous one-liner `new Learner\Portal_Headings()` so both hooks share one instance.)

Do **not** suppress `core/post-title` on `bfc_course` singles — `is_page( PAGE_SLUGS )` already limits scope.

- [ ] **Step 5: Run tests**

Run: `npm run test:php -- --filter LearnerPortalHeadingsTest`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Learner/Portal_Headings.php src/Burbank_LMS.php tests/php/LearnerPortalHeadingsTest.php
git commit -m "$(cat <<'EOF'
fix(learner): suppress post-title H1 on profile and my-courses

EOF
)"
```

---

### Task 5: Harden quiz title demotion (TDD)

**Files:**
- Modify: `tests/php/PressPrimerChromeTest.php`
- Modify: `src/Integrations/PressPrimer_Quiz.php` (`demote_quiz_title`)

- [ ] **Step 1: Write the failing tests**

In `tests/php/PressPrimerChromeTest.php`, add after the existing demotion tests:

```php
/** Demotion still works when ppq-quiz-title shares the class attribute. */
public function test_demote_quiz_title_with_extra_classes(): void {
	$input  = '<h1 class="ppq-quiz-title ppq-heading">Safety Quiz</h1>';
	$output = ( new PressPrimer_Quiz() )->demote_quiz_title( $input );

	$this->assertSame( '<h2 class="ppq-quiz-title ppq-heading">Safety Quiz</h2>', $output );
}

/** Demotion accepts single-quoted class attributes. */
public function test_demote_quiz_title_single_quoted_class(): void {
	$input  = "<h1 class='ppq-quiz-title'>Safety Quiz</h1>";
	$output = ( new PressPrimer_Quiz() )->demote_quiz_title( $input );

	$this->assertSame( "<h2 class='ppq-quiz-title'>Safety Quiz</h2>", $output );
}
```

- [ ] **Step 2: Run tests — note which fail**

Run: `npm run test:php -- --filter PressPrimerChromeTest::test_demote_quiz_title`

Expected: `test_demote_quiz_title_with_extra_classes` may already PASS (current `preg_split` handles multi-class). `test_demote_quiz_title_single_quoted_class` may FAIL if the attribute regex is too strict — implement Step 3 to make both (and existing cases) pass with a clearer matcher.

- [ ] **Step 3: Harden `demote_quiz_title`**

Replace the callback body in `src/Integrations/PressPrimer_Quiz.php` with a class-token check that tolerates quote style and extra classes:

```php
public function demote_quiz_title( string $html ): string {
	return preg_replace_callback(
		'/<h1\b([^>]*)>(.*?)<\/h1>/is',
		static function ( array $heading ): string {
			$attrs = $heading[1];
			if ( ! preg_match( '/\bclass\s*=\s*(["\'])(.*?)\1/is', $attrs, $class ) ) {
				return $heading[0];
			}
			$tokens = preg_split( '/\s+/', trim( $class[2] ) );
			if ( ! is_array( $tokens ) || ! in_array( 'ppq-quiz-title', $tokens, true ) ) {
				return $heading[0];
			}
			return '<h2' . $attrs . '>' . $heading[2] . '</h2>';
		},
		$html
	) ?? $html;
}
```

(If live markup still shows an H1 after this, the title may be outside `render_block_pressprimer-quiz/quiz` HTML — verify the filter still fires; do not invent a second hook unless evidence shows the block name differs. Document any follow-up in the commit body if demotion cannot apply.)

- [ ] **Step 4: Run tests**

Run: `npm run test:php -- --filter PressPrimerChromeTest`

Expected: PASS for all demotion cases; other chrome tests unchanged.

- [ ] **Step 5: Commit**

```bash
git add src/Integrations/PressPrimer_Quiz.php tests/php/PressPrimerChromeTest.php
git commit -m "$(cat <<'EOF'
fix(learner): harden PressPrimer quiz title H1 demotion

EOF
)"
```

---

### Task 6: Docs + final verification

**Files:**
- Modify: `docs/learner-access.md` (shell / heading / CSS paragraphs ~lines 69–79)
- Optionally touch `AGENTS.md` Feature Quick Reference learner shell bullet if it still says “hybrid disclosure”

- [ ] **Step 1: Update `docs/learner-access.md`**

Replace the shell sentence that says desktop pill strip / mobile Training disclosure with always-expanded nav. Update heading ownership to mention `core/post-title` suppression. Add one sentence on CTA inverse text under Shared LMS CSS / PressPrimer.

Concrete replacements:

- Change: `Desktop uses the pill strip; mobile uses a **Training** \`<details>\` disclosure.`
  → `Pill-track nav (Catalog · My Courses · Profile) stays expanded at all breakpoints; course contextual actions wrap onto a second row when needed.`

- Change heading ownership bullet to note: `Learner\Portal_Headings` suppresses `core/heading` level-1 **and** `core/post-title` on `/profile/` and `/my-courses/`.

- In Shared LMS CSS / PressPrimer note: primary quiz/cert CTAs and active certificate filter pills use `--bfc-cta-text` (white) on `--bfc-cta-bg` (action-red). Portal adds narrow-viewport padding so content clears typical WP Accessibility / GTranslate floats.

- Change “Intentional differences… hybrid mobile disclosure” → remove hybrid disclosure; say nav remains expanded on mobile.

- [ ] **Step 2: Sync `AGENTS.md` if needed**

If the Learner shell bullet still describes mobile disclosure, update it to match always-expanded pills in one sentence.

- [ ] **Step 3: Full verification**

Run:

```bash
npm run test:php -- --filter 'LearnerShellTest|LearnerPortalHeadingsTest|PressPrimerChromeTest'
composer run phpstan
composer run phpcs -- src/Learner/Shell.php src/Learner/Portal_Headings.php src/Integrations/PressPrimer_Quiz.php src/Burbank_LMS.php
npm run build
```

Expected: tests pass; phpstan/phpcs clean on touched files; build succeeds.

- [ ] **Step 4: Commit**

```bash
git add docs/learner-access.md AGENTS.md
git commit -m "$(cat <<'EOF'
docs: note always-expanded shell nav and CTA contrast

EOF
)"
```

---

## Spec coverage checklist

| Spec requirement | Task |
| --- | --- |
| CTA tokens + inverse text on PPQ/PPCERT | Task 2 |
| Theme CTAs untouched | Task 2 (scoped selectors) |
| Always-expanded LMS menu | Task 1 |
| Certificate filter wrap + inverse active pills | Task 2 |
| Portal safe padding for floats | Task 3 |
| Suppress post-title / duplicate H1 | Task 4 |
| Harden quiz demotion | Task 5 |
| PHPUnit + docs | Tasks 1, 4, 5, 6 |
| Out of scope: quiz hang, theme header, plugin config | Not scheduled |

## Self-review notes

- No TBD/placeholder steps; SCSS primary button class names include a verify-against-live-DOM note if PressPrimer uses different BEM — contract stays white-on-red.
- Shell block uses `Shell::render_chrome()` only — no duplicate mobile markup path.
- Course `core/post-title` remains H1 (filter gated by `is_page`).
)
