<?php
/**
 * Training access grant, suspend, and quiz-cap sync.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Access;

/** Owns learner training access state and PressPrimer take-quiz capability sync. */
class Learner {
	public const CAP_ACCESS = 'burbank_lms_access';

	public const CAP_TAKE_QUIZ = 'pressprimer_quiz_take_quiz';

	public const META_TRAINING_ACCESS = 'burbank_lms_training_access';

	public const META_SITE_INVITE_ID = 'burbank_lms_site_invite_id';

	public const META_SUSPENDED = 'burbank_lms_suspended';

	public const META_EMAIL_VERIFIED_AT = 'burbank_lms_email_verified_at';

	/**
	 * Grant training access and optionally record the redeeming site invite.
	 *
	 * @param int      $user_id        User ID.
	 * @param int|null $site_invite_id Site invite row ID when granted via redeem.
	 */
	public function grant_access( int $user_id, ?int $site_invite_id = null ): void {
		if ( $user_id < 1 ) {
			return;
		}
		$user = new \WP_User( $user_id );
		if ( ! $user->exists() ) {
			return;
		}
		$user->add_cap( self::CAP_ACCESS );
		update_user_meta( $user_id, self::META_TRAINING_ACCESS, '1' );
		if ( null !== $site_invite_id && $site_invite_id > 0 ) {
			update_user_meta( $user_id, self::META_SITE_INVITE_ID, $site_invite_id );
		}
		$this->sync_quiz_cap( $user_id );
	}

	/**
	 * Revoke training access and clear the meta mirror.
	 *
	 * @param int $user_id User ID.
	 */
	public function revoke_access( int $user_id ): void {
		if ( $user_id < 1 ) {
			return;
		}
		$user = new \WP_User( $user_id );
		if ( ! $user->exists() ) {
			return;
		}
		$user->remove_cap( self::CAP_ACCESS );
		delete_user_meta( $user_id, self::META_TRAINING_ACCESS );
		$this->sync_quiz_cap( $user_id );
	}

	/**
	 * Suspend a learner so enroll and quiz stay blocked.
	 *
	 * @param int $user_id User ID.
	 */
	public function suspend( int $user_id ): void {
		if ( $user_id < 1 ) {
			return;
		}
		update_user_meta( $user_id, self::META_SUSPENDED, '1' );
		$this->sync_quiz_cap( $user_id );
	}

	/**
	 * Clear suspension; quiz cap restores only when can_learn is true.
	 *
	 * @param int $user_id User ID.
	 */
	public function reactivate( int $user_id ): void {
		if ( $user_id < 1 ) {
			return;
		}
		delete_user_meta( $user_id, self::META_SUSPENDED );
		$this->sync_quiz_cap( $user_id );
	}

	/**
	 * Whether the user may enroll and take mapped quizzes.
	 *
	 * @param int $user_id User ID.
	 */
	public function can_learn( int $user_id ): bool {
		if ( $user_id < 1 ) {
			return false;
		}
		if ( ! user_can( $user_id, self::CAP_ACCESS ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Learner training access.
			return false;
		}
		if ( $this->is_suspended( $user_id ) ) {
			return false;
		}
		return $this->is_email_verified( $user_id );
	}

	/**
	 * Assign or remove pressprimer_quiz_take_quiz to match can_learn.
	 *
	 * PressPrimer may grant the cap on the subscriber role; {@see filter_quiz_cap}
	 * enforces the effective per-user gate. User-level add/remove keeps an explicit
	 * grant signal for users whose role does not already include the cap.
	 *
	 * @param int $user_id User ID.
	 */
	public function sync_quiz_cap( int $user_id ): void {
		if ( $user_id < 1 ) {
			return;
		}
		$user = new \WP_User( $user_id );
		if ( ! $user->exists() ) {
			return;
		}
		if ( $this->can_learn( $user_id ) ) {
			$user->add_cap( self::CAP_TAKE_QUIZ );
			return;
		}
		$user->remove_cap( self::CAP_TAKE_QUIZ );
	}

	/**
	 * Force pressprimer_quiz_take_quiz to follow can_learn, overriding role grants.
	 *
	 * @param array<string, bool> $allcaps All capabilities for the user.
	 * @param string[]            $caps    Primitive caps being checked.
	 * @param array<int, mixed>   $args    Arguments passed to current_user_can / user_can.
	 * @param \WP_User            $user    User object.
	 * @return array<string, bool>
	 */
	public function filter_quiz_cap( array $allcaps, array $caps, array $args, \WP_User $user ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- WordPress filter signature.
		// Only when this primitive is requested — subscriber allcaps always includes the role grant.
		if ( ! in_array( self::CAP_TAKE_QUIZ, $caps, true ) ) {
			return $allcaps;
		}
		$allcaps[ self::CAP_TAKE_QUIZ ] = $this->can_learn( (int) $user->ID );
		return $allcaps;
	}

	/**
	 * Whether the learner is currently suspended.
	 *
	 * @param int $user_id User ID.
	 */
	public function is_suspended( int $user_id ): bool {
		return '1' === (string) get_user_meta( $user_id, self::META_SUSPENDED, true );
	}

	/**
	 * Whether the learner has verified their email address.
	 *
	 * @param int $user_id User ID.
	 */
	public function is_email_verified( int $user_id ): bool {
		return '' !== (string) get_user_meta( $user_id, self::META_EMAIL_VERIFIED_AT, true );
	}
}
