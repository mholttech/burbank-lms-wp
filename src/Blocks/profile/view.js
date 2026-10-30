/**
 * Profile updates via wp/v2/users/me (legal name stays read-only server-side).
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

document.querySelectorAll( '.bfc-profile' ).forEach( ( root ) => {
	const form = root.querySelector( '.bfc-profile__form' );
	const status = root.querySelector( '[data-bfc-status]' );
	if ( ! form ) {
		return;
	}
	const config = parseConfig( root );

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		setStatus( status, '' );
		const raw = Object.fromEntries( new FormData( form ).entries() );
		const body = {
			name: raw.display_name,
			email: raw.email,
		};
		if ( raw.password ) {
			body.password = raw.password;
		}
		try {
			const response = await fetch( config.userUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce || '',
				},
				body: JSON.stringify( body ),
			} );
			const payload = await response.json().catch( () => ( {} ) );
			if ( ! response.ok ) {
				setStatus(
					status,
					payload.message || 'Profile update failed.',
					true
				);
				return;
			}
			const passwordField = form.querySelector( '[name="password"]' );
			if ( passwordField ) {
				passwordField.value = '';
			}
			setStatus( status, 'Profile saved.', false );
		} catch ( error ) {
			setStatus( status, 'Profile update failed. Please try again.', true );
		}
	} );
} );
