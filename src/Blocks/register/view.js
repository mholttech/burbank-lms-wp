/**
 * Front-end registration form → POST /burbank-lms/v1/register.
 */

function parseConfig( root ) {
	try {
		return JSON.parse( root.getAttribute( 'data-bfc-rest-config' ) || '{}' );
	} catch ( error ) {
		return {};
	}
}

function setStatus( el, message, isError ) {
	if ( ! el ) {
		return;
	}
	el.hidden = ! message;
	el.textContent = message || '';
	el.classList.toggle( 'is-error', Boolean( isError ) );
}

document.querySelectorAll( '.bfc-register' ).forEach( ( root ) => {
	const form = root.querySelector( '.bfc-register__form' );
	const status = root.querySelector( '[data-bfc-status]' );
	if ( ! form ) {
		return;
	}
	const config = parseConfig( root );

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		setStatus( status, '' );
		const data = Object.fromEntries( new FormData( form ).entries() );
		try {
			const response = await fetch( `${ config.restUrl }/register`, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce || '',
				},
				body: JSON.stringify( data ),
			} );
			const payload = await response.json().catch( () => ( {} ) );
			if ( ! response.ok ) {
				setStatus(
					status,
					payload.message ||
						payload?.data?.params ||
						'Registration failed.',
					true
				);
				return;
			}
			form.reset();
			setStatus(
				status,
				'Account created. Check your email to verify before enrolling.',
				false
			);
		} catch ( error ) {
			setStatus( status, 'Registration failed. Please try again.', true );
		}
	} );
} );
