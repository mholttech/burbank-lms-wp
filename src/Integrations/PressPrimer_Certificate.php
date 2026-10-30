<?php
/**
 * PressPrimer Certificate merge fields and completion adapters.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Integrations;

use Burbank_LMS\Access\Profile;
use Burbank_LMS\Enrollment\Service as Enrollment_Service;
use Burbank_LMS\Progress\Service as Progress_Service;

/**
 * Supplies Burbank merge tokens, gates issuance, and completes enrollments.
 *
 * Unmapped quizzes/templates (no published `bfc_course`) are left unchanged.
 */
class PressPrimer_Certificate {
	/** Merge field group key in the PressPrimer registry. */
	private const GROUP = 'bfc';

	/**
	 * Optional collaborators for tests; production uses lazy defaults.
	 *
	 * @param Progress_Service|null   $progress   Progress service.
	 * @param Enrollment_Service|null $enrollment Enrollment service.
	 * @param Profile|null            $profile    Learner profile service.
	 */
	public function __construct(
		private ?Progress_Service $progress = null,
		private ?Enrollment_Service $enrollment = null,
		private ?Profile $profile = null,
	) {}

	/**
	 * Register Burbank course/learner merge fields for the designer and issuer.
	 *
	 * @param mixed  $fields  Existing merge field registry.
	 * @param string $context 'designer' or 'issue'.
	 * @return array<string, mixed>
	 */
	public function register_merge_fields( $fields, $context = 'designer' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- PressPrimer filter signature.
		$fields                = is_array( $fields ) ? $fields : array();
		$fields[ self::GROUP ] = array_merge(
			isset( $fields[ self::GROUP ] ) && is_array( $fields[ self::GROUP ] ) ? $fields[ self::GROUP ] : array(),
			array(
				'legal_name'              => $this->field( 'bfc.legal_name', __( 'Legal / certificate name', 'burbank-lms' ), 'Jamie Learner' ),
				'bfc_code'                => $this->field( 'bfc.bfc_code', __( 'BFC code', 'burbank-lms' ), 'BFC-201' ),
				'title'                   => $this->field( 'bfc.title', __( 'Course title', 'burbank-lms' ), __( 'Scene safety', 'burbank-lms' ) ),
				'course_revision'         => $this->field( 'bfc.course_revision', __( 'Course revision', 'burbank-lms' ), '1.0' ),
				'course_outcome'          => $this->field( 'bfc.course_outcome', __( 'Course outcome', 'burbank-lms' ), 'independent_study' ),
				'subject_category'        => $this->field( 'bfc.subject_category', __( 'Subject category', 'burbank-lms' ), 'safety_field_awareness' ),
				'matrix_requirement_slug' => $this->field( 'bfc.matrix_requirement_slug', __( 'Matrix requirement slug', 'burbank-lms' ), 'req-scene-safety' ),
			)
		);
		return $fields;
	}

	/**
	 * Inject Burbank merge values at certificate render time (never email).
	 *
	 * @param mixed $values  Resolved merge values.
	 * @param mixed $context Issuance context.
	 * @return array<string, string>
	 */
	public function merge_data( $values, $context ) {
		$values    = is_array( $values ) ? $values : array();
		$course_id = $this->course_id_from_context( $context );
		if ( null === $course_id || ! is_array( $context ) ) {
			return $values;
		}
		$recipient_id                          = isset( $context['recipient_id'] ) ? (int) $context['recipient_id'] : 0;
		$course                                = get_post( $course_id );
		$values['bfc.legal_name']              = $recipient_id > 0 ? $this->profile()->get_legal_name( $recipient_id ) : '';
		$values['bfc.bfc_code']                = (string) get_post_meta( $course_id, 'bfc_code', true );
		$values['bfc.title']                   = $course ? (string) $course->post_title : '';
		$values['bfc.course_revision']         = (string) get_post_meta( $course_id, 'course_revision', true );
		$values['bfc.course_outcome']          = (string) get_post_meta( $course_id, 'course_outcome', true );
		$values['bfc.subject_category']        = (string) get_post_meta( $course_id, 'subject_category', true );
		$values['bfc.matrix_requirement_slug'] = (string) get_post_meta( $course_id, 'matrix_requirement_slug', true );
		return $values;
	}

