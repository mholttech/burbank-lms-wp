# Learner Course Pages Redesign (Phase 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild My Courses, Catalog, and Profile in the approved section/tile design language, and make WordPress first/last name the single source for the audited certificate (legal) name.

**Architecture:** Server-rendered section scaffolding in block `render.php` files; client rendering stays in block `view.js` files fed by the existing REST endpoints. Two new learner REST routes (`profile/legal-name`, `profile/sign-out-others`) sit beside the existing enrollment routes in `REST\Controller`. All styles use the existing `--bfc-*` tokens; no new endpoints for course data, no schema changes.

**Tech Stack:** PHP 8.2 / WordPress 7.1, Gutenberg server-rendered blocks (`src/Blocks/*`), vanilla JS view scripts, SCSS compiled by `@wordpress/scripts`, PHPUnit via wp-env (`npm run test:php`).

**Spec:** `docs/superpowers/specs/2026-10-31-learner-course-pages-redesign-design.md`

**Branch context:** Work continues on `fix/learner-light-mode-pill-contrast`. HEAD already contains interim card work (commits `28d77e5`–`9ba43ae`: `data-status` badges, meta line, Continue/View actions in `my-courses/view.js`; bordered card in its `style.scss`; `is-secondary` catalog CTA). This plan *replaces* that row layout with tiles — reuse its JS helpers (`statusLabel`, `formatDate`, `metaText`) and badge styles, do not resurrect the pre-card markup.

**Test commands used below**

- Full suite: `npm run test:php` (starts wp-env test env; stop later with `npm run env:test:stop`)
- Targeted: `npm run env:test:cli -- bash -lc 'cd tests && vendor/bin/phpunit --filter <TestName>'`
- Lint JS: `npx wp-scripts lint-js <file>` · Lint SCSS: `npx wp-scripts lint-style '<file>'` and `npm run lint:style` (resources only)
- Build: `npm run build` · PHP checks: `composer run phpcs` / `composer run phpstan` if configured, else `php -l <file>`

---

### Task 1: `Profile::set_legal_name_self_service()` (audited learner path)

**Files:**
- Modify: `src/Access/Profile.php`
- Test: `tests/php/AccessLearnerTest.php`

- [ ] **Step 1: Write the failing tests**

Add to `AccessLearnerTest` (keep the existing lock test untouched — `set_legal_name()` behavior does not change):

```php
/** Learner can change their own legal name via self-service; audit records self. */
public function test_learner_self_service_legal_name_change_audits(): void {
	$user_id = $this->learner_with_access( 'self-name@example.com' );
	$profile = new Profile();

	wp_set_current_user( $user_id );
	$result = $profile->set_legal_name_self_service( $user_id, 'Jamie A. Learner' );
	$this->assertTrue( $result );
	$this->assertSame( 'Jamie A. Learner', $profile->get_legal_name( $user_id ) );

	$audit   = get_user_meta( $user_id, 'burbank_lms_legal_name_audit', true );
	$entries = json_decode( (string) $audit, true );
	$last    = end( $entries );
	$this->assertSame( 'Jamie Learner', $last['from'] );
	$this->assertSame( 'Jamie A. Learner', $last['to'] );
	$this->assertSame( $user_id, (int) $last['by'] );

	// Another learner cannot use self-service on this user.
	$other_id = $this->learner_with_access( 'other-name@example.com' );
	wp_set_current_user( $other_id );
	$denied = $profile->set_legal_name_self_service( $user_id, 'Nope Name' );
	$this->assertWPError( $denied );
	$this->assertSame( 'Jamie A. Learner', $profile->get_legal_name( $user_id ) );

	// Unchanged name: success but no second audit entry.
	wp_set_current_user( $user_id );
	$this->assertTrue( $profile->set_legal_name_self_service( $user_id, 'Jamie A. Learner' ) );
	$entries_after = json_decode( (string) get_user_meta( $user_id, 'burbank_lms_legal_name_audit', true ), true );
	$this->assertCount( count( $entries ), $entries_after );
}
```

(If `learner_with_access()` sets a different initial legal name, align the `'from'` expectation with the helper — check the top of the file first and use whatever it sets.)

- [ ] **Step 2: Run to verify failure**

Run: `npm run env:test:cli -- bash -lc 'cd tests && vendor/bin/phpunit --filter test_learner_self_service_legal_name_change_audits'`
Expected: FAIL — `set_legal_name_self_service` does not exist.

- [ ] **Step 3: Implement in `src/Access/Profile.php`**

```php
/**
 * Set the legal/certificate name as the learner themself (audited).
 *
 * Self-service counterpart to admin correction: the current user must be
 * the target user. Appends an audit entry (by = the learner) when the
 * stored name changes. Direct usermeta writes stay blocked by the guards.
 *
 * @param int    $user_id User ID (must be the current user).
 * @param string $name    New legal name.
 */
public function set_legal_name_self_service( int $user_id, string $name ): bool|\WP_Error {
	if ( $user_id < 1 || get_current_user_id() !== $user_id ) {
		return new \WP_Error(
			'burbank_profile_legal_name_forbidden',
			__( 'You are not allowed to change this legal name.', 'burbank-lms' )
		);
	}
	$name = sanitize_text_field( $name );
	if ( '' === $name ) {
		return new \WP_Error( 'burbank_profile_legal_name_required', __( 'Legal name is required.', 'burbank-lms' ) );
	}
	$current = $this->get_legal_name( $user_id );
	if ( $current === $name ) {
		return true;
	}

	self::$allow_legal_name_write = true;
	try {
		update_user_meta( $user_id, self::META_LEGAL_NAME, $name );
		if ( '' !== $current ) {
			$this->append_audit( $user_id, $current, $name );
		}
	} finally {
		self::$allow_legal_name_write = false;
	}
	return true;
}
```

- [ ] **Step 4: Run test + full Access tests**

`--filter AccessLearnerTest` → PASS. Commit: `feat(access): audited self-service legal-name changes`.

---

### Task 2: REST routes `profile/legal-name` + `profile/sign-out-others`

**Files:**
- Modify: `src/REST/Controller.php`
- Test: `tests/php/LearnerRestTest.php`

- [ ] **Step 1: Write failing REST tests**

