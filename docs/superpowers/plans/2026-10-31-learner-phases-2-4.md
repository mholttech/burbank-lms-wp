# Learner UI Phases 2–4 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship Phases 2–4 into PR 34: email verification with shell+verify soft-gate, course/enroll/quiz/cert UI in Phase 1 tile language, React Training admin + sidebar kit, and one branded mail template.

**Architecture:** Verification is an extra learning gate on top of `can_learn` (granted at register). Guest/`!can_learn` soft-gates stay shell-less; unverified `can_learn` users get Shell then a verify panel instead of body content. Enroll/quiz require verified. Course/enroll blocks match catalog/my-courses tiles. Admin: React wp-admin pages for site invites + learners; Course sidebar shares the kit. Mailer wraps all transactional messages in one HTML + text template.

**Tech Stack:** PHP 8.2 / WordPress, Gutenberg blocks, vanilla block `view.js`, `@wordpress/scripts` React admin (existing course-editor pattern), PHPUnit via `npm run test:php`.

**Spec:** `docs/superpowers/specs/2026-10-31-learner-phases-2-4-design.md`  
**Branch:** `feat/learner-phases-2-4` (stacked PR → PR 34 → `main`)  
**Phase 1:** Lives on PR 34 (`fix/learner-light-mode-pill-contrast`) — do not regress; rebase/retarget when 34 merges.

**Test commands**

- Full: `npm run test:php`
- Targeted: `npm run env:test:cli -- bash -lc 'cd tests && vendor/bin/phpunit --filter <Name>'`
- Build: `npm run build` · Lint: `npx wp-scripts lint-js <file>` · `npm run lint:style`

**File map (responsibilities)**

| Path | Role |
| --- | --- |
| `src/Access/Learner.php` | `META_EMAIL_VERIFIED`, `is_email_verified`, `mark_email_verified`, `needs_email_verification`; quiz cap respects verified |
| `src/Access/Email_Verification.php` (new) | Token create/consume, resend throttle helpers |
| `src/Access/Registration.php` | After grant: mark unverified + send verify mail |
| `src/Learner/Soft_Gate.php` | `AUDIENCE_UNVERIFIED` + `render_verify()`; `should_block` stays guest/no_access only |
| `src/Learner/Shell.php` | Hide/repurpose enroll/quiz contextual actions when unverified |
| `src/Blocks/{catalog,course,profile,my-courses}/render.php` | After shell: if unverified → verify panel; else content |
| `src/Blocks/course/*` | Stack tile redesign |
| `src/Blocks/enroll/*` | State-aware mini-card |
| `src/Integrations/PressPrimer_Quiz.php` | Require verified in can_start / before_submit |
| `src/REST/Controller.php` | Verify consume, resend, change-email (re-verify) |
| `src/Mail/Template.php` (new) + `Mailer.php` | Branded HTML + text wrap; verify/resend sends |
| `src/Access/Admin.php` + `resources/admin/js/training/*` (new) | React Site invites + Learners pages |
| `resources/admin/js/course-editor/*` + scss | Sidebar kit restyle |
| `docs/learner-access.md` + `AGENTS.md` | Document verify + React admin |

---

### Task 1: Email verification domain (`Learner` + `Email_Verification`)

**Files:**
- Create: `src/Access/Email_Verification.php`
- Modify: `src/Access/Learner.php`
- Test: `tests/php/AccessLearnerTest.php` (or new `tests/php/EmailVerificationTest.php`)

- [ ] **Step 1: Write failing tests**

```php
public function test_new_learner_needs_email_verification_until_marked(): void {
	$user_id = $this->learner_with_access( 'verify@example.com' );
	$learner = new Learner();
	// After Task 2 registration will leave unverified; for this unit test:
	delete_user_meta( $user_id, Learner::META_EMAIL_VERIFIED );
	$this->assertFalse( $learner->is_email_verified( $user_id ) );
	$this->assertTrue( $learner->needs_email_verification( $user_id ) );
	$learner->mark_email_verified( $user_id );
	$this->assertTrue( $learner->is_email_verified( $user_id ) );
	$this->assertFalse( $learner->needs_email_verification( $user_id ) );
}

public function test_email_verification_token_round_trip(): void {
	$user_id = $this->learner_with_access( 'token@example.com' );
	$svc     = new Email_Verification();
	$token   = $svc->issue_token( $user_id );
	$this->assertNotSame( '', $token );
	$this->assertSame( $user_id, $svc->consume_token( $token ) );
	$this->assertTrue( ( new Learner() )->is_email_verified( $user_id ) );
	$this->assertWPError( $svc->consume_token( $token ) ); // one-time
}
```

