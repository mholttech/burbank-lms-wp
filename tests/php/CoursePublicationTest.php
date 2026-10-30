<?php
/**
 * Publication policy integration tests.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Registry;
use Burbank_LMS\Courses\Schema;
use Burbank_LMS\Courses\Publication;
use Burbank_LMS\Courses\Validator;

/** Exercise publication policy using WordPress and released plugins. */
class CoursePublicationTest extends WP_UnitTestCase {
	/** Recreate test schema and clear reservations between cases. */
	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		// Registry transactions commit; opt out of PHPUnit's wrapping transaction.
		$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
		( new Schema() )->register();
		( new Registry() )->install();
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Explicit cleanup is required because registry transactions commit real fixtures. */
	public function tear_down(): void {
		global $wpdb;
		$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'bfc_course'" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}
		$wpdb->query( "DELETE FROM {$wpdb->prefix}bfc_course_registry" ); // phpcs:ignore WordPress.DB -- Isolated application fixture.
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB -- Transaction integration fixture.
		parent::tear_down();
	}

	/** Dependencies fail closed. */
	public function test_dependencies_fail_closed(): void {
		$this->assertTrue( ( new Publication() )->validate( 0, array() )->has_errors() );
	}

	/** Direct publish cannot bypass approval. */
	public function test_direct_publish_cannot_bypass_approval(): void {
		$id = wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_title'  => 'Unapproved',
				'post_status' => 'draft',
				'meta_input'  => array(
					'bfc_code'                => 'BFC-220',
					'subject_category'        => 'safety_field_awareness',
					'course_revision'         => '1',
					'course_outcome'          => 'independent_study',
					'pressprimer_quiz_id'     => 1,
					'certificate_template_id' => 2,
				),
			)
		);
		wp_publish_post( $id );
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	/** Real released mapping and trigger failures. */
	public function test_real_released_mapping_and_trigger_failures(): void {
		$mapping = bfc_test_mapping();
		$adapter = new Burbank_LMS\Integrations\PressPrimer();
		$this->assertFalse( $adapter->validate_mapping( $mapping['pressprimer_quiz_id'], $mapping['certificate_template_id'] )->has_errors() );
		foreach ( array(
			array( 'source_ref' => 'any' ),
			array( 'source_ref' => '99999' ),
			array(
				'source_ref' => (string) $mapping['pressprimer_quiz_id'],
				'is_active'  => 0,
			),
		) as $trigger ) {
			PressPrimer_Certificate_Trigger::replace_for_template(
				$mapping['certificate_template_id'],
				array(
					array_replace(
						array(
							'trigger_type' => 'ppq_quiz',
							'is_active'    => 1,
						),
						$trigger
					),
				)
			);
			$this->assertTrue( $adapter->validate_mapping( $mapping['pressprimer_quiz_id'], $mapping['certificate_template_id'] )->has_errors() );
		}
	}

	/** Approval and rest and classic gates. */
	public function test_approval_and_rest_and_classic_gates(): void {
		$mapping = bfc_test_mapping();
		$id      = wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_title'  => 'Safety',
				'post_status' => 'draft',
				'meta_input'  => array_replace(
					array(
						'bfc_code'         => 'BFC-230',
						'subject_category' => 'safety_field_awareness',
						'course_revision'  => '1',
						'course_outcome'   => 'independent_study',
					),
					$mapping
				),
			)
		);
		$fields  = ( new Validator() )->fields( $id );
		$this->assertContains(
			'burbank_course_approval',
			( new Publication() )->validate(
				$id,
				array_replace(
					$fields,
					array(
						'approved_by' => 1,
						'approved_at' => '2026-10-30',
					)
				)
			)->get_error_codes()
		);
		$request = new WP_REST_Request( 'POST' );
		$request->set_param( 'id', $id );
		$this->assertWPError( ( new Validator() )->rest_preflight( (object) array( 'post_status' => 'publish' ), $request ) );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );
		bfc_test_approve( $id );
		$this->assertFalse( ( new Publication() )->validate( $id, $fields )->has_errors() );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( '1', ( new Registry() )->get( $id )['published'] );
	}

	/** Unpublished quiz and duplicate assignment. */
	public function test_unpublished_quiz_and_duplicate_assignment(): void {
		$mapping      = bfc_test_mapping();
		$quiz         = PressPrimer_Quiz_Quiz::get( $mapping['pressprimer_quiz_id'] );
		$quiz->status = 'draft';
		$quiz->save();
		$this->assertContains( 'burbank_course_quiz', ( new Burbank_LMS\Integrations\PressPrimer() )->validate_mapping( $mapping['pressprimer_quiz_id'], $mapping['certificate_template_id'] )->get_error_codes() );
		$id = wp_insert_post(
			array(
				'post_type'  => 'bfc_course',
				'post_title' => 'Duplicate',
				'meta_input' => $mapping,
			)
		);
		$this->assertContains( 'burbank_course_duplicate_quiz', ( new Publication() )->validate( 0, $mapping )->get_error_codes() );
		wp_trash_post( $id );
		$this->assertContains( 'burbank_course_duplicate_quiz', ( new Publication() )->validate( 0, $mapping )->get_error_codes() );
	}

	/** Missing plugins, unsupported releases and changed contracts fail closed. */
	public function test_missing_unsupported_and_changed_contracts_fail_closed(): void {
		$mapping = bfc_test_mapping();
		$cases   = array(
			'missing quiz'        => array( null, '2.0.2', null ),
			'missing certificate' => array( '3.1.3', null, null ),
			'old quiz'            => array( '3.1.2', '2.0.2', null ),
			'new certificate'     => array( '3.1.3', '2.1.0', null ),
			'changed contract'    => array(
				'3.1.3',
				'2.0.2',
				array( 'PressPrimer_Quiz_Quiz' => 'no_such_released_method' ),
			),
		);
		foreach ( $cases as $label => $case ) {
			$adapter = new class( ...$case ) extends Burbank_LMS\Integrations\PressPrimer {
				/** Simulated installed releases.
				 *
				 * @var array<string,string|null>
				 */
				private array $simulated;
				/** Simulated contract methods.
				 *
				 * @var array<string,string>|null
				 */
				private ?array $contracts;
				/** Capture simulated state.
				 *
				 * @param string|null               $quiz Quiz version.
				 * @param string|null               $certificate Certificate version.
				 * @param array<string,string>|null $contracts Contract overrides.
				 */
				public function __construct( ?string $quiz, ?string $certificate, ?array $contracts ) {
					$this->simulated = array(
						'quiz'        => $quiz,
						'certificate' => $certificate,
					);
					$this->contracts = $contracts;
				}
				/** Simulated releases. */
				protected function installed_versions(): array {
					return $this->simulated;
				}
				/** Simulated contract methods. */
				protected function contract_methods(): array {
					return $this->contracts ?? parent::contract_methods();
				}
			};
			$result  = $adapter->validate_mapping( $mapping['pressprimer_quiz_id'], $mapping['certificate_template_id'] );
			$this->assertSame( array( 'burbank_course_dependencies' ), $result->get_error_codes(), $label );
			$this->assertStringContainsString( '3.1.3', $result->get_error_message(), $label );
		}
	}

	/** The real adapter is satisfied only by the pinned released contracts. */
	public function test_real_released_contracts_are_supported_or_absent(): void {
		$mapping = bfc_test_mapping_or_empty();
		$result  = ( new Burbank_LMS\Integrations\PressPrimer() )->validate_mapping( (int) ( $mapping['pressprimer_quiz_id'] ?? 1 ), (int) ( $mapping['certificate_template_id'] ?? 2 ) );
		if ( getenv( 'BFC_TEST_NO_PRESSPRIMER' ) ) {
			$this->assertSame( array( 'burbank_course_dependencies' ), $result->get_error_codes() );
			return;
		}
		$this->assertSame( '3.1.3', PRESSPRIMER_QUIZ_VERSION );
		$this->assertSame( '2.0.2', PPCERT_VERSION );
		$this->assertFalse( $result->has_errors() );
	}

	/** Template, quiz and trigger problems each produce their own recoverable error. */
	public function test_quiz_template_and_trigger_failures_are_specific(): void {
		$mapping = bfc_test_mapping();
		$adapter = new Burbank_LMS\Integrations\PressPrimer();
		$quiz_id = $mapping['pressprimer_quiz_id'];
		$tpl_id  = $mapping['certificate_template_id'];
		$exact   = array(
			'trigger_type' => 'ppq_quiz',
			'source_ref'   => (string) $quiz_id,
			'is_active'    => 1,
		);
		$this->assertContains( 'burbank_course_quiz', $adapter->validate_mapping( 99999, $tpl_id )->get_error_codes() );
		$this->assertContains( 'burbank_course_certificate', $adapter->validate_mapping( $quiz_id, 99999 )->get_error_codes() );
		// A null source reference matches every quiz in the released trigger table.
		PressPrimer_Certificate_Trigger::replace_for_template(
			$tpl_id,
			array(
				$exact,
				array(
					'trigger_type' => 'ppq_quiz',
					'is_active'    => 1,
				),
			)
		);
		$this->assertContains( 'burbank_course_any_quiz', $adapter->validate_mapping( $quiz_id, $tpl_id )->get_error_codes() );
		// Another active quiz on the template would issue this course's certificate for that quiz.
		PressPrimer_Certificate_Trigger::replace_for_template(
			$tpl_id,
			array(
				$exact,
				array_replace( $exact, array( 'source_ref' => '99999' ) ),
			)
		);
		$this->assertContains( 'burbank_course_trigger_mismatch', $adapter->validate_mapping( $quiz_id, $tpl_id )->get_error_codes() );
		// Inactive extra triggers and unrelated trigger types are harmless.
		PressPrimer_Certificate_Trigger::replace_for_template(
			$tpl_id,
			array(
				$exact,
				array_replace(
					$exact,
					array(
						'source_ref' => '99999',
						'is_active'  => 0,
					)
				),
				array(
					'trigger_type' => 'other_integration',
					'source_ref'   => 'any',
					'is_active'    => 1,
				),
			)
		);
		$this->assertFalse( $adapter->validate_mapping( $quiz_id, $tpl_id )->has_errors() );
		$quiz         = PressPrimer_Quiz_Quiz::get( $quiz_id );
		$quiz->status = 'archived';
		$quiz->save();
		$this->assertContains( 'burbank_course_quiz', $adapter->validate_mapping( $quiz_id, $tpl_id )->get_error_codes() );
	}

	/**
	 * Create a complete draft course for the supplied released mapping.
	 *
	 * @param array<string,int> $mapping Released quiz and template IDs.
	 * @param string            $code Permanent BFC code.
	 * @param bool              $approve Whether to persist approval evidence.
	 */
	private function course( array $mapping, string $code, bool $approve = true ): int {
		$id = wp_insert_post(
			array(
				'post_type'   => 'bfc_course',
				'post_title'  => 'Course ' . $code,
				'post_status' => 'draft',
				'meta_input'  => array_replace(
					array(
						'bfc_code'         => $code,
						'subject_category' => Validator::SUBJECTS[ (int) $code[4] ],
						'course_revision'  => '1',
						'course_outcome'   => 'independent_study',
					),
					$mapping
				),
			)
		);
		if ( $approve ) {
			bfc_test_approve( $id );
		}
		return $id;
	}

	/** REST publication is rejected with actionable errors and leaves a draft. */
	public function test_rest_publication_rejection_is_actionable_and_recoverable(): void {
		$mapping = bfc_test_mapping();
		$id      = $this->course( $mapping, 'BFC-250', false );
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
		$request->set_param( 'status', 'publish' );
		$response = rest_do_request( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertContains( 'burbank_course_approval', $response->as_error()->get_error_codes() );
		$this->assertSame( 'draft', get_post_status( $id ) );
		bfc_test_approve( $id );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( '1', ( new Registry() )->get( $id )['published'] );
	}

	/** Stored approval requires an approver who is currently authorized. */
	public function test_unauthorized_stored_approver_cannot_publish(): void {
		$id     = $this->course( bfc_test_mapping(), 'BFC-260', false );
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->assertFalse( user_can( $editor, 'approve_bfc_courses' ) ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Registered by Courses\Capabilities.
		Validator::internal_write(
			static function () use ( $id, $editor ): void {
				update_post_meta( $id, 'approved_by', $editor );
				update_post_meta( $id, 'approved_at', gmdate( 'Y-m-d H:i:s' ) );
			}
		);
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
		$request->set_param( 'status', 'publish' );
		$response = rest_do_request( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertContains( 'burbank_course_approval', $response->as_error()->get_error_codes() );
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertContains( 'burbank_course_approval', ( new Publication() )->validate( $id, ( new Validator() )->fields( $id ) )->get_error_codes() );
		wp_publish_post( $id );
		$this->assertSame( 'draft', get_post_status( $id ) );
		// The fixture records the current administrator, whose capability permits publication.
		bfc_test_approve( $id );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/** A REST edit with no status cannot leave an already published course invalid. */
	public function test_rest_meta_edit_of_published_course_is_rejected_when_invalid(): void {
		$mapping = bfc_test_mapping();
		$id      = $this->course( $mapping, 'BFC-251' );
		wp_publish_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$quiz         = PressPrimer_Quiz_Quiz::get( $mapping['pressprimer_quiz_id'] );
		$quiz->status = 'archived';
		$quiz->save();
		$request = new WP_REST_Request( 'POST', rest_get_route_for_post( $id ) );
		$request->set_param( 'title', 'Retitled' );
		$response = rest_do_request( $request );
		$this->assertSame( 400, $response->get_status() );
		$this->assertContains( 'burbank_course_quiz', $response->as_error()->get_error_codes() );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( 'Course BFC-251', get_post( $id )->post_title );
	}

	/** Core insert and update paths both fall back to a recoverable draft. */
	public function test_core_save_paths_revert_unapproved_or_invalid_publication(): void {
		$mapping = bfc_test_mapping();
		$id      = $this->course( $mapping, 'BFC-252', false );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertSame( '0', ( new Registry() )->get( $id )['published'] );
		bfc_test_approve( $id );
		$quiz         = PressPrimer_Quiz_Quiz::get( $mapping['pressprimer_quiz_id'] );
		$quiz->status = 'draft';
		$quiz->save();
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $id ) );
		$quiz->status = 'published';
		$quiz->save();
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'publish', get_post_status( $id ) );
	}

	/** Classic editor falls back to a draft, replaces the misleading published message and explains why. */
	public function test_classic_editor_fallback_notice_and_message(): void {
		$id = $this->course( bfc_test_mapping(), 'BFC-253', false );
		wp_update_post(
			array(
				'ID'          => $id,
				'post_status' => 'publish',
				'meta_input'  => array( 'course_revision' => '2' ),
			)
		);
		$this->assertSame( '2', get_post_meta( $id, 'course_revision', true ) );
		$location = apply_filters( 'redirect_post_location', admin_url( 'post.php?post=' . $id . '&action=edit&message=6' ), $id );
		$this->assertStringContainsString( 'message=10', $location );
		$this->assertStringNotContainsString( 'message=6', $location );
		$this->assertContains( 'Obtain course approval before publishing.', get_transient( 'burbank_course_notice_' . get_current_user_id() ) );
		ob_start();
		do_action( 'admin_notices' );
		$html = ob_get_clean();
		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'Obtain course approval', $html );
		$this->assertSame( 'draft', get_post_status( $id ) );
	}

	/** Direct publication of an approved course seals identity and announces one transition. */
	public function test_direct_publication_announces_one_sealed_transition(): void {
		$id    = $this->course( bfc_test_mapping(), 'BFC-254' );
		$seen  = array();
		$watch = function ( $to, $from, $post ) use ( $id, &$seen ): void {
			if ( $id === $post->ID && 'publish' === $to ) {
				$row    = ( new Registry() )->get( $id );
				$seen[] = array( $from, $post->post_status, $row ? $row['published'] : null );
			}
		};
		add_action( 'transition_post_status', $watch, 20, 3 );
		wp_publish_post( $id );
		remove_action( 'transition_post_status', $watch, 20 );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( array( array( 'draft', 'publish', '1' ) ), $seen );
	}

	/**
	 * Move a scheduled fixture into the past as the cron queue would observe it.
	 *
	 * @param int $id Course ID.
	 */
	private function age_post( int $id ): void {
		global $wpdb;
		$past = gmdate( 'Y-m-d H:i:s', time() - 60 );
		// phpcs:disable WordPress.DB -- Age the scheduled fixture.
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_date'     => get_date_from_gmt( $past ),
				'post_date_gmt' => $past,
			),
			array( 'ID' => $id )
		);
		// phpcs:enable WordPress.DB
		clean_post_cache( $id );
	}

	/** Scheduled publication is gated when scheduled and again when cron publishes. */
	public function test_scheduled_publication_is_gated_at_schedule_and_publish_time(): void {
		$future = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		$args   = array(
			'post_status'   => 'future',
			'edit_date'     => true,
			'post_date'     => get_date_from_gmt( $future ),
			'post_date_gmt' => $future,
		);
		$bad    = $this->course( bfc_test_mapping(), 'BFC-255', false );
		wp_update_post( array_replace( $args, array( 'ID' => $bad ) ) );
		$this->assertSame( 'draft', get_post_status( $bad ) );

		$id = $this->course( bfc_test_mapping(), 'BFC-256' );
		wp_update_post( array_replace( $args, array( 'ID' => $id ) ) );
		$this->assertSame( 'future', get_post_status( $id ) );
		$this->assertSame( '0', ( new Registry() )->get( $id )['published'] );
		// Approval is withdrawn by trusted code before the cron event fires.
		Validator::internal_write(
			static function () use ( $id ): void {
				delete_post_meta( $id, 'approved_by' );
			}
		);
		$this->age_post( $id );
		check_and_publish_future_post( $id );
		$this->assertSame( 'draft', get_post_status( $id ) );
		$this->assertSame( '0', ( new Registry() )->get( $id )['published'] );

		bfc_test_approve( $id );
		wp_update_post( array_replace( $args, array( 'ID' => $id ) ) );
		$this->age_post( $id );
		check_and_publish_future_post( $id );
		$this->assertSame( 'publish', get_post_status( $id ) );
		$this->assertSame( '1', ( new Registry() )->get( $id )['published'] );
	}

	/** Quiz assignment is exclusive across every other course state. */
	public function test_duplicate_quiz_assignment_blocks_publication(): void {
		$mapping = bfc_test_mapping();
		$first   = $this->course( $mapping, 'BFC-257' );
		$second  = $this->course( $mapping, 'BFC-258' );
		wp_update_post(
			array(
				'ID'          => $second,
				'post_status' => 'publish',
			)
		);
		$this->assertSame( 'draft', get_post_status( $second ) );
		$this->assertContains( 'burbank_course_duplicate_quiz', ( new Publication() )->validate( $second, ( new Validator() )->fields( $second ) )->get_error_codes() );
		wp_trash_post( $first );
		$this->assertContains( 'burbank_course_duplicate_quiz', ( new Publication() )->validate( $second, ( new Validator() )->fields( $second ) )->get_error_codes() );
	}

	/** Published courses keep identity/mapping gates but can still be archived when dependencies fail. */
	public function test_published_course_edits_are_gated_but_archiving_remains_possible(): void {
		$mapping = bfc_test_mapping();
		$id      = $this->course( $mapping, 'BFC-259' );
		wp_publish_post( $id );
		$other        = bfc_test_mapping();
		$quiz         = PressPrimer_Quiz_Quiz::get( $other['pressprimer_quiz_id'] );
		$quiz->status = 'draft';
		$quiz->save();
		$this->assertFalse( update_post_meta( $id, 'pressprimer_quiz_id', $other['pressprimer_quiz_id'] ) );
		$this->assertSame( (string) $mapping['pressprimer_quiz_id'], get_post_meta( $id, 'pressprimer_quiz_id', true ) );
		$broken         = PressPrimer_Quiz_Quiz::get( $mapping['pressprimer_quiz_id'] );
		$broken->status = 'archived';
		$broken->save();
		$this->assertNotFalse( update_post_meta( $id, 'catalog_state', 'archived' ) );
		$this->assertSame( 'archived', get_post_meta( $id, 'catalog_state', true ) );
	}

	/** Classic error survives redirect and is escaped. */
	public function test_classic_error_survives_redirect_and_is_escaped(): void {
		$id        = wp_insert_post(
			array(
				'post_type'  => 'bfc_course',
				'post_title' => 'Draft',
			)
		);
		$validator = new Validator();
		$validator->guard_post(
			array(
				'post_type'   => 'bfc_course',
				'post_status' => 'publish',
			),
			array( 'ID' => $id )
		);
		$validator->redirect_notice( 'post.php' );
		ob_start();
		( new Validator() )->notice();
		$html = ob_get_clean();
		$this->assertStringContainsString( 'Obtain course approval', $html );
		$this->assertFalse( get_transient( 'burbank_course_notice_' . get_current_user_id() ) );
	}

	/** Identity cannot move during core direct publication boundary. */
	public function test_identity_cannot_move_during_core_direct_publication_boundary(): void {
		global $wpdb;
		$id = wp_insert_post(
			array(
				'post_type'  => 'bfc_course',
				'post_title' => 'Boundary',
				'meta_input' => array( 'bfc_code' => 'BFC-240' ),
			)
		);
		$wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => $id ) ); // phpcs:ignore WordPress.DB -- Reproduce core direct-publication boundary.
		$this->assertFalse( ( new Registry() )->reserve( $id, 'BFC-241' ) );
	}
}
