<?php
/**
 * Site and course invite service coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Access\Invites;
use Burbank_LMS\Access\Tables as AccessTables;

/** Exercises invite create, lookup, redeem, and scope isolation. */
class AccessInvitesTest extends WP_UnitTestCase {
	/** Install invite tables and authenticate an admin for creates. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Registry-style transactions commit outside PHPUnit's wrapper.
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		delete_option( AccessTables::VERSION_OPTION );
		$this->assertTrue( ( new AccessTables() )->install() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Restore PHPUnit transaction wrapping after real commits. */
	public function tear_down(): void {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_invite_codes" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/** Return a default invite service bound to the test database. */
	private function invites(): Invites {
		return new Invites();
	}

	/**
	 * Start a separate PHP process that calls Invites::redeem for the given row.
	 *
	 * @param int $id Invite row ID.
	 * @return array{0:resource|false,1:array<int,resource>,2:string}
	 */
	private function spawn_redeem_process( int $id ): array {
		global $wpdb;
		$plugin_root = dirname( __DIR__, 2 );
		$script      = tempnam( sys_get_temp_dir(), 'bfc-redeem-' );
		$this->assertNotFalse( $script );
		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Build isolated redeem subprocess source.
		$contents = sprintf(
			'<?php
define( "WP_USE_THEMES", false );
require %1$s;
require %2$s;
$GLOBALS["wpdb"]->set_prefix( %3$s );
echo ( new \\Burbank_LMS\\Access\\Invites() )->redeem( %4$d ) ? "1" : "0";
',
			var_export( ABSPATH . 'wp-load.php', true ),
			var_export( $plugin_root . '/vendor/autoload.php', true ),
			var_export( $wpdb->prefix, true ),
			$id
		);
		// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_var_export
		file_put_contents( $script, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Ephemeral parallel redeem fixture.
		$descriptors = array(
			1 => array( 'pipe', 'w' ),
			2 => array( 'pipe', 'w' ),
		);
		$process     = proc_open( array( 'php', $script ), $descriptors, $pipes, ABSPATH ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- Parallel redeem sessions.
		return array( $process, $pipes, $script );
	}

	/** Site-scoped multi-use codes persist hashed and stay discoverable until limits hit. */
	public function test_create_site_multi_invite(): void {
		$created = $this->invites()->create(
			'site',
			array(
				'label' => 'Volunteer batch',
				'kind'  => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$this->assertArrayHasKey( 'id', $created );
		$this->assertArrayHasKey( 'plaintext', $created );
		$this->assertNotSame( '', $created['plaintext'] );
		$row = $this->invites()->find_active( $created['plaintext'], 'site' );
		$this->assertNotNull( $row );
		$this->assertSame( (int) $created['id'], (int) $row->id );
		$this->assertSame( 'site', $row->scope );
		$this->assertSame(
			hash_hmac( 'sha256', $created['plaintext'], wp_salt( 'auth' ) ),
			$row->code_hash
		);
	}

	/** Site-scoped one-time codes are active until the first redeem. */
	public function test_create_site_once_invite(): void {
		$created = $this->invites()->create(
			'site',
			array(
				'label' => 'Single seat',
				'kind'  => 'once',
			)
		);
		$this->assertIsArray( $created );
		$row = $this->invites()->find_active( $created['plaintext'], 'site' );
		$this->assertNotNull( $row );
		$this->assertSame( 'once', $row->kind );
		$this->assertTrue( $this->invites()->redeem( (int) $created['id'] ) );
		$this->assertNull( $this->invites()->find_active( $created['plaintext'], 'site' ) );
	}

	/** Course invites require a course id at creation time. */
	public function test_create_course_requires_course_id(): void {
		$error = $this->invites()->create(
			'course',
			array(
				'label' => 'Restricted',
				'kind'  => 'multi',
			)
		);
		$this->assertWPError( $error );
		$this->assertSame( 'burbank_invite_missing_course_id', $error->get_error_code() );

		$course_id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$created   = $this->invites()->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Restricted',
				'kind'      => 'multi',
			)
		);
		$this->assertIsArray( $created );
		$row = $this->invites()->find_active( $created['plaintext'], 'course', $course_id );
		$this->assertNotNull( $row );
		$this->assertSame( $course_id, (int) $row->course_id );
	}

	/** Revoked codes no longer resolve as active. */
	public function test_revoke_makes_invite_inactive(): void {
		$created = $this->invites()->create(
			'site',
			array(
				'label' => 'Revocable',
				'kind'  => 'multi',
			)
		);
		$this->assertTrue( $this->invites()->revoke( (int) $created['id'] ) );
		$this->assertNull( $this->invites()->find_active( $created['plaintext'], 'site' ) );
	}

	/** Expired codes are not returned by find_active. */
	public function test_expired_invite_is_not_active(): void {
		$created = $this->invites()->create(
			'site',
			array(
				'label'      => 'Expired',
				'kind'       => 'multi',
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() - 3600 ),
			)
		);
		$this->assertNull( $this->invites()->find_active( $created['plaintext'], 'site' ) );
	}

	/** Multi-use codes honor max_uses then stop redeeming. */
	public function test_max_uses_limits_multi_invite(): void {
		$created = $this->invites()->create(
			'site',
			array(
				'label'    => 'Cap two',
				'kind'     => 'multi',
				'max_uses' => 2,
			)
		);
		$id      = (int) $created['id'];
		$this->assertTrue( $this->invites()->redeem( $id ) );
		$this->assertTrue( $this->invites()->redeem( $id ) );
		$this->assertFalse( $this->invites()->redeem( $id ) );
		$this->assertNull( $this->invites()->find_active( $created['plaintext'], 'site' ) );
	}

	/** Site and course scopes never match each other's lookup. */
	public function test_site_and_course_scopes_are_not_interchangeable(): void {
		$course_id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$site      = $this->invites()->create(
			'site',
			array(
				'label' => 'Site only',
				'kind'  => 'multi',
			)
		);
		$course    = $this->invites()->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Course only',
				'kind'      => 'multi',
			)
		);
		$this->assertNotNull( $this->invites()->find_active( $site['plaintext'], 'site' ) );
		$this->assertNull( $this->invites()->find_active( $site['plaintext'], 'course', $course_id ) );
		$this->assertNotNull( $this->invites()->find_active( $course['plaintext'], 'course', $course_id ) );
		$this->assertNull( $this->invites()->find_active( $course['plaintext'], 'site' ) );
	}

