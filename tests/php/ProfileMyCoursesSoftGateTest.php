<?php
/**
 * Soft-gate coverage for profile and my-courses blocks.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Profile;

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
		$this->assertStringContainsString( 'bfc-profile__summary', $html );
		$this->assertStringContainsString( 'bfc-profile__avatar', $html );
		$this->assertStringContainsString( 'Training access', $html );
		$this->assertStringNotContainsString( 'Member since', $html );
		$this->assertStringContainsString( 'name="first_name"', $html );
		$this->assertStringContainsString( 'name="last_name"', $html );
		$this->assertStringContainsString( 'bfc-profile__cert-preview', $html );
		$this->assertStringContainsString( 'name="display_name"', $html );
		$this->assertStringContainsString( 'bfc-profile__security-form', $html );
		$this->assertStringNotContainsString( 'Contact a training administrator to correct your legal name.', $html );
		$this->assertStringNotContainsString( 'readonly', $html );
	}

	/** Empty WP first/last prefills from the existing legal/certificate name. */
	public function test_profile_render_prefills_first_last_from_legal_name(): void {
		$user_id = self::factory()->user->create(
			array(
				'role'       => 'subscriber',
				'first_name' => '',
				'last_name'  => '',
			)
		);
		( new Learner() )->grant_access( $user_id );
		$this->assertTrue( ( new Profile() )->set_legal_name( $user_id, 'Mary Jane Watson' ) );
		wp_set_current_user( $user_id );
		$html = do_blocks( '<!-- wp:burbank-lms/profile /-->' );
		$this->assertStringContainsString( 'value="Mary"', $html );
		$this->assertStringContainsString( 'value="Jane Watson"', $html );
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
		$this->assertStringContainsString( 'bfc-my-courses__catalog-link', $html );
		$this->assertStringNotContainsString( 'bfc-soft-gate', $html );
	}
}
