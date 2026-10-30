<?php
/**
 * Released PressPrimer contracts.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Integrations;

/**
 * Fail closed outside the inspected release pair.
 *
 * Contracts inspected in the released artifacts (not documented elsewhere):
 *
 * - PressPrimer Quiz 3.1.3: `PressPrimer_Quiz_Quiz::get( $id )` (inherited from `PressPrimer_Quiz_Model`)
 *   returns a row object or null. `status` is `draft|published|archived`; the quiz table has no `deleted_at`.
 *   Certificate issuance receives the quiz ID through the `pressprimer_quiz_quiz_passed` action.
 * - PressPrimer Certificate 2.0.2: `PressPrimer_Certificate_Template::get( $id )` returns a non-soft-deleted row
 *   (`status` is `draft|published|archived`) or null. `PressPrimer_Certificate_Trigger::get_for_template( $id )`
 *   returns rows with `trigger_type`, `source_ref` and `is_active`. Its quiz adapter issues for the trigger type
 *   `ppq_quiz` using `Trigger::find_active( 'ppq_quiz', $quiz_id )`, which returns active rows (with `template_id`) on
 *   every template whose `source_ref` equals the quiz ID or the sentinel `any`, and issues for each; value-only types
 *   store NULL.
 */
class PressPrimer {
	/** Supported released Quiz plugin version. */
	public const QUIZ_VERSION = '3.1.3';

	/** Supported released Certificate plugin version. */
	public const CERTIFICATE_VERSION = '2.0.2';

	/** Certificate trigger type issued by the Quiz adapter. */
	public const TRIGGER_TYPE = 'ppq_quiz';

	/**
	 * Validate the real Quiz model and Certificate trigger rows.
	 *
	 * @param int $quiz_id Quiz row ID.
	 * @param int $template_id Template row ID.
	 */
	public function validate_mapping( int $quiz_id, int $template_id ): \WP_Error {
		$error = new \WP_Error();
		if ( ! $this->supported() ) {
			$error->add(
				'burbank_course_dependencies',
				sprintf(
					/* translators: 1: Quiz version, 2: Certificate version. */
					__( 'Activate the supported PressPrimer Quiz %1$s and PressPrimer Certificate %2$s releases before publishing.', 'burbank-lms' ),
					self::QUIZ_VERSION,
					self::CERTIFICATE_VERSION
				),
				array( 'status' => 400 )
			);
			return $error;
		}
		$quiz     = \PressPrimer_Quiz_Quiz::get( $quiz_id );
		$template = \PressPrimer_Certificate_Template::get( $template_id );
		if ( ! is_object( $quiz ) || 'published' !== ( $quiz->status ?? '' ) ) {
			$error->add( 'burbank_course_quiz', __( 'Select an existing published PressPrimer quiz.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		if ( ! is_object( $template ) || 'published' !== ( $template->status ?? '' ) ) {
			$error->add( 'burbank_course_certificate', __( 'Select an existing published certificate template.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		$matched = false;
		$broad   = false;
		$other   = false;
		foreach ( \PressPrimer_Certificate_Trigger::get_for_template( $template_id ) as $trigger ) {
			if ( self::TRIGGER_TYPE !== ( $trigger->trigger_type ?? '' ) || empty( $trigger->is_active ) ) {
				continue;
			}
			$ref = (string) ( $trigger->source_ref ?? '' );
			if ( '' === $ref || 'any' === $ref ) {
				$broad = true;
			} elseif ( (string) $quiz_id === $ref ) {
				$matched = true;
			} else {
				$other = true;
			}
		}
		if ( $broad ) {
			$error->add( 'burbank_course_any_quiz', __( 'Replace the broad any-quiz certificate trigger with this specific quiz.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		if ( $other ) {
			$error->add( 'burbank_course_trigger_mismatch', __( 'Remove the other active quiz triggers from this certificate template; it would issue this course certificate for a different quiz.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		if ( ! $matched ) {
			$error->add( 'burbank_course_trigger', __( 'Configure an active PressPrimer quiz-passed certificate trigger for the selected quiz and template.', 'burbank-lms' ), array( 'status' => 400 ) );
		}
		// Issuance fires every active trigger find_active() returns, on any template that get() still returns.
		$competing = array();
		foreach ( \PressPrimer_Certificate_Trigger::find_active( self::TRIGGER_TYPE, (string) $quiz_id ) as $trigger ) {
			$other = (int) ( $trigger->template_id ?? 0 );
			if ( $other !== $template_id && null !== \PressPrimer_Certificate_Template::get( $other ) ) {
				$competing[] = $other;
			}
		}
		if ( $competing ) {
			$error->add(
				'burbank_course_competing_trigger',
				sprintf(
					/* translators: %s: comma-separated certificate template IDs. */
					__( 'Deactivate the quiz-passed triggers on certificate templates %s; they would also issue a certificate for this course quiz, either for this quiz or for any quiz.', 'burbank-lms' ),
					implode( ', ', array_unique( $competing ) )
				),
				array( 'status' => 400 )
			);
		}
		return $error;
	}

	/** Whether both plugins are the pinned releases and expose the inspected public contracts. */
	private function supported(): bool {
		$versions = $this->installed_versions();
		if ( self::QUIZ_VERSION !== ( $versions['quiz'] ?? null ) || self::CERTIFICATE_VERSION !== ( $versions['certificate'] ?? null ) ) {
			return false;
		}
		foreach ( $this->contract_methods() as $class => $methods ) {
			if ( ! class_exists( $class ) ) {
				return false;
			}
			foreach ( $methods as $method => $parameters ) {
				if ( ! method_exists( $class, $method ) ) {
					return false;
				}
				$contract = new \ReflectionMethod( $class, $method );
				if ( ! $contract->isPublic() || ! $contract->isStatic() || $parameters !== $contract->getNumberOfParameters() ) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Installed plugin versions, or null when a plugin is not loaded.
	 *
	 * @return array{quiz: string|null, certificate: string|null}
	 */
	protected function installed_versions(): array {
		return array(
			'quiz'        => defined( 'PRESSPRIMER_QUIZ_VERSION' ) ? (string) constant( 'PRESSPRIMER_QUIZ_VERSION' ) : null,
			'certificate' => defined( 'PPCERT_VERSION' ) ? (string) constant( 'PPCERT_VERSION' ) : null,
		);
	}

	/**
	 * Public static methods this adapter calls and their released parameter counts, keyed by class.
	 *
	 * @return array<string,array<string,int>>
	 */
	protected function contract_methods(): array {
		return array(
			'PressPrimer_Quiz_Quiz'            => array( 'get' => 1 ),
			'PressPrimer_Certificate_Template' => array( 'get' => 1 ),
			'PressPrimer_Certificate_Trigger'  => array(
				'get_for_template' => 1,
				'find_active'      => 2,
			),
		);
	}
}