Follow the existing `LearnerRestTest` patterns for creating a learner + dispatching requests (copy its helper usage). Add:

```php
/** Legal-name route changes own name, rejects guests and empty names. */
public function test_legal_name_route_self_service(): void {
	// Arrange a granted learner (use the file's existing helper/factory pattern).
	$user_id = $this->make_learner( 'route-name@example.com' ); // adapt to file conventions
	wp_set_current_user( $user_id );

	$request = new WP_REST_Request( 'POST', '/burbank-lms/v1/profile/legal-name' );
	$request->set_body_params( array( 'legal_name' => 'Route Renamed' ) );
	$response = $this->server()->dispatch( $request ); // adapt to file conventions
	$this->assertSame( 200, $response->get_status() );
	$this->assertSame( 'Route Renamed', ( new Profile() )->get_legal_name( $user_id ) );

	$empty = new WP_REST_Request( 'POST', '/burbank-lms/v1/profile/legal-name' );
	$empty->set_body_params( array( 'legal_name' => '  ' ) );
	$this->assertSame( 400, $this->server()->dispatch( $empty )->get_status() );

	wp_set_current_user( 0 );
	$guest = new WP_REST_Request( 'POST', '/burbank-lms/v1/profile/legal-name' );
	$guest->set_body_params( array( 'legal_name' => 'Guest Name' ) );
	$this->assertSame( 401, $this->server()->dispatch( $guest )->get_status() );
}
```

Adapt helper names to the file's actual conventions (look at how `test_…_register…` builds its server/users — do not invent new helpers if equivalents exist; if the file dispatches via `rest_do_request()`, use that).

- [ ] **Step 2: Run to verify failure** — route not found (404).

- [ ] **Step 3: Register routes + callbacks in `src/REST/Controller.php`**

In `register_routes()`, after `/enrollments/admin`:

```php
register_rest_route(
	self::NAMESPACE,
	'/profile/legal-name',
	array(
		'methods'             => \WP_REST_Server::CREATABLE,
		'callback'            => array( $this, 'update_legal_name' ),
		'permission_callback' => array( $this, 'permission_profile' ),
		'args'                => array(
			'legal_name' => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
		),
	)
);

register_rest_route(
	self::NAMESPACE,
	'/profile/sign-out-others',
	array(
		'methods'             => \WP_REST_Server::CREATABLE,
		'callback'            => array( $this, 'sign_out_others' ),
		'permission_callback' => array( $this, 'permission_profile' ),
	)
);
```

Add `use Burbank_LMS\Access\Profile;` at the top. Callbacks:

```php
/**
 * Change the current learner's legal/certificate name (audited, self only).
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response|\WP_Error
 */
public function update_legal_name( \WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	$result  = ( new Profile() )->set_legal_name_self_service( $user_id, (string) $request->get_param( 'legal_name' ) );
	if ( is_wp_error( $result ) ) {
		return $this->to_rest_error( $result );
	}
	return new \WP_REST_Response(
		array( 'legal_name' => ( new Profile() )->get_legal_name( $user_id ) ),
		200
	);
}

/**
 * Destroy the current user's other sessions (wp-admin "Log Out Everywhere Else").
 *
 * @return \WP_REST_Response
 */
public function sign_out_others() {
	$user_id = get_current_user_id();
	$tokens  = \WP_Session_Tokens::get_instance( $user_id );
	$current = wp_get_session_token();
	if ( is_string( $current ) && '' !== $current ) {
		$tokens->destroy_others( $current );
	} else {
		$tokens->destroy_all();
	}
	return new \WP_REST_Response( array( 'signed_out_others' => true ), 200 );
}

/**
 * Profile routes require a logged-in learner who can_learn.
 *
 * @return true|\WP_Error
 */
public function permission_profile() {
	if ( ! is_user_logged_in() ) {
		return $this->unauthorized();
	}
	if ( ! $this->learner()->can_learn( get_current_user_id() ) ) {
		return new \WP_Error(
			'burbank_profile_cannot_learn',
			__( 'You must have training access to edit your profile.', 'burbank-lms' ),
			array( 'status' => 403 )
		);
	}
	return true;
}
```

