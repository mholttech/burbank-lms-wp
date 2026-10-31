<?php
/**
 * Learner presentation label and enrollment status coverage.
 *
 * @package Burbank_LMS
 */

/** Exercises Presentation slug labels and enrollment status projection. */
class LearnerPresentationTest extends WP_UnitTestCase {
	/** Humanize underscore slugs for display. */
	public function test_label_humanizes_slug(): void {
		$p = new \Burbank_LMS\Learner\Presentation();
		$this->assertSame( 'Independent study', $p->label( 'independent_study' ) );
		$this->assertSame( 'Communications', $p->label( 'communications' ) );
	}

	/** Map enrollment rows to learner-facing status tokens. */
	public function test_enrollment_status_projection(): void {
		$p = new \Burbank_LMS\Learner\Presentation();
		$this->assertSame( 'none', $p->enrollment_status( null ) );
		$this->assertSame(
			'enrolled',
			$p->enrollment_status(
				(object) array(
					'status'         => 'active',
					'quiz_passed_at' => null,
				)
			)
		);
		$this->assertSame(
			'passed',
			$p->enrollment_status(
				(object) array(
					'status'         => 'active',
					'quiz_passed_at' => '2026-01-01 00:00:00',
				)
			)
		);
		$this->assertSame(
			'completed',
			$p->enrollment_status(
				(object) array(
					'status'         => 'completed',
					'quiz_passed_at' => '2026-01-01 00:00:00',
				)
			)
		);
		$this->assertSame(
			'withdrawn',
			$p->enrollment_status(
				(object) array(
					'status'         => 'withdrawn',
					'quiz_passed_at' => null,
				)
			)
		);
	}
}
