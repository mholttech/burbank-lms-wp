/**
 * Front-end registration form → POST /burbank-lms/v1/register.
 */

function parseConfig( root ) {
	try {
		return JSON.parse(
			root.getAttribute( 'data-bfc-rest-config' ) || '{}'
		);
	} catch {
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
	if ( ! form ) {
		return;
	}
	const status = root.querySelector( '[data-bfc-status]' );
	const done = root.querySelector( '[data-bfc-done]' );
	const submit = form.querySelector( '[data-bfc-submit]' );
	const config = parseConfig( root );
	const first = form.querySelector( '[name="first_name"]' );
	const last = form.querySelector( '[name="last_name"]' );
	const display = form.querySelector( '[name="display_name"]' );
	const certificate = root.querySelector( '[data-bfc-cert-name]' );
	const password = form.querySelector( '[name="password"]' );
	const reveal = form.querySelector( '.bfc-register__reveal' );
	let displayEdited = Boolean( display && display.value );

	const fillNames = () => {
		const parts = [ first?.value || '', last?.value || '' ]
			.map( ( part ) => part.trim() )
			.filter( Boolean );
		const legal = parts.join( ' ' );
		if ( certificate ) {
			certificate.textContent = legal;
		}
		if ( ! displayEdited && display ) {
			display.value = legal;
		}
	};
	display?.addEventListener( 'input', () => {
		displayEdited = true;
	} );
	first?.addEventListener( 'input', fillNames );
	last?.addEventListener( 'input', fillNames );
	fillNames();

	reveal?.addEventListener( 'click', () => {
		if ( ! password ) {
			return;
		}
		const showing = password.type === 'text';
		password.type = showing ? 'password' : 'text';
		reveal.textContent = showing ? 'Show' : 'Hide';
		reveal.setAttribute( 'aria-pressed', showing ? 'false' : 'true' );
	} );

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		setStatus( status, '' );
		if ( submit ) {
			submit.disabled = true;
		}
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
					payload.message || 'Registration failed.',
					true
				);
				if ( submit ) {
					submit.disabled = false;
				}
				return;
			}
			form.hidden = true;
			if ( done ) {
				done.hidden = false;
			}
		} catch {
			setStatus( status, 'Registration failed. Please try again.', true );
			if ( submit ) {
				submit.disabled = false;
			}
		}
	} );
} );