	/** Restricted-course detection follows active course-scoped rows. */
	public function test_course_has_active_invites(): void {
		$course_id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$other_id  = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		$this->assertFalse( $this->invites()->course_has_active_invites( $course_id ) );

		$created = $this->invites()->create(
			'course',
			array(
				'course_id' => $course_id,
				'label'     => 'Gate',
				'kind'      => 'multi',
			)
		);
		$this->assertTrue( $this->invites()->course_has_active_invites( $course_id ) );
		$this->assertFalse( $this->invites()->course_has_active_invites( $other_id ) );

		$this->assertTrue( $this->invites()->revoke( (int) $created['id'] ) );
		$this->assertFalse( $this->invites()->course_has_active_invites( $course_id ) );
	}

	/** Overlapping connections cannot both burn the same one-time code. */
	public function test_concurrent_redeem_of_once_code_allows_only_one_success(): void {
		$created = $this->invites()->create(
			'site',
			array(
				'label' => 'Race',
				'kind'  => 'once',
			)
		);
		$id      = (int) $created['id'];
		global $wpdb;
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Subprocess redeems need a committed invite row.

		list( $proc_a, $pipes_a, $script_a ) = $this->spawn_redeem_process( $id );
		list( $proc_b, $pipes_b, $script_b ) = $this->spawn_redeem_process( $id );
		$this->assertIsResource( $proc_a );
		$this->assertIsResource( $proc_b );

		$out_a = stream_get_contents( $pipes_a[1] );
		$out_b = stream_get_contents( $pipes_b[1] );
		proc_close( $proc_a );
		proc_close( $proc_b );
		unlink( $script_a ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Ephemeral parallel redeem fixture.
		unlink( $script_b ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Ephemeral parallel redeem fixture.

		$wins = ( '1' === trim( (string) $out_a ) ? 1 : 0 ) + ( '1' === trim( (string) $out_b ) ? 1 : 0 );
		$this->assertSame( 1, $wins );
		$this->assertFalse( $this->invites()->redeem( $id ) );
	}
}