	/**
	 * Require active enrollment + quiz pass before issuing for a mapped course.
	 *
	 * @param mixed $validation Prior validation result.
	 * @param mixed $context    Issuance context.
	 * @return true|\WP_Error
	 */
	public function issue_validation( $validation, $context ) {
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		$course_id = $this->course_id_from_context( $context );
		if ( null === $course_id || ! is_array( $context ) ) {
			return true;
		}
		$recipient_id = isset( $context['recipient_id'] ) ? (int) $context['recipient_id'] : 0;
		$row          = $recipient_id > 0 ? $this->enrollment()->get_active( $recipient_id, $course_id ) : null;
		if ( ! $row ) {
			return new \WP_Error(
				'burbank_certificate_enrollment',
				__( 'The learner must have an active enrollment in this course before a certificate can be issued.', 'burbank-lms' )
			);
		}
		if ( empty( $row->quiz_passed_at ) ) {
			return new \WP_Error(
				'burbank_certificate_quiz_pass',
				__( 'The learner must pass the course quiz before a certificate can be issued.', 'burbank-lms' )
			);
		}
		return true;
	}

	/**
	 * Complete the enrollment after PressPrimer issues a certificate.
	 *
	 * @param mixed $certificate_id Certificate row ID.
	 * @param mixed $context        Issuance context.
	 */
	public function certificate_issued( $certificate_id, $context ): void {
		$course_id = $this->course_id_from_context( $context );
		if ( null === $course_id || ! is_array( $context ) ) {
			return;
		}
		$recipient_id = isset( $context['recipient_id'] ) ? (int) $context['recipient_id'] : 0;
		if ( $recipient_id < 1 ) {
			return;
		}
		$this->progress()->complete_with_credential( $recipient_id, $course_id, (int) $certificate_id );
	}

	/**
	 * Resolve the published course for a quiz-sourced issuance context.
	 *
	 * @param mixed $context Issuance context from PressPrimer.
	 */
	private function course_id_from_context( $context ): ?int {
		if ( ! is_array( $context ) ) {
			return null;
		}
		$source_type = (string) ( $context['source_type'] ?? $context['trigger_type'] ?? '' );
		if ( 'ppq_quiz' !== $source_type ) {
			return null;
		}
		$quiz_id = isset( $context['source_ref'] ) ? (int) $context['source_ref'] : 0;
		if ( $quiz_id < 1 ) {
			return null;
		}
		$ids = get_posts(
			array(
				'post_type'              => 'bfc_course',
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single quiz→course lookup.
					array(
						'key'   => 'pressprimer_quiz_id',
						'value' => (string) $quiz_id,
					),
				),
			)
		);
		return $ids ? (int) $ids[0] : null;
	}

	/**
	 * Build a PressPrimer merge field definition.
	 *
	 * @param string $key    Token key.
	 * @param string $label  Designer label.
	 * @param string $sample Sample value for the designer palette.
	 * @return array<string, mixed>
	 */
	private function field( string $key, string $label, string $sample ): array {
		return array(
			'key'      => $key,
			'label'    => $label,
			'sample'   => $sample,
			'resolver' => static function (): string {
				return '';
			},
		);
	}

	/** Lazy progress service. */
	private function progress(): Progress_Service {
		return $this->progress ??= new Progress_Service();
	}

	/** Lazy enrollment service. */
	private function enrollment(): Enrollment_Service {
		return $this->enrollment ??= new Enrollment_Service();
	}

	/** Lazy profile service. */
	private function profile(): Profile {
		return $this->profile ??= new Profile();
	}
}
