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
	if ( message && isError ) {
		el.focus();
	}
}

function passwordScore( password, email ) {
	const meter =
		window.wp &&
		window.wp.passwordStrength &&
		typeof window.wp.passwordStrength.meter === 'function';
	if ( ! meter ) {
		return password.length >= 8 ? 3 : 1;
	}
	const score = window.wp.passwordStrength.meter(
		password,
		[],
		email ? [ email ] : []
	);
	if ( score < 0 ) {
		return password.length >= 8 ? 3 : 1;
	}
	return score;
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
	const email = form.querySelector( '[name="email"]' );
	const reveal = form.querySelector( '.bfc-register__reveal' );
	const meter = form.querySelector( '[data-bfc-meter]' );
	const meterBar = form.querySelector( '[data-bfc-meter-bar]' );
	const meterLabel = form.querySelector( '[data-bfc-meter-label]' );
	const strengthLabels = [
		'Very weak',
		'Weak',
		'Medium',
		'Strong',
		'Very strong',
	];
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

	const updateMeter = () => {
		const value = password ? password.value : '';
		const score = passwordScore( value, email ? email.value : '' );
		if ( ! value || score < 0 || score > 4 ) {
			if ( meter ) {
				meter.hidden = true;
			}
			if ( meterLabel ) {
				meterLabel.hidden = true;
			}
			return score;
		}
		if ( meterBar ) {
			meterBar.style.width = `${ ( ( score + 1 ) / 5 ) * 100 }%`;
		}
		if ( meter ) {
			meter.hidden = false;
			meter.dataset.score = String( score );
		}
		if ( meterLabel ) {
			meterLabel.hidden = false;
			meterLabel.textContent = strengthLabels[ score ] || '';
		}
		return score;
	};
	password?.addEventListener( 'input', updateMeter );
	email?.addEventListener( 'input', updateMeter );

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
		const score = updateMeter();
		if ( score < 3 ) {
			setStatus(
				status,
				'Use a stronger password. Aim for a mix of words, numbers, and symbols.',
				true
			);
			password?.focus();
			return;
		}
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
				done.querySelector( '[data-bfc-done-title]' )?.focus();
			}
		} catch {
			setStatus( status, 'Registration failed. Please try again.', true );
			if ( submit ) {
				submit.disabled = false;
			}
		}
	} );
} );
