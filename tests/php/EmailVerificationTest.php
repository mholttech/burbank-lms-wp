<?php
/** Email verification contracts.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Email_Verification;
use Burbank_LMS\Access\Learner;

/** Verification lifecycle and learning gates. */
class EmailVerificationTest extends WP_UnitTestCase {
	/** Verify the stated lifecycle contract. */
	public function test_legacy_access_and_unverified_gate(): void {
		$id      = self::factory()->user->create();
		$learner = new Learner();
		$learner->grant_access( $id );
		$this->assertTrue( $learner->is_email_verified( $id ) );
		update_user_meta( $id, Learner::META_EMAIL_VERIFIED, '0' );
		$this->assertTrue( $learner->needs_email_verification( $id ) );
		$this->assertFalse( ( new Burbank_LMS\Learner\Soft_Gate() )->should_block( $id ) );
		wp_set_current_user( $id );
		$this->assertWPError( ( new Burbank_LMS\REST\Controller() )->permission_enroll() );
		$this->assertStringContainsString( 'data-bfc-resend-verify', ( new Burbank_LMS\Learner\Soft_Gate() )->render_verify( $id ) );
	}

	/** Verify the stated lifecycle contract. */
	public function test_token_is_email_bound_expiring_and_one_time(): void {
		$id      = self::factory()->user->create();
		$service = new Email_Verification();
		$token   = $service->issue_token( $id );
		$this->assertSame( $id, $service->consume_token( $token ) );
		$this->assertWPError( $service->consume_token( $token ) );
		$token = $service->issue_token( $id );
		wp_update_user(
			array(
				'ID'         => $id,
				'user_email' => 'changed@example.org',
			)
		);
		$this->assertWPError( $service->consume_token( $token ) );
		$token             = $service->issue_token( $id );
		$record            = get_user_meta( $id, 'burbank_lms_email_token', true );
		$record['expires'] = time() - 1;
		update_user_meta( $id, 'burbank_lms_email_token', $record );
		$this->assertWPError( $service->consume_token( $token ) );
	}

	/** Verify the stated lifecycle contract. */
	public function test_resend_is_throttled(): void {
		$id = self::factory()->user->create();
		update_user_meta( $id, 'burbank_lms_verify_sent', time() );
		$this->assertWPError( ( new Email_Verification() )->send( $id ) );
	}

	/** Unverified learners retain shell but cannot see any learner block body. */
	public function test_all_learner_blocks_show_verification_panel(): void {
		$id = self::factory()->user->create();
		( new Learner() )->grant_access( $id );
		update_user_meta( $id, Learner::META_EMAIL_VERIFIED, '0' );
		wp_set_current_user( $id );
		$course = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		foreach ( array( 'catalog', 'profile', 'my-courses', 'course', 'enroll' ) as $block ) {
			$html = do_blocks( '<!-- wp:burbank-lms/' . $block . ' {"courseId":' . $course . '} /-->' );
			$this->assertStringContainsString( 'data-bfc-verify', $html, $block );
			$this->assertStringNotContainsString( 'data-bfc-enroll-submit', $html, $block );
		}
	}

	/** REST endpoints enforce access, throttle and email verification lifecycle. */
	public function test_verification_rest_lifecycle(): void {
		rest_get_server();
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'POST', '/burbank-lms/v1/profile/resend-verification' );
		$this->assertSame( 401, rest_get_server()->dispatch( $request )->get_status() );
		$id = self::factory()->user->create();
		( new Learner() )->grant_access( $id );
		update_user_meta( $id, Learner::META_EMAIL_VERIFIED, '0' );
		wp_set_current_user( $id );
		add_filter( 'pre_wp_mail', '__return_true' );
		try {
			$this->assertSame( 200, rest_get_server()->dispatch( $request )->get_status() );
			$this->assertSame( 429, rest_get_server()->dispatch( $request )->get_status() );
			delete_user_meta( $id, 'burbank_lms_verify_sent' );
			$change = new WP_REST_Request( 'POST', '/burbank-lms/v1/profile/email' );
			$change->set_param( 'email', 'verify-new@example.org' );
			$this->assertSame( 200, rest_get_server()->dispatch( $change )->get_status() );
			$this->assertSame( 'verify-new@example.org', get_userdata( $id )->user_email );
			$this->assertTrue( ( new Learner() )->needs_email_verification( $id ) );
			( new Learner() )->mark_email_verified( $id );
			$this->assertSame( 400, rest_get_server()->dispatch( $request )->get_status() );
		} finally {
			remove_filter( 'pre_wp_mail', '__return_true' );
		}
	}
}
