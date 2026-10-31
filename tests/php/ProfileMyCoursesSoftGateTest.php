<?php
/**
 * Soft-gate coverage for profile and my-courses blocks.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;

/** Guests and no-access users see Soft_Gate; learners keep form/list markup. */
class ProfileMyCoursesSoftGateTest extends WP_UnitTestCase {

	/** Guest profile render shows soft-gate with login/register CTAs, not the form. */
	public function test_profile_render_soft_gates_guest(): void {
		wp_set_current_user( 0 );
		$html = do_blocks( '<!-- wp:burbank-lms/profile /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $html );
		$this->assertStringContainsString( 'bfc-soft-gate__actions', $html );
		$this->assertStringContainsString( 'Log in', $html );
		$this->assertStringContainsString( 'Register', $html );
		$this->assertStringNotContainsString( 'bfc-profile__form', $html );
		$this->assertStringNotContainsString( 'Sign in to manage your profile.', $html );
	}

	/** Subscriber without training access sees administrator soft-gate messaging on profile. */
	public function test_profile_render_soft_gates_no_access(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/profile /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $html );
		$this->assertStringContainsString( 'administrator', strtolower( $html ) );
		$this->assertStringNotContainsString( 'bfc-soft-gate__actions', $html );
		$this->assertStringNotContainsString( 'bfc-profile__form', $html );
	}

	/** Learner with training access still sees the profile form. */
	public function test_profile_render_learner_sees_form(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/profile /-->' );
		$this->assertStringContainsString( 'bfc-profile__form', $html );
		$this->assertStringNotContainsString( 'bfc-soft-gate', $html );
	}

	/** Guest my-courses render shows soft-gate with login/register CTAs, not the list shell. */
	public function test_my_courses_render_soft_gates_guest(): void {
		wp_set_current_user( 0 );
		$html = do_blocks( '<!-- wp:burbank-lms/my-courses /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $html );
		$this->assertStringContainsString( 'bfc-soft-gate__actions', $html );
		$this->assertStringContainsString( 'Log in', $html );
		$this->assertStringContainsString( 'Register', $html );
		$this->assertStringNotContainsString( 'bfc-my-courses__list', $html );
		$this->assertStringNotContainsString( 'bfc-my-courses__catalog-cta', $html );
		$this->assertStringNotContainsString( 'Sign in to view your courses.', $html );
	}

	/** Subscriber without training access sees administrator soft-gate messaging on my-courses. */
	public function test_my_courses_render_soft_gates_no_access(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/my-courses /-->' );
		$this->assertStringContainsString( 'bfc-soft-gate', $html );
		$this->assertStringContainsString( 'administrator', strtolower( $html ) );
		$this->assertStringNotContainsString( 'bfc-soft-gate__actions', $html );
		$this->assertStringNotContainsString( 'bfc-my-courses__list', $html );
		$this->assertStringNotContainsString( 'bfc-my-courses__catalog-cta', $html );
	}

	/** Learner with training access still sees my-courses list markup. */
	public function test_my_courses_render_learner_sees_list(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		( new Learner() )->grant_access( $user_id );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/my-courses /-->' );
		$this->assertStringContainsString( 'bfc-my-courses__list', $html );
		$this->assertStringContainsString( 'bfc-my-courses__catalog-cta', $html );
		$this->assertStringNotContainsString( 'bfc-soft-gate', $html );
	}
}