Add a second REST test for sign-out-others: create two session tokens for the learner (via `WP_Session_Tokens::create( time() + HOUR_IN_SECONDS )` twice is fragile — instead assert the route returns 200 for a learner and 401 for a guest; token-destruction behavior is core's, already covered by WP core tests; do not re-test core).

- [ ] **Step 4: Run `--filter LearnerRestTest`** → PASS. Commit: `feat(rest): legal-name and sign-out-others profile routes`.

---

### Task 3: Registration takes first/last name

**Files:**
- Modify: `src/Access/Registration.php`, `src/REST/Controller.php` (register args), `src/Blocks/register/render.php`, `src/Blocks/register/view.js` (only if field names change the payload assembly — it posts `FormData` entries verbatim, so new input names flow automatically)
- Test: `tests/php/AccessRegistrationTest.php`

- [ ] **Step 1: Write failing tests**

In `AccessRegistrationTest` (follow its invite-arrangement pattern):

```php
/** First/last name composes the certificate name and WP name fields. */
public function test_register_first_last_composes_legal_name(): void {
	$result = $this->register_via_service( // adapt to the file's existing helper
		array(
			'first_name' => 'Alex',
			'last_name'  => 'Volunteer',
			// no legal_name
		)
	);
	$this->assertNotWPError( $result );
	$user_id = (int) $result;
	$this->assertSame( 'Alex Volunteer', ( new Profile() )->get_legal_name( $user_id ) );
	$this->assertSame( 'Alex', get_user_meta( $user_id, 'first_name', true ) );
	$this->assertSame( 'Volunteer', get_user_meta( $user_id, 'last_name', true ) );
}

/** Missing first or last name is rejected like missing legal name was. */
public function test_register_missing_last_name_fails(): void {
	$result = $this->register_via_service( array( 'first_name' => 'Alex', 'last_name' => '' ) );
	$this->assertWPError( $result );
	$this->assertSame( 'burbank_register_legal_name_required', $result->get_error_code() );
}
```

If the file calls `( new Registration() )->register( [...] )` directly with a full payload array, do that verbatim including its invite/email/password keys.

- [ ] **Step 2: Run to verify failure** — composed name empty / user uses legal_name only.

- [ ] **Step 3: Implement**

In `Registration::register()` docblock + body:

```php
$first_name = isset( $data['first_name'] ) ? sanitize_text_field( (string) $data['first_name'] ) : '';
$last_name  = isset( $data['last_name'] ) ? sanitize_text_field( (string) $data['last_name'] ) : '';
$legal_name = isset( $data['legal_name'] ) ? sanitize_text_field( (string) $data['legal_name'] ) : '';
if ( '' !== $first_name || '' !== $last_name ) {
	$legal_name = trim( $first_name . ' ' . $last_name );
}
```

Keep the existing `burbank_register_legal_name_required` check on the composed `$legal_name` (so a missing last name yields first-only… wait: `trim('Alex ')` is non-empty and would pass. Enforce both when either is present):

```php
if ( ( '' !== $first_name || '' !== $last_name ) && ( '' === $first_name || '' === $last_name ) ) {
	return new \WP_Error( 'burbank_register_legal_name_required', __( 'First and last name are required.', 'burbank-lms' ) );
}
```

In the `wp_insert_user()` array add `'first_name' => $first_name, 'last_name' => $last_name,` (empty strings are fine for the `legal_name`-only fallback). Update the `@param` docblock to include `first_name`/`last_name`.

In `Controller::register_routes()` register args add `first_name` and `last_name` (same shape as `legal_name`), and in `register()` pass `'first_name' => (string) $request->get_param( 'first_name' ), 'last_name' => (string) $request->get_param( 'last_name' ),`.

In `src/Blocks/register/render.php`, replace the legal-name field block with:

```php
<p class="bfc-register__field">
	<label for="bfc-register-first-name"><?php esc_html_e( 'First name', 'burbank-lms' ); ?></label>
	<input id="bfc-register-first-name" name="first_name" type="text" required autocomplete="given-name">
</p>
<p class="bfc-register__field">
	<label for="bfc-register-last-name"><?php esc_html_e( 'Last name', 'burbank-lms' ); ?></label>
	<input id="bfc-register-last-name" name="last_name" type="text" required autocomplete="family-name">
</p>
```

Add a hint under the last-name field: `<?php esc_html_e( 'Your name is printed on certificates as first + last name.', 'burbank-lms' ); ?>` (use the register block's existing hint markup/class if it has one; otherwise a plain `<span>` — check the file; profile uses `bfc-profile__hint`, register currently has no hints, so add class `bfc-register__hint` and leave styling to Task 6's register-adjacent CSS only if needed — default: no new CSS).

- [ ] **Step 4: Run `--filter AccessRegistrationTest` + `--filter LearnerRestTest`** → PASS (existing `legal_name`-only tests must still pass via the fallback). Commit: `feat(access): registration takes first/last name for certificate name`.

---

### Task 4: My Courses redesign (sections, stats, tiles)

**Files:**
- Modify: `src/Blocks/my-courses/render.php`, `src/Blocks/my-courses/view.js`, `src/Blocks/my-courses/style.scss`
- Test: `tests/php/MyCoursesBlockTest.php`

- [ ] **Step 1: Update the block test first**

Replace the catalog-CTA test's expectations (the `is-secondary` / `bfc-my-courses__catalog-cta` assertions from the interim work go away) and add scaffolding assertions:

```php
$this->assertStringContainsString( 'bfc-my-courses__stats', $html );
$this->assertStringContainsString( 'data-bfc-stat="enrolled"', $html );
$this->assertStringContainsString( 'data-bfc-stat="in-progress"', $html );
$this->assertStringContainsString( 'data-bfc-stat="completed"', $html );
$this->assertStringContainsString( 'Enrolled courses', $html );
$this->assertStringContainsString( 'Browse course catalog', $html );
$this->assertStringNotContainsString( 'bfc-my-courses__catalog-cta', $html );
```

In the certificate test add `$this->assertStringContainsString( 'Certificates', $html );` and `data-bfc-cert-valid`.

- [ ] **Step 2: Run to verify failure.**

- [ ] **Step 3: Rewrite `render.php` (non-gate branch)**

Replace everything from `$wrapper = …` down with (keep the gate + shell chrome above verbatim):

```php
$wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'bfc-my-courses',
		'data-bfc-rest-config' => Block_Context::rest_config_json(),
	)
);
$archive   = get_post_type_archive_link( 'bfc_course' );
$has_certs = shortcode_exists( 'ppcert_my_certificates' );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes(). ?>>
	<?php
	echo ( new Block_Title() )->render( __( 'My courses', 'burbank-lms' ), 'bfc-my-courses__heading' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Block_Title escapes.
	?>
	<ul class="bfc-my-courses__stats" data-bfc-stats hidden>
		<li class="bfc-my-courses__stat">
			<span class="bfc-my-courses__stat-value" data-bfc-stat="enrolled">–</span>
			<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'Enrolled', 'burbank-lms' ); ?></span>
		</li>
		<li class="bfc-my-courses__stat">
			<span class="bfc-my-courses__stat-value" data-bfc-stat="in-progress">–</span>
			<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'In progress', 'burbank-lms' ); ?></span>
		</li>
		<li class="bfc-my-courses__stat">
			<span class="bfc-my-courses__stat-value" data-bfc-stat="completed">–</span>
			<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'Completed', 'burbank-lms' ); ?></span>
		</li>
		<?php if ( $has_certs ) : ?>
			<li class="bfc-my-courses__stat">
				<span class="bfc-my-courses__stat-value" data-bfc-stat="certificates">–</span>
				<span class="bfc-my-courses__stat-label"><?php esc_html_e( 'Certificates', 'burbank-lms' ); ?></span>
			</li>
		<?php endif; ?>
	</ul>
	<div class="bfc-my-courses__section-head">
		<h2 class="bfc-my-courses__section-title"><?php esc_html_e( 'Enrolled courses', 'burbank-lms' ); ?></h2>
		<?php if ( is_string( $archive ) && $archive ) : ?>
			<a class="bfc-my-courses__catalog-link" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Browse course catalog', 'burbank-lms' ); ?> →</a>
		<?php endif; ?>
	</div>
	<p class="bfc-my-courses__loading" data-bfc-loading><?php esc_html_e( 'Loading your courses…', 'burbank-lms' ); ?></p>
	<ul class="bfc-my-courses__list" data-bfc-list hidden></ul>
	<div class="bfc-my-courses__empty" data-bfc-empty hidden>
		<p><?php esc_html_e( 'You are not enrolled in any courses yet.', 'burbank-lms' ); ?></p>
		<?php if ( is_string( $archive ) && $archive ) : ?>
			<a class="bfc-my-courses__action" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'Browse course catalog', 'burbank-lms' ); ?></a>
		<?php endif; ?>
	</div>
	<p class="bfc-my-courses__status" hidden data-bfc-status></p>
	<?php if ( $has_certs ) : ?>
		<div class="bfc-my-courses__certificates">
			<div class="bfc-my-courses__section-head">
				<h2 class="bfc-my-courses__section-title"><?php esc_html_e( 'Certificates', 'burbank-lms' ); ?></h2>
				<span class="bfc-my-courses__cert-valid" data-bfc-cert-valid hidden></span>
			</div>
			<?php echo do_shortcode( '[ppcert_my_certificates]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PressPrimer shortcode markup. ?>
		</div>
	<?php endif; ?>
</div>
```

Note the empty state changed from `<p>` to `<div>` (contains a link styled as an action); view.js only toggles `hidden`, so it works unchanged in that respect.

- [ ] **Step 4: Rewrite the tile rendering in `view.js`**

Keep `parseConfig`, `setStatus`, `statusLabel`, `formatDate`, `metaText`. Replace `render()` internals:

```js
const STATUS_ORDER = { active: 0, passed: 1, completed: 2, withdrawn: 3 };

const sortEnrollments = ( enrollments ) =>
	[ ...enrollments ].sort( ( a, b ) => {
		const rankA = STATUS_ORDER[ a.status ] ?? 0;
		const rankB = STATUS_ORDER[ b.status ] ?? 0;
		if ( rankA !== rankB ) {
			return rankA - rankB;
		}
		if ( a.status === 'completed' ) {
			return String( b.completed_at || '' ).localeCompare(
				String( a.completed_at || '' )
			);
		}
		return 0;
	} );

const setStat = ( name, value ) => {
	const el = root.querySelector( `[data-bfc-stat="${ name }"]` );
	if ( el ) {
		el.textContent = String( value );
	}
};

const renderStats = ( enrollments ) => {
	const stats = root.querySelector( '[data-bfc-stats]' );
	if ( ! stats ) {
		return;
	}
	const count = ( ...statuses ) =>
		enrollments.filter( ( item ) => statuses.includes( item.status ) )
			.length;
	setStat( 'enrolled', count( 'active', 'passed', 'completed' ) );
	setStat( 'in-progress', count( 'active', 'passed' ) );
	setStat( 'completed', count( 'completed' ) );

	const certRoot = root.querySelector( '.ppcert-my-certificates' );
	if ( certRoot ) {
		const items = certRoot.querySelectorAll(
			'.ppcert-my-certificates__item'
		);
		setStat( 'certificates', items.length );
		const valid = certRoot.querySelectorAll(
			'.ppcert-my-certificates__item .ppcert-pill--valid'
		).length;
		const validEl = root.querySelector( '[data-bfc-cert-valid]' );
		if ( validEl && items.length > 0 ) {
			validEl.textContent =
				valid === 1 ? '1 valid' : `${ valid } valid`;
			validEl.hidden = false;
		}
	}
	stats.hidden = false;
};
```

(The certificates stat element only exists when the shortcode exists; `setStat` no-ops otherwise. The stats strip unhides even with no PressPrimer root — the spec's certificates rule concerns only its own stat, which is absent from the DOM in that case.)

In `render()`, call `renderStats( enrollments )` first, iterate `sortEnrollments( enrollments )`, and build the tile structure:

```js
const li = document.createElement( 'li' );
li.className = 'bfc-my-courses__item';
li.dataset.courseId = String( item.course_id );
li.dataset.status = item.status || 'active';

const badge = document.createElement( 'span' );
badge.className = 'bfc-my-courses__badge';
badge.dataset.status = item.status || 'active';
badge.textContent = statusLabel( item.status );

const title = document.createElement( item.permalink ? 'a' : 'span' );
title.className = 'bfc-my-courses__title';
title.textContent = item.title || `Course #${ item.course_id }`;
if ( item.permalink ) {
	title.href = item.permalink;
}

li.append( badge, title );

const meta = metaText( item );
if ( meta ) {
	const metaEl = document.createElement( 'p' );
	metaEl.className = 'bfc-my-courses__meta';
	metaEl.textContent = meta;
	li.append( metaEl );
}

const footer = document.createElement( 'div' );
footer.className = 'bfc-my-courses__foot';
li.append( footer );
```

Footer contents: status text span + actions. Replace the previous `main`/`actions` construction:

```js
const footStatus = document.createElement( 'span' );
footStatus.className = 'bfc-my-courses__foot-status';
footStatus.textContent = statusLabel( item.status );
footer.append( footStatus );

let action = null;
if ( item.permalink ) {
	action = document.createElement( 'a' );
	action.className = 'bfc-my-courses__action';
	action.href = item.permalink;
	const inProgress =
		item.status === 'active' || item.status === 'passed';
	action.textContent = inProgress ? 'Continue course' : 'View course';
	action.classList.toggle( 'is-secondary', ! inProgress );
	footer.append( action );
}
```

Withdraw button: append into `footer` **before** the action link (footer is `space-between`: foot-status left group? No — withdraw must sit left with foot-status. Wrap left cluster). Change: create `left` div `bfc-my-courses__foot-left` containing `footStatus` + withdraw (when applicable); footer = left + action. On withdraw success (existing handler): badge/footStatus text → 'Withdrawn', `li.dataset.status = 'withdrawn'`, badge dataset too, action → 'View course' + `is-secondary`, withdraw removes itself. Keep the fetch logic from the current handler verbatim.

`metaText` edge: for `passed`, prepend nothing new — keep the current behavior (`Enrolled …`). Fine.

- [ ] **Step 5: Rewrite `style.scss`**

Keep the badge/action/title + scoped `.wp-site-blocks main` override blocks from the current file. Replace the item/list rules with the tile system and add stats/section/certificates rules. New/changed selectors (values from the approved mocks, all token-based):

```scss
&__stats {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 0.75rem;
	list-style: none;
	margin: 0 0 1.5rem;
	padding: 0;

	@media (max-width: 599px) {
		grid-template-columns: repeat(2, 1fr);
	}
}

&__stat {
	display: grid;
	gap: 0.2rem;
	padding: 0.85rem 1rem;
	border: 1px solid var(--bfc-control-border, #d0ddd4);
	border-radius: var(--bfc-radius, 12px);
	background: var(--bfc-control-bg, #f2f6f3);
}

&__stat-value {
	font-size: 1.35rem;
	font-weight: 800;
	line-height: 1.1;
}

&__stat-label {
	color: var(--bfc-control-muted, #556b60);
	font-size: 0.72rem;
	font-weight: 700;
	letter-spacing: 0.05em;
	text-transform: uppercase;
}

&__section-head {
	display: flex;
	flex-wrap: wrap;
	align-items: baseline;
	justify-content: space-between;
	gap: 0.5rem 1rem;
	margin: 1.5rem 0 0.75rem;
}

&__section-title {
	margin: 0;
	font-size: 1.05rem;
	font-weight: 800;
}

&__catalog-link {
	font-weight: 700;
}

&__list { /* replaces old list rule */
	list-style: none;
	margin: 0;
	padding: 0;
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(min(20rem, 100%), 1fr));
	gap: 0.9rem;
}

&__item { /* tile */
	display: grid;
	align-content: start;
	gap: 0.6rem;
	padding: clamp(1.125rem, 2.5vw, 1.5rem);
	border: 1px solid var(--bfc-control-border, #d0ddd4);
	border-radius: var(--bfc-radius-panel, 16px);
	background: var(--bfc-control-bg, #f2f6f3);
	color: var(--bfc-control-text, inherit);
	box-shadow: none;
}

&__item[data-status="withdrawn"] { title/meta opacity rule — keep existing }

&__foot {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 0.5rem 0.75rem;
	margin-top: 0.35rem;
	padding-top: 0.85rem;
	border-top: 1px solid var(--bfc-control-border, #d0ddd4);
}

&__foot-left {
	display: inline-flex;
	align-items: center;
	gap: 0.75rem;
}

&__foot-status {
	color: var(--bfc-control-muted, #556b60);
	font-size: 0.85rem;
	font-weight: 600;
}

&__empty { /* tile-styled empty state */
	padding: clamp(1.125rem, 2.5vw, 1.5rem);
	border: 1px dashed var(--bfc-control-border, #d0ddd4);
	border-radius: var(--bfc-radius-panel, 16px);
	background: var(--bfc-control-bg, #f2f6f3);

	p { margin: 0 0 0.85rem; }
}

&__certificates {
	margin-block-start: 2rem;

	.ppcert-my-certificates {
		max-width: none;
	}

	.ppcert-my-certificates__list {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(min(20rem, 100%), 1fr));
		gap: 0.9rem;
		list-style: none;
		margin: 0;
		padding: 0;
	}
}
```

Remove `__main`, `__actions`, the old `__item` flex rule, and `__catalog-cta` (CTA gone). Badge goes first in the tile now (its own row) — keep `align-self: start`-equivalent (`justify-self: start` in grid) so it doesn't stretch: add `justify-self: start;` to `&__badge`. Withdraw becomes a quiet text button inside the tile footer: restyle `&__withdraw` as borderless, transparent background, muted text, underline on hover (drop the pill-button treatment from the interim card; keep min-height for touch).

Certificates: confirm PressPrimer's root class/max-width against live markup (`preview_evaluate` getComputedStyle on `.ppcert-my-certificates`) before finalizing the `max-width` override; if PP centers with `margin-inline: auto`, also set `margin-inline: 0`. The `__list` is a `<ul>` in live markup (`ppcert-my-certificates__list` on it per inspection 2026-10-31); grid on it reflows items — their internal flex rows stay untouched.

- [ ] **Step 6: Verify**

`npx wp-scripts lint-style 'src/Blocks/my-courses/style.scss'`, `npx wp-scripts lint-js src/Blocks/my-courses/view.js`, `npm run build`, `--filter MyCoursesBlockTest` → PASS (also `--filter ProfileMyCoursesSoftGateTest` — gated output must contain none of the new classes; the gate returns before scaffolding, fine). Commit: `feat(blocks): my-courses sections, stats strip, and tile grid`.

---

### Task 5: Catalog tiles + enrolled Continue + archive H1 suppression

**Files:**
- Modify: `src/Blocks/catalog/render.php`, `src/Blocks/catalog/style.scss`, `src/Learner/Portal_Headings.php`, `src/Burbank_LMS.php`
- Test: `tests/php/CatalogBlockTest.php`, `tests/php/LearnerPortalHeadingsTest.php`

- [ ] **Step 1: Write failing tests**

Portal headings — new test (archive context needs a course; follow how other tests build `bfc_course` posts, or use `go_to( get_post_type_archive_link( 'bfc_course' ) )` after creating one):

```php
/** Theme query-title H1 is suppressed on the course archive only. */
public function test_suppresses_query_title_on_course_archive(): void {
	// create + publish a bfc_course (mirror CatalogBlockTest's course factory usage)
	$this->go_to( (string) get_post_type_archive_link( 'bfc_course' ) );

	$headings = new Portal_Headings();
	$block    = array( 'blockName' => 'core/query-title', 'attrs' => array() );
	$this->assertSame( '', $headings->suppress_archive_title( '<h1>Course catalog</h1>', $block ) );
	$this->assertSame( '<h1>Blog</h1>', $headings->suppress_archive_title( '<h1>Blog</h1>', $block, true ) ); // signature per implementation below
}
```

Prefer a signature that keeps the single-filter style: implement `suppress_archive_title( string $block_content, array $block ): string` that internally checks `is_post_type_archive( 'bfc_course' )`; the negative case runs after `$this->go_to( home_url( '/' ) )`. Write the test to that shape (no third param).

Catalog — extend `CatalogBlockTest` with render assertions for an enrolled learner (mirror its existing user/course setup):

```php
$this->assertStringContainsString( 'Course catalog', $html );
$this->assertStringContainsString( 'bfc-catalog__item', $html );
$this->assertStringContainsString( 'Continue course', $html ); // enrolled tile
$this->assertMatchesRegularExpression( '/\d+ courses? available/', $html );
```

- [ ] **Step 2: Run to verify failures.**

- [ ] **Step 3: `Portal_Headings` + loader**

Add to `Portal_Headings`:

```php
/**
 * Drop core/query-title output on the course archive (portal owns the H1).
 *
 * @param string               $block_content Block HTML.
 * @param array<string, mixed> $block         Parsed block.
 */
public function suppress_archive_title( string $block_content, array $block ): string {
	if ( '' === $block_content || is_admin() ) {
		return $block_content;
	}
	if ( ! is_post_type_archive( 'bfc_course' ) ) {
		return $block_content;
	}
	$name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
	return 'core/query-title' === $name ? '' : $block_content;
}
```

In `src/Burbank_LMS.php` next to the other two heading filters:

```php
$this->loader->add_filter( 'render_block_core/query-title', $portal_headings, 'suppress_archive_title', 10, 2 );
```

- [ ] **Step 4: Catalog `render.php` markup**

Add above the filters block (inside the non-empty branch):

```php
<div class="bfc-catalog__head">
	<h2 class="bfc-catalog__heading"><?php esc_html_e( 'Course catalog', 'burbank-lms' ); ?></h2>
	<span class="bfc-catalog__count">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: number of courses in the catalog. */
				_n( '%d course available', '%d courses available', count( $courses ), 'burbank-lms' ),
				count( $courses )
			)
		);
		?>
	</span>
</div>
```

Rework the `<li>` body (keep all `$` variables and state logic). Tile structure:

```php
<li
	class="bfc-catalog__item"
	data-course-id="…"
	data-bfc-title="…"
	data-bfc-code="…"
	data-bfc-subject="…"
>
	<?php if ( $subject ) : ?>
		<span class="bfc-catalog__badge"><?php echo esc_html( $presentation->label( $subject ) ); ?></span>
	<?php endif; ?>
	<a class="bfc-catalog__title" href="…">…</a>
	<div class="bfc-catalog__meta">
		<?php if ( $code ) : ?><span class="bfc-catalog__code"><?php echo esc_html( $code ); ?></span><?php endif; ?>
		<?php if ( $outcome ) : ?><span class="bfc-catalog__outcome"><?php echo esc_html( $presentation->label( $outcome ) ); ?></span><?php endif; ?>
	</div>
	<?php if ( $course->post_excerpt ) : ?> excerpt paragraph, unchanged <?php endif; ?>
	<div class="bfc-catalog__foot">
		<?php if ( $active ) : ?>
			<span class="bfc-catalog__chip bfc-catalog__chip--enrolled"><?php esc_html_e( 'Enrolled', 'burbank-lms' ); ?></span>
			<a class="bfc-catalog__cta bfc-catalog__cta--continue" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'Continue course', 'burbank-lms' ); ?></a>
		<?php elseif ( $can_learn && $restricted ) : ?>
			<span class="bfc-catalog__chip bfc-catalog__chip--invite"><?php esc_html_e( 'Invite required', 'burbank-lms' ); ?></span>
			<a class="bfc-catalog__cta wp-element-button is-secondary" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'View course', 'burbank-lms' ); ?></a>
		<?php elseif ( $can_learn ) : ?>
			<span class="bfc-catalog__open"><?php esc_html_e( 'Open enrollment', 'burbank-lms' ); ?></span>
			<button type="button" class="bfc-catalog__enroll wp-element-button" data-bfc-enroll="…"><?php esc_html_e( 'Enroll', 'burbank-lms' ); ?></button>
		<?php endif; ?>
	</div>
</li>
```

(Subject moves from the meta row to the badge; drop `bfc-catalog__subject-label` from meta.) `view.js` enroll handler: after success it replaces the button with an Enrolled chip — also swap in a Continue link so the tile matches the enrolled state:

```js
const wrap = button.closest( '.bfc-catalog__foot' );
const chip = document.createElement( 'span' );
chip.className = 'bfc-catalog__chip bfc-catalog__chip--enrolled';
chip.textContent = 'Enrolled';
button.replaceWith( chip );
// then, if the tile title link exists, add Continue after the chip:
```

Simpler and sufficient: keep `button.replaceWith( chip )` and append a Continue anchor built from the item's title-link `href` (`.bfc-catalog__title` in the same `li`). Implement exactly that; `is-secondary` handling for the invite View-course button is already painted by app.scss.

- [ ] **Step 5: Catalog `style.scss`**

- `&__list` → `grid-template-columns: repeat(auto-fill, minmax(min(20rem, 100%), 1fr)); gap: 0.9rem;`
- `&__item` → tile: `display:grid; align-content:start; gap:0.55rem; padding: clamp(1.125rem, 2.5vw, 1.5rem); box-shadow:none;` (remove the app-group shadow effect for catalog tiles too — `app.scss` still groups `.bfc-catalog__item` with the panel shadow; set `box-shadow: none` here with the higher-specificity block rule *and* remove `.bfc-catalog__item` from that app.scss group as done for my-courses in commit `96454b7`. Filters keep their panel card.)
- New: `&__head` (flex, baseline, space-between), `&__heading`, `&__count` (muted), `&__badge` (subject pill, green tint like `chip--enrolled` bg), `&__foot` (divider + flex space-between), `&__open` (muted small), `&__cta--continue` (green pill, mirror my-courses `__action` — copy that rule shape with catalog class names, including the `.wp-site-blocks main` scoped override needed for link paint: add catalog action selectors to the scoped pattern in catalog's own style or reuse `app.scss`'s CTA section by adding `.bfc-catalog a.bfc-catalog__cta--continue` rules there — choose catalog `style.scss` with a `.wp-site-blocks main` scoped block, same as my-courses).
- Title link color: catalog titles today inherit the theme paint problem — apply the same treatment as my-courses titles (`a.bfc-catalog__title` card-color, underline on hover, scoped override).

- [ ] **Step 6: Verify**

`--filter CatalogBlockTest`, `--filter LearnerPortalHeadingsTest`, lint style/js for catalog, `npm run build`. Commit: `feat(blocks): catalog tile grid, enrolled Continue, archive H1 suppression`.

---

### Task 6: Profile redesign + name simplification UI

**Files:**
- Modify: `src/Blocks/profile/render.php`, `src/Blocks/profile/view.js`, `src/Blocks/profile/style.scss`
- Test: `tests/php/ProfileMyCoursesSoftGateTest.php` (extend) or new assertions in the same file's learner case

- [ ] **Step 1: Write failing render assertions**

For a granted learner rendering `burbank-lms/profile` (follow the existing learner test setup):

```php
$this->assertStringContainsString( 'bfc-profile__summary', $html );
$this->assertStringContainsString( 'bfc-profile__avatar', $html );
$this->assertStringContainsString( 'Training access', $html );
$this->assertStringNotContainsString( 'Member since', $html );
$this->assertStringContainsString( 'name="first_name"', $html );
$this->assertStringContainsString( 'name="last_name"', $html );
$this->assertStringContainsString( 'bfc-profile__cert-preview', $html );
$this->assertStringContainsString( 'name="display_name"', $html ); // the <select>
$this->assertStringContainsString( 'bfc-profile__security-form', $html );
$this->assertStringNotContainsString( 'Contact a training administrator to correct your legal name.', $html );
$this->assertStringNotContainsString( 'readonly', $html );
```

Plus a prefill case: user with legal name `Mary Jane Watson` and empty first/last renders `value="Mary"` / `value="Jane Watson"`.

- [ ] **Step 2: Run to verify failure.**

- [ ] **Step 3: Rewrite `render.php`**

Server data at top (after gate + shell): `$user`, `$learner_state`, counts:

```php
$user        = wp_get_current_user();
$legal_name  = ( new Profile() )->get_legal_name( (int) $user->ID );
$first_name  = (string) $user->first_name;
$last_name   = (string) $user->last_name;
if ( '' === $first_name && '' === $last_name && '' !== $legal_name ) {
	$parts      = explode( ' ', $legal_name, 2 );
	$first_name = $parts[0];
	$last_name  = $parts[1] ?? '';
}
$learner     = new \Burbank_LMS\Access\Learner();
$can_learn   = $learner->can_learn( (int) $user->ID );
$access_text = $can_learn
	? __( 'Active', 'burbank-lms' )
	: ( $learner->is_suspended( (int) $user->ID ) ? __( 'Suspended', 'burbank-lms' ) : __( 'No access', 'burbank-lms' ) );
$enrollments = ( new \Burbank_LMS\Enrollment\Service() )->list_for_user( (int) $user->ID );
$enrolled_ct = 0;
foreach ( $enrollments as $row ) {
	if ( 'withdrawn' !== (string) $row->status ) {
		++$enrolled_ct;
	}
}
$display_options = array();
foreach ( array( $user->user_login, $first_name, $last_name, trim( $first_name . ' ' . $last_name ), trim( $last_name . ' ' . $first_name ), (string) $user->nickname ) as $candidate ) {
	$candidate = trim( (string) $candidate );
	if ( '' !== $candidate && ! in_array( $candidate, $display_options, true ) ) {
		$display_options[] = $candidate;
	}
}
if ( ! in_array( $user->display_name, $display_options, true ) ) {
	$display_options[] = $user->display_name;
}
```

Check `Learner::is_suspended` exists (it does — `src/Access/Learner.php:160`) and `Enrollment\Service::list_for_user` return shape (objects with `status` — same rows the REST controller uses).

Markup (abbreviated structure; write full PHP with escaping per existing conventions):

```php
<div class="bfc-profile__summary">
	<span class="bfc-profile__avatar"><?php echo get_avatar( (int) $user->ID, 96 ); // phpcs:ignore ... ?></span>
	<div class="bfc-profile__identity">
		<span class="bfc-profile__identity-name"><?php echo esc_html( $user->display_name ); ?></span>
		<span class="bfc-profile__identity-sub"><?php echo esc_html( $user->user_login ); ?> · <?php echo esc_html( $user->user_email ); ?></span>
	</div>
	<dl class="bfc-profile__facts">
		<div class="bfc-profile__fact">
			<dt><?php esc_html_e( 'Training access', 'burbank-lms' ); ?></dt>
			<dd><span class="bfc-profile__badge"><?php echo esc_html( $access_text ); ?></span></dd>
		</div>
		<div class="bfc-profile__fact">
			<dt><?php esc_html_e( 'Courses enrolled', 'burbank-lms' ); ?></dt>
			<dd><?php echo esc_html( (string) $enrolled_ct ); ?></dd>
		</div>
	</dl>
</div>

<h2 class="bfc-profile__section-title"><?php esc_html_e( 'Personal information', 'burbank-lms' ); ?></h2>
<form class="bfc-profile__form" data-bfc-legal-name="<?php echo esc_attr( $legal_name ); ?>" novalidate>
	… first_name / last_name inputs (required) …
	<p class="bfc-profile__cert-preview" data-bfc-cert-preview>
		<?php
		printf(
			/* translators: %s: name that will be printed on certificates. */
			esc_html__( 'On your certificates: %s', 'burbank-lms' ),
			'<strong data-bfc-cert-name>' . esc_html( trim( $first_name . ' ' . $last_name ) ?: $legal_name ) . '</strong>'
		);
		?>
		<span class="bfc-profile__hint"><?php esc_html_e( 'Certificates you\'ve already earned keep the name printed on them. Name changes are recorded.', 'burbank-lms' ); ?></span>
	</p>
	… nickname input; display_name <select> built from $display_options with selected = $user->display_name; email input; username read-only input …
	<p class="bfc-profile__actions"><button type="submit" class="wp-element-button"><?php esc_html_e( 'Save profile', 'burbank-lms' ); ?></button></p>
	<p class="bfc-profile__status" hidden data-bfc-status></p>
</form>

<h2 class="bfc-profile__section-title"><?php esc_html_e( 'Security', 'burbank-lms' ); ?></h2>
<form class="bfc-profile__security-form" novalidate>
	… password + password_confirm inputs; meter <div class="bfc-profile__meter" hidden><span data-bfc-meter-bar></span></div><span data-bfc-meter-label> …
	<p class="bfc-profile__actions">
		<button type="submit" class="wp-element-button"><?php esc_html_e( 'Change password', 'burbank-lms' ); ?></button>
		<button type="button" class="bfc-profile__signout-others" data-bfc-signout-others><?php esc_html_e( 'Sign out other sessions', 'burbank-lms' ); ?></button>
	</p>
	<p class="bfc-profile__status" hidden data-bfc-security-status></p>
</form>
```

Avatar escaping: `get_avatar()` returns HTML — output with the same phpcs-ignore pattern other shortcode/avatar outputs use in this codebase (search `get_avatar` first; if unused so far, use `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core avatar HTML.`).

Attempt `wp_enqueue_script( 'password-strength-meter' );` in the render (guard `function_exists` not needed; core registers it). If wp-env QA shows the handle missing on the front end, drop the enqueue and keep the graceful JS fallback (note the outcome in the commit message).

- [ ] **Step 4: Rewrite `view.js`**

Details form handler:

```js
const composedName = () =>
	[ form.first_name.value.trim(), form.last_name.value.trim() ]
		.filter( Boolean )
		.join( ' ' );
```

- Rebuild display-name options: on `input` of first/last/nickname, rebuild the select from `[username(read-only value), first, last, 'first last', 'last first', nickname]`, dedupe, keep current selection if still present, else select the composed "first last" if present. (Read username from the read-only input's value.)
- Live cert preview: `data-bfc-cert-name` textContent = composedName() (or the original legal name when both blank).
- Submit: validate first/last non-empty → else status error `First and last name are required.`; POST `config.userUrl` with `{ first_name, last_name, nickname, name: displaySelect.value, email }`; on success, if `composedName() !== form.dataset.bfcLegalName`, POST `${config.restUrl}/profile/legal-name` `{ legal_name: composedName() }`; status per the partial-failure matrix in the spec; update `form.dataset.bfcLegalName` on success.

Security form handler: both blank → status "Enter a new password first."; mismatch → "Passwords do not match."; else POST `config.userUrl` `{ password }`; clear fields; "Password changed.". Meter: on password input, if `window.wp?.passwordStrength` exists, `wp.passwordStrength.meter( pw, [], [ email ] )` (score 0–4; -1/5 = unknown → hide meter), width = `(score+1)/5*100%`, labels `['Very weak','Weak','Medium','Strong','Very strong']` (plain strings — this file currently uses untranslated literals everywhere; keep consistent). Sign-out button: POST `${config.restUrl}/profile/sign-out-others`, status "Other sessions signed out." / failure message.

Check `Block_Context::rest_config_json()` provides `userUrl` (it does — current profile view.js uses `config.userUrl`, and `restUrl` is used by sibling blocks; confirm the key exists in `Block_Context`, else use the same key my-courses uses).

- [ ] **Step 5: `style.scss`**

Rewrite around: `__summary` (flex card, border, no shadow), `__avatar img` (round, 64px), `__facts` (dl flex), `__section-title`, `__form`/`__security-form` (bordered panel, no panel shadow — override the app.scss-era `box-shadow` on `bfc-profile__form` by updating that rule in `resources/frontend/scss/app.scss` too, same treatment as my-courses), `__cert-preview` (green tint strip), field grid `1fr 1fr` collapsing at 599px, `__meter` bar, secondary button style for sign-out. Reuse token variables. Keep input styles from app.scss group (already covers `.bfc-profile` inputs/selects — the new `<select>` is covered by that group).

- [ ] **Step 6: Verify**

`--filter ProfileMyCoursesSoftGateTest` (gate must still show no form/scaffolding for guests — gate returns early, fine), lint, build. Commit: `feat(blocks): profile redesign with WP name fields and security form`.

---

### Task 7: Build, full tests, docs, QA preview

**Files:**
- Modify: `docs/learner-access.md` (my-courses/catalog/profile rows + legal-name rule), `AGENTS.md` + `CLAUDE.md` only if the Access quick-reference sentence about legal-name guards becomes wrong (it stays true — guards unchanged; the *lock* description in docs/learner-access.md is what changes)

- [ ] **Step 1: `npm run build`** — succeeds; `git status` shows no `build/` changes tracked (build is gitignored in this repo; if any built file *is* tracked, commit it).
- [ ] **Step 2: `npm run lint:style`, `npx wp-scripts lint-js` on the three view.js files, `php -l` on every touched PHP file, PHPCS/PHPStan via composer scripts; fix findings (existing codebase suppressions style, no new blanket ignores).**
- [ ] **Step 3: `npm run test:php`** — full suite PASS (228+ tests incl. new ones); then `npm run env:test:stop`.
- [ ] **Step 4: Update `docs/learner-access.md`**: my-courses row (sections/stats/tiles), catalog row (tiles + Continue + H1 note), profile (WP fields, display-name select, security form), legal-name paragraphs (first/last source, self-service audited, registration fields), REST route list (+2 routes).
- [ ] **Step 5: Live-preview QA via the preview browser on `cert.responseforge.app`** (test user): inject built `build/Blocks/*/style-index.css` as before and screenshot my-courses, catalog, profile in light; toggle `html[data-bc-theme="dark"]` for dark; resize to ~390px (`preview_resize`) and re-screenshot. Confirm: no duplicate H1 on /courses/ (cannot be verified by CSS injection — the query-title suppression is PHP; verify in wp-env instead by rendering the archive template there, and note it as deploy-pending for the live site).
- [ ] **Step 6: Final commits per task already made; push branch. Summarize deploy note: live site reflects this only after the plugin build from this branch is deployed (existing rollout path via the site repo / release zip, not from this branch).**

---

## Self-review notes (plan vs spec)

- Spec → tasks: My Courses sections/stats/tiles/order/certificates (T4), catalog tiles/Continue/H1 (T5), profile summary/two forms/display-name select/meter/sign-out (T6), legal-name backend + routes (T1–T2), registration first/last (T3), docs/QA (T7). Progress percentages: excluded everywhere (T4 stats are counts). Roadmap phases 2–4: intentionally no tasks — separate spec cycles per the spec's Roadmap.
- Known adaptations the implementer must make (marked inline): test helper names in `AccessLearnerTest::learner_with_access`, `LearnerRestTest`, `CatalogBlockTest`, `AccessRegistrationTest` (copy each file's existing conventions rather than the snippets' placeholder helper names); PressPrimer cert root max-width confirmation against live markup (T4 Step 5); `password-strength-meter` front-end availability (T6 Step 3).