Adapt helpers to the file. Grandfather existing test users: `is_email_verified` returns true when meta is missing **only if** you choose grandfathering — **spec: new registers are unverified; for tests that use `learner_with_access`, set verified meta `1` in the helper** so Phase 1 tests keep passing.

- [ ] **Step 2: Run — expect FAIL** (missing methods / class).

- [ ] **Step 3: Implement**

In `Learner.php`:

```php
public const META_EMAIL_VERIFIED = 'burbank_lms_email_verified';

public function is_email_verified( int $user_id ): bool {
	return '1' === (string) get_user_meta( $user_id, self::META_EMAIL_VERIFIED, true );
}

public function mark_email_verified( int $user_id ): void {
	if ( $user_id < 1 ) {
		return;
	}
	update_user_meta( $user_id, self::META_EMAIL_VERIFIED, '1' );
}

/** can_learn but not yet verified — shell OK, learning gated. */
public function needs_email_verification( int $user_id ): bool {
	return $this->can_learn( $user_id ) && ! $this->is_email_verified( $user_id );
}
```

Update `learner_with_access` / factories used by Access/LearnerRest/etc. to `update_user_meta( $id, Learner::META_EMAIL_VERIFIED, '1' )`.

`Email_Verification.php`: store hashed token + expiry in usermeta (`burbank_lms_email_verify_token`, `burbank_lms_email_verify_expires`); `issue_token` returns plaintext once; `consume_token` validates hash+expiry, marks verified, clears token; return `WP_Error` on failure.

Update `filter_quiz_cap` / `sync_quiz_cap` so quiz cap requires `can_learn && is_email_verified`.

- [ ] **Step 4: Tests PASS. Commit:** `feat(access): email verification domain and tokens`

---

### Task 2: Registration sends verify + Mailer verify/resend stubs

**Files:**
- Modify: `src/Access/Registration.php`, `src/Mail/Mailer.php`
- Test: `tests/php/AccessRegistrationTest.php`, `tests/php/MailerTest.php`

- [ ] **Step 1: Failing tests** — after successful register without grandfathering: `needs_email_verification` true; Mailer `send_verification_email` invoked (action spy or mock). Existing legal_name-only admin-created users in other tests stay verified via helper.

- [ ] **Step 2: Run — FAIL.**

- [ ] **Step 3: Implement** — On successful register after `grant_access`: set `META_EMAIL_VERIFIED` to `0` / delete so unverified; `Email_Verification::issue_token`; `Mailer::send_verification_email( $user_id, $plaintext_token )` (plain message OK until Task 8 template). Welcome email still sends.

- [ ] **Step 4: PASS. Commit:** `feat(access): send verification email on registration`

---

### Task 3: Soft_Gate verify panel + block wiring (shell stays)

**Files:**
- Modify: `src/Learner/Soft_Gate.php`, `src/Learner/Shell.php`
- Modify: `src/Blocks/catalog/render.php`, `course/render.php`, `profile/render.php`, `my-courses/render.php`
- Test: `tests/php/LearnerSoftGateTest.php`, `tests/php/LearnerShellTest.php`, `tests/php/ProfileMyCoursesSoftGateTest.php`

**Critical behavior:** `should_block()` remains **only** guest / no_access (no shell). Unverified learners: render **Shell first**, then `Soft_Gate::render_verify( $user_id )` instead of block body.

- [ ] **Step 1: Failing tests**

```php
public function test_unverified_learner_gets_verify_markup_not_guest_gate(): void {
	$user_id = /* can_learn, META_EMAIL_VERIFIED absent/0 */;
	$gate    = new Soft_Gate();
	$this->assertFalse( $gate->should_block( $user_id ) );
	$html = $gate->render_verify( $user_id );
	$this->assertStringContainsString( 'Verify your email', $html );
	$this->assertStringContainsString( 'data-bfc-resend-verify', $html );
}

public function test_shell_hides_enroll_quiz_actions_when_unverified(): void {
	// create course context; assert chrome lacks #bfc-course-enroll / quiz continue when needs_email_verification
}
```

- [ ] **Step 2: Run — FAIL.**

- [ ] **Step 3: Implement `render_verify`** — Training caption, H1 Verify your email, body with email, Resend button (`data-bfc-resend-verify`), optional Change email control. Shared small `view.js` or inline each block’s existing view to POST resend (Task 4 wires REST).

Block pattern:

```php
if ( $gate->should_block( $user_id ) ) {
	// existing: wrapper + gate only, return
}
echo $shell->render_chrome( ... );
if ( ( new Learner() )->needs_email_verification( $user_id ) ) {
	echo $gate->render_verify( $user_id );
	return;
}
// existing content
```

Shell: if `needs_email_verification`, do not print Enroll / Continue-quiz contextual actions (or link them to profile/verify CTA).

- [ ] **Step 4: PASS. Commit:** `feat(learner): verify soft-gate with shell for unverified`

---

### Task 4: REST verify consume + resend (+ optional change-email)

**Files:**
- Modify: `src/REST/Controller.php`
- Create public verify landing: prefer `template_redirect` handler in `Email_Verification` or thin `src/Access/Verify_Landing.php` for `?burbank_lms_verify=TOKEN` → consume → redirect with query flag
- Test: `tests/php/LearnerRestTest.php`

- [ ] **Step 1: Failing tests** — `POST /profile/resend-verification` 200 for unverified learner / 401 guest / 400 if already verified; resend rate-limit error code. Landing consume marks verified.

- [ ] **Step 2: FAIL (404).**

- [ ] **Step 3: Register routes** with `permission_profile`-like callback (logged-in `can_learn`). Resend: issue new token + mail. Optional `POST /profile/email` changes email via `wp_update_user`, clears verified, issues token (keep YAGNI: if change-email is heavy, ship resend + landing first and add change-email in same task only if tests from spec mock require it — **spec includes Change email button → implement**).

- [ ] **Step 4: PASS. Commit:** `feat(rest): email verification resend and consume`

---

### Task 5: Course stack redesign

**Files:**
- Modify: `src/Blocks/course/render.php`, `style.scss`, `view.js` as needed
- Test: extend `tests/php/CourseBlockTest.php` or create if missing — assert badge/foot/stack classes

- [ ] **Step 1: Failing assertions** for `bfc-course__badge`, `bfc-course__foot`, materials/quiz order, no progress %.

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: Rewrite markup** to Stack A: badge → title → meta (code · subject · outcome) → lede/excerpt → materials note / rely on theme post content above block as today → `#bfc-course-quiz` when enrolled+verified+mapped → foot status + actions (Enroll / Continue / View / Withdraw). Unverified already handled by Task 3. Match catalog CTA classes (`bfc-course__action`, `is-secondary`). SCSS: tile tokens, no panel shadow, scoped `.wp-site-blocks main` link paint like my-courses.

- [ ] **Step 4: lint + PASS + `npm run build`. Commit:** `feat(blocks): course single stack tile redesign`

---

### Task 6: Enroll mini-card states

**Files:**
- Modify: `src/Blocks/enroll/render.php`, `style.scss`, `view.js`
- Test: new assertions in enroll/block test or `CatalogBlockTest`-style enroll render test

- [ ] **Step 1: Failing tests** — open/invite/enrolled/unverified foot strings.

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: Implement** state foot mirroring catalog; unverified → Verify email CTA only; enroll JS success → Enrolled + Continue like catalog.

- [ ] **Step 4: PASS. Commit:** `feat(blocks): enroll state-aware mini-card`

---

### Task 7: PressPrimer quiz gate + chrome polish

**Files:**
- Modify: `src/Integrations/PressPrimer_Quiz.php`, pressprimer SCSS if needed
- Test: `tests/php/PressPrimerGateTest.php`, `PressPrimerChromeTest.php`

- [ ] **Step 1: Failing test** — unverified learner cannot start mapped quiz (`can_start` false / error code).

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: Add verified check** beside `can_learn` + enrollment. Chrome: back link + status pill already present — ensure token skin / no %; demotion unchanged.

- [ ] **Step 4: PASS. Commit:** `feat(integrations): require email verification for mapped quizzes`

---

### Task 8: Branded mail template

**Files:**
- Create: `src/Mail/Template.php`
- Modify: `src/Mail/Mailer.php` — all send_* use template
- Test: `tests/php/MailerTest.php`

- [ ] **Step 1: Failing test** — `Template::wrap( $title, $body_html )` contains brand string; `wp_mail` headers include `Content-Type: multipart` or HTML content-type per chosen approach (HTML body + `AltBody` via filters, or `wp_mail` HTML with plaintext in filter). Prefer: HTML message + `Content-Type: text/html` and append plaintext alternative via `phpmailer_init` if already used in project — else send HTML and include plaintext URL in body footer.

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: Implement** one wrapper (brand header, title, body slot, footer). Route welcome/enroll/completion/verify/resend through it.

