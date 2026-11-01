<?php
/** Branded transactional email.
 *
 * @package Burbank_LMS
 */

namespace Burbank_LMS\Mail;

/** HTML layout with a scoped PHPMailer plain-text alternative. */
class Template {
	/** Wrap escaped message copy in a shared layout.
	 *
	 * @param string $title Message title.
	 * @param string $body_html Sanitized body HTML.
	 */
	public static function wrap( string $title, string $body_html ): string {
		return '<!doctype html><html><body style="margin:0;background:#f3f5f4;color:#172820;font-family:Arial,sans-serif"><div style="max-width:600px;margin:24px auto;padding:32px;background:#ffffff;border:1px solid #d5ded8;border-radius:16px"><p style="font-weight:bold;color:#315a46">Burbank LMS</p><h1 style="font-size:24px">' . esc_html( $title ) . '</h1>' . wp_kses_post( $body_html ) . '<hr><p>' . esc_html__( 'Training · Burbank LMS', 'burbank-lms' ) . '</p></div></body></html>';
	}

	/** Send HTML while passing its alternative through a private removable header.
	 *
	 * @param string               $to Recipient.
	 * @param string               $subject Subject.
	 * @param string               $message Filtered plain message.
	 * @param array<string>|string $headers Existing headers.
	 */
	public static function send( string $to, string $subject, string $message, array|string $headers = array() ): bool {
		$headers   = is_array( $headers ) ? $headers : explode( "\n", $headers );
		$headers   = array_values( array_filter( $headers, static fn( $header ) => ! preg_match( '/^(content-type|x-burbank-lms-plain):/i', trim( $header ) ) ) );
		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'X-Burbank-LMS-Plain: ' . base64_encode( $message ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Encodes plaintext for a newline-safe private MIME transport header, removed before sending.
		return wp_mail( $to, $subject, self::wrap( $subject, wpautop( make_clickable( esc_html( $message ) ) ) ), $headers );
	}

	/** Set alternatives only for marked LMS messages and remove the private header.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $mailer Mailer.
	 */
	public function configure_alternative( $mailer ): void {
		foreach ( $mailer->getCustomHeaders() as $header ) {
			if ( 'X-Burbank-LMS-Plain' === $header[0] ) {
				$mailer->AltBody = (string) base64_decode( $header[1], true ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase,WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- PHPMailer API; decode the private plaintext transport header, removed immediately below.
				$mailer->clearCustomHeader( $header[0] );
				return;
			}
		}
	}
}
