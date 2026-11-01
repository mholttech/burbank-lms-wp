<?php
/** React training admin contracts.
 *
 * @package Burbank_LMS
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer API property names in MIME assertions.
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Test private plaintext transport header.
use Burbank_LMS\Access\Learner;
use Burbank_LMS\Access\Invites;

/** End-to-end REST permissions, mutations and safe payloads. */
class TrainingAdminRestTest extends WP_UnitTestCase {
	/** Set training capabilities. */
	public function set_up(): void {
		parent::set_up();
		( new Burbank_LMS\Courses\Capabilities() )->install();
	}

	/** Dispatch actual registered routes.
	 *
	 * @param string              $method Method.
	 * @param string              $path Route suffix.
	 * @param array<string,mixed> $data Parameters.
	 */
	private function request( string $method, string $path, array $data = array() ): WP_REST_Response {
		$request = new WP_REST_Request( $method, '/burbank-lms/v1/training' . $path );
		$request->set_body_params( $data );
		return rest_get_server()->dispatch( $request );
	}

	/** Unprivileged callers cannot read user emails or mutate invites. */
	public function test_permissions(): void {
		foreach ( array( 0, self::factory()->user->create( array( 'role' => 'subscriber' ) ) ) as $id ) {
			wp_set_current_user( $id );
			$this->assertGreaterThanOrEqual( 400, $this->request( 'GET', '/learners' )->get_status() );
			$this->assertGreaterThanOrEqual( 400, $this->request( 'POST', '/invites', array( 'kind' => 'once' ) )->get_status() );
		}
	}

	/** Invites expose plaintext once, never their stored hash, and scope revocation. */
	public function test_invite_create_list_revoke(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$created = $this->request(
			'POST',
			'/invites',
			array(
				'kind'        => 'once',
				'label'       => 'Volunteers',
				'bound_email' => 'volunteer@example.org',
			)
		);
		$this->assertSame( 200, $created->get_status() );
		$data = $created->get_data();
		$this->assertNotEmpty( $data['plaintext'] );
		$rows = $this->request( 'GET', '/invites' )->get_data();
		$this->assertStringNotContainsString( 'code_hash', wp_json_encode( $rows ) );
		$this->assertStringNotContainsString( $data['plaintext'], wp_json_encode( $rows ) );
		$this->assertSame( 200, $this->request( 'DELETE', '/invites/' . $data['id'] )->get_status() );
		$course = ( new Invites() )->create( 'course', array( 'course_id' => 77 ) );
		$this->assertIsArray( $course );
		$this->assertSame( 404, $this->request( 'DELETE', '/invites/' . $course['id'] )->get_status() );
		$this->assertSame( 400, $this->request( 'POST', '/invites', array( 'max_uses' => -1 ) )->get_status() );
	}

	/** Learner actions use audited domain services. */
	public function test_learner_management_and_search(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = self::factory()->user->create( array( 'user_email' => 'training-admin-subject@example.org' ) );
		( new Burbank_LMS\Access\Profile() )->set_legal_name( $id, 'Original Name' );
		foreach ( array( 'grant', 'suspend', 'reactivate', 'revoke' ) as $action ) {
			$response = $this->request( 'POST', '/learners/' . $id, array( 'operation' => $action ) );
			$this->assertSame( 200, $response->get_status() );
			$data = $response->get_data();
			$this->assertSame( 'suspend' === $action, $data['suspended'] );
			$this->assertSame( 'revoke' !== $action, $data['access'] );
		}
		$this->assertSame(
			200,
			$this->request(
				'POST',
				'/learners/' . $id,
				array(
					'operation'  => 'legal_name',
					'legal_name' => 'Correct Name',
				)
			)->get_status()
		);
		$this->assertSame( 'Correct Name', ( new Burbank_LMS\Access\Profile() )->get_legal_name( $id ) );
		$audit = json_decode( get_user_meta( $id, Burbank_LMS\Access\Profile::META_LEGAL_NAME_AUDIT, true ), true );
		$this->assertSame( 'Correct Name', $audit[0]['to'] );
		$this->assertSame( get_current_user_id(), $audit[0]['by'] );
		$request = new WP_REST_Request( 'GET', '/burbank-lms/v1/training/learners' );
		$request->set_query_params( array( 'search' => 'training-admin-subject' ) );
		$this->assertSame( 1, rest_get_server()->dispatch( $request )->get_data()['total'] );
		$this->assertSame( 400, $this->request( 'POST', '/learners/' . $id, array( 'operation' => 'unknown' ) )->get_status() );
		$this->assertSame( 404, $this->request( 'POST', '/learners/999999', array( 'operation' => 'grant' ) )->get_status() );
		( new Learner() )->grant_access( $id );
		update_user_meta( $id, Learner::META_EMAIL_VERIFIED, '0' );
		update_user_meta( $id, 'burbank_lms_verify_sent', time() );
		$this->assertSame( 429, $this->request( 'POST', '/learners/' . $id, array( 'operation' => 'resend' ) )->get_status() );
	}

	/** WordPress sends multipart LMS mail and resets its reused mailer for unrelated mail. */
	public function test_wordpress_multipart_and_following_mail_isolation(): void {
		global $phpmailer;
		$previous  = $phpmailer;
		$phpmailer = new class() extends PHPMailer\PHPMailer\PHPMailer {
			/** Render MIME without contacting a transport. */
			public function send() {
				return $this->preSend();
			}
		};
		try {
			$this->assertTrue( Burbank_LMS\Mail\Template::send( 'recipient@example.org', 'Verify', "Follow this link:\nhttps://example.org/verify?token=123" ) );
			$this->assertStringContainsString( 'multipart/alternative', $phpmailer->getSentMIMEMessage() );
			$this->assertStringContainsString( 'https://example.org/verify?token=123', $phpmailer->AltBody );
			$this->assertStringNotContainsString( 'X-Burbank-LMS-Plain', $phpmailer->getSentMIMEMessage() );
			$this->assertTrue( wp_mail( 'other@example.org', 'Unrelated', 'Other body' ) );
			$this->assertSame( '', $phpmailer->AltBody );
			$this->assertStringNotContainsString( 'multipart/alternative', $phpmailer->getSentMIMEMessage() );
		} finally {
			$phpmailer = $previous;
		}
	}

	/** MIME alternative is real and never affects an unrelated message. */
	public function test_mail_alternative_scope_and_brand(): void {
		$mailer = new PHPMailer\PHPMailer\PHPMailer();
		$mailer->addCustomHeader( 'X-Burbank-LMS-Plain', base64_encode( "Hello\nTraining" ) );
		( new Burbank_LMS\Mail\Template() )->configure_alternative( $mailer );
		$this->assertSame( "Hello\nTraining", $mailer->AltBody );
		$this->assertSame( array(), $mailer->getCustomHeaders() );
		$mailer->AltBody = 'Other mail';
		( new Burbank_LMS\Mail\Template() )->configure_alternative( $mailer );
		$this->assertSame( 'Other mail', $mailer->AltBody );
		$this->assertStringContainsString( 'Burbank LMS', Burbank_LMS\Mail\Template::wrap( 'Welcome', '<p>Hello</p>' ) );
	}
}
