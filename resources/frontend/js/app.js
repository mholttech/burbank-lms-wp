/**
 * All of the code for your public-facing JavaScript source
 * should reside in this file.
 *
 * jQuery is available via global aliases ($ and jQuery).
 * Example: $(document).ready(function() { ... });
 */

import { __ } from '@wordpress/i18n';

document.addEventListener( 'click', async ( event ) => {
	const button = event.target.closest( '[data-bfc-resend-verify]' );
	if ( button ) {
		await verifyRequest(
			button.closest( '[data-bfc-verify]' ),
			'resend-verification',
			{}
		);
	}
} );
document.addEventListener( 'submit', async ( event ) => {
	if ( ! event.target.matches( '[data-bfc-change-email]' ) ) {
		return;
	}
	event.preventDefault();
	await verifyRequest( event.target.closest( '[data-bfc-verify]' ), 'email', {
		email: new FormData( event.target ).get( 'email' ),
	} );
} );
async function verifyRequest( panel, action, body ) {
	const config = JSON.parse( panel.dataset.bfcRestConfig );
	const status = panel.querySelector( '[role="status"]' );
	try {
		const response = await fetch( config.restUrl + '/profile/' + action, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': config.nonce,
			},
			body: JSON.stringify( body ),
		} );
		const result = await response.json();
		if ( result.email ) {
			panel.querySelector( '[data-bfc-verify-email]' ).textContent =
				result.email;
		}
		if ( ! response.ok || ! result.sent ) {
			throw new Error(
				result.message ||
					__(
						'Email could not be sent. Please try again.',
						'burbank-lms'
					)
			);
		}
		if ( body.email ) {
			panel.querySelector( '[data-bfc-verify-email]' ).textContent =
				body.email;
		}
		status.textContent = __(
			'Check your email for a verification link.',
			'burbank-lms'
		);
	} catch ( error ) {
		status.textContent = error.message;
	}
}