- [ ] **Step 4: PASS. Commit:** `feat(mail): branded HTML template for transactional email`

---

### Task 9: Public certificate verify skin

**Files:**
- Modify: PressPrimer verify templates via filters/CSS in `resources/frontend/scss/pressprimer.scss` / `PressPrimer_Assets.php` enqueue on verify screens
- Test: `tests/php/PressPrimerAssetsTest.php` or chrome test — assert stylesheet conditions

- [ ] **Step 1: Failing test** for enqueue on public verify context (mirror how assets detect surfaces today).

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: Style** code field + valid/invalid/expired using `--bfc-*` panel language; **no shell**.

- [ ] **Step 4: PASS. Commit:** `feat(integrations): skin public certificate verify panel`

---

### Task 10: React Training admin — Site invites

**Files:**
- Modify: `src/Access/Admin.php` — enqueue React app, replace PHP list UI with root div
- Create: `resources/admin/js/training/index.js`, `invites-app.js`, scss entry
- Modify: `webpack.config.js` — new entry `burbank-lms-training-admin`
- REST: reuse/extend admin invite endpoints if site invites lack REST — **if only PHP today, add** `REST\Admin_Controller` or Access REST for site invite CRUD matching course invites shape
- Test: PHPUnit for new REST; JS lint

- [ ] **Step 1: Failing REST tests** for site invite list/create/revoke (mirror course invite tests).

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: REST + React list/create/revoke/copy-once UI** under Training → Site invites.

- [ ] **Step 4: build + PASS. Commit:** `feat(admin): React site invites screen`

---

### Task 11: React Training admin — Learners / Training Access

**Files:**
- Same training admin app router/nav: Site invites | Training access
- REST: grant/revoke/suspend/reactivate/list + admin legal-name correction (wrap existing `Learner` / `Profile::set_legal_name` force)
- Remove PHP profile Training Access UI hooks from `Access\Admin`
- Test: REST + capability checks

- [ ] **Step 1: Failing REST tests** for suspend/reactivate/grant and legal-name admin.

- [ ] **Step 2: FAIL.**

- [ ] **Step 3: Implement React Learners table** (search, actions, verified column, resend verify).

- [ ] **Step 4: PASS. Commit:** `feat(admin): React training access learners screen`

---

### Task 12: Course editor sidebar kit restyle

**Files:**
- Modify: `resources/admin/scss/course-editor.scss`, panel JS only if class names need alignment
- Visual: match training admin tokens (spacing, buttons, tables)

- [ ] **Step 1: No PHPUnit** — lint-style + visual checklist in commit body.

- [ ] **Step 2: Restyle** Invites/Enrollments/Approval panels to shared admin kit.

- [ ] **Step 3: `npm run build`. Commit:** `style(admin): align course sidebar with training admin kit`

---

### Task 13: Docs, register success copy, full QA

**Files:**
- Modify: `docs/learner-access.md`, `AGENTS.md`, `src/Blocks/register/render.php` success strings
- Run full suite

- [ ] **Step 1: Update docs** — verify gate model; REST routes; React admin replaces PHP; mail template.

- [ ] **Step 2: Register success** — “Check your email… then sign in.”

- [ ] **Step 3: `npm run build` && `composer run phpstan` && scoped phpcs && `npm run test:php` → PASS; `npm run env:test:stop`.

- [ ] **Step 4: Commit:** `docs(learner): document phases 2–4 verify and React admin`  
- [ ] **Step 5: Push stacked PR** (base = Phase 1 branch until 34 merges; live preview deferred until deploy).

---

## Spec coverage checklist

| Spec item | Task |
| --- | --- |
| Email verify domain + tokens | 1 |
| Register unverified + verify mail | 2 |
| Shell + verify panel; guest/no-access unchanged | 3 |
| Resend / consume / change-email | 4 |
| Course stack A | 5 |
| Enroll mini-card states | 6 |
| Quiz require verified + chrome | 7 |
| Branded mail wrapper | 8 |
| Public cert verify skin | 9 |
| React site invites | 10 |
| React Training Access | 11 |
| Course sidebar kit | 12 |
| Docs + register copy + suite | 13 |
| No progress % | 5–6 (explicit) |
| Stacked on PR 34 | all commits on `feat/learner-phases-2-4` |

## Self-review notes

- Soft_Gate `should_block` must **not** treat unverified as block-without-shell — Task 3 encodes the shell-then-verify pattern.
- Grandfather: test helpers mark verified; real new registrations do not.
- Change-email included in Task 4 to match approved mock.
- Create-invite **modal** polish optional; create flow required in Task 10.
