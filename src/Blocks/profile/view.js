/**
 * Profile details (WP name fields + legal-name route) and security form.
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

document.querySelectorAll( '.bfc-profile' ).forEach( ( root ) => {
	const form = root.querySelector( '.bfc-profile__form' );
	if ( ! form ) {
		return;
	}
	const status = root.querySelector( '[data-bfc-status]' );
	const config = parseConfig( root );
	const displaySelect = form.querySelector( '[name="display_name"]' );
	const certNameEl = form.querySelector( '[data-bfc-cert-name]' );
	const firstInput = form.querySelector( '[name="first_name"]' );
	const lastInput = form.querySelector( '[name="last_name"]' );
	const nicknameInput = form.querySelector( '[name="nickname"]' );
	const emailInput = form.querySelector( '[name="email"]' );
	const usernameEl = root.querySelector( '.bfc-profile__username' );
	const username =
		form.dataset.bfcUsername ||
		( usernameEl && usernameEl.textContent
			? usernameEl.textContent.trim()
			: '' );

	const composedName = () =>
		[
			firstInput ? firstInput.value.trim() : '',
			lastInput ? lastInput.value.trim() : '',
		]
			.filter( Boolean )
			.join( ' ' );

	const rebuildDisplayOptions = () => {
		if ( ! displaySelect ) {
			return;
		}
		const first = firstInput ? firstInput.value.trim() : '';
		const last = lastInput ? lastInput.value.trim() : '';
		const nickname = nicknameInput ? nicknameInput.value.trim() : '';
		const candidates = [
			username,
			first,
			last,
			[ first, last ].filter( Boolean ).join( ' ' ),
			[ last, first ].filter( Boolean ).join( ' ' ),
			nickname,
		];
		const options = [];
		candidates.forEach( ( candidate ) => {
			const value = String( candidate || '' ).trim();
			if ( value && ! options.includes( value ) ) {
				options.push( value );
			}
		} );
		const previous = displaySelect.value;
		displaySelect.replaceChildren();
		options.forEach( ( value ) => {
			const option = document.createElement( 'option' );
			option.value = value;
			option.textContent = value;
			displaySelect.append( option );
		} );
		if ( options.includes( previous ) ) {
			displaySelect.value = previous;
		} else {
			const composed = [ first, last ].filter( Boolean ).join( ' ' );
			displaySelect.value = options.includes( composed )
				? composed
				: options[ 0 ] || '';
		}
	};

	const updateCertPreview = () => {
		if ( ! certNameEl ) {
			return;
		}
		certNameEl.textContent =
			composedName() || form.dataset.bfcLegalName || '';
	};

	[ firstInput, lastInput, nicknameInput ].forEach( ( input ) => {
		if ( ! input ) {
			return;
		}
		input.addEventListener( 'input', () => {
			rebuildDisplayOptions();
			updateCertPreview();
		} );
	} );

	let personalBusy = false;
	const personalSubmit = form.querySelector( 'button[type="submit"]' );
	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		if ( personalBusy ) {
			return;
		}
		setStatus( status, '' );
		const first = firstInput ? firstInput.value.trim() : '';
		const last = lastInput ? lastInput.value.trim() : '';
		if ( ! first || ! last ) {
			setStatus( status, 'First and last name are required.', true );
			return;
		}
		const body = {
			/* eslint-disable camelcase -- WP users/me REST field names. */
			first_name: first,
			last_name: last,
			/* eslint-enable camelcase */
			nickname: nicknameInput ? nicknameInput.value.trim() : '',
			name: displaySelect ? displaySelect.value : '',
			email: emailInput ? emailInput.value.trim() : '',
		};
		personalBusy = true;
		if ( personalSubmit ) {
			personalSubmit.disabled = true;
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

			const nextLegal = composedName();
			if ( nextLegal && nextLegal !== form.dataset.bfcLegalName ) {
				const legalResponse = await fetch(
					`${ config.restUrl }/profile/legal-name`,
					{
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'X-WP-Nonce': config.nonce || '',
						},
						body: JSON.stringify( {
							/* eslint-disable-next-line camelcase -- Plugin REST field. */
							legal_name: nextLegal,
						} ),
					}
				);
				const legalPayload = await legalResponse
					.json()
					.catch( () => ( {} ) );
				if ( ! legalResponse.ok ) {
					setStatus(
						status,
						'Profile saved, but your legal name was not updated.',
						true
					);
					return;
				}
				form.dataset.bfcLegalName =
					legalPayload.legal_name || nextLegal;
			}

			setStatus( status, 'Profile saved.', false );
		} catch {
			setStatus(
				status,
				'Profile update failed. Please try again.',
				true
			);
		} finally {
			personalBusy = false;
			if ( personalSubmit ) {
				personalSubmit.disabled = false;
			}
		}
	} );

	const securityForm = root.querySelector( '.bfc-profile__security-form' );
	if ( ! securityForm ) {
		return;
	}
	const securityStatus = root.querySelector( '[data-bfc-security-status]' );
	const passwordInput = securityForm.querySelector( '[name="password"]' );
	const confirmInput = securityForm.querySelector(
		'[name="password_confirm"]'
	);
	const meter = securityForm.querySelector( '[data-bfc-meter]' );
	const meterBar = securityForm.querySelector( '[data-bfc-meter-bar]' );
	const meterLabel = securityForm.querySelector( '[data-bfc-meter-label]' );
	const strengthLabels = [
		'Very weak',
		'Weak',
		'Medium',
		'Strong',
		'Very strong',
	];

	const updateMeter = () => {
		const password = passwordInput ? passwordInput.value : '';
		const hasMeter =
			window.wp &&
			window.wp.passwordStrength &&
			typeof window.wp.passwordStrength.meter === 'function';
		if ( ! password || ! hasMeter ) {
			if ( meter ) {
				meter.hidden = true;
			}
			if ( meterLabel ) {
				meterLabel.hidden = true;
			}
			return;
		}
		const score = window.wp.passwordStrength.meter(
			password,
			[],
			[ emailInput ? emailInput.value : '' ]
		);
		if ( score < 0 || score > 4 ) {
			if ( meter ) {
				meter.hidden = true;
			}
			if ( meterLabel ) {
				meterLabel.hidden = true;
			}
			return;
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
	};

	if ( passwordInput ) {
		passwordInput.addEventListener( 'input', updateMeter );
	}

	let securityBusy = false;
	const securitySubmit = securityForm.querySelector(
		'button[type="submit"]'
	);
	securityForm.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		if ( securityBusy ) {
			return;
		}
		setStatus( securityStatus, '' );
		const password = passwordInput ? passwordInput.value : '';
		const confirm = confirmInput ? confirmInput.value : '';
		if ( ! password && ! confirm ) {
			setStatus( securityStatus, 'Enter a new password first.', true );
			return;
		}
		// nosemgrep: gitlab.eslint.detect-possible-timing-attacks -- browser UX comparing two user-typed fields before POST; not a secret-store check.
		if ( password !== confirm ) {
			setStatus( securityStatus, 'Passwords do not match.', true );
			return;
		}
		securityBusy = true;
		if ( securitySubmit ) {
			securitySubmit.disabled = true;
		}
		try {
			const response = await fetch( config.userUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': config.nonce || '',
				},
				body: JSON.stringify( { password } ),
			} );
			const payload = await response.json().catch( () => ( {} ) );
			if ( ! response.ok ) {
				setStatus(
					securityStatus,
					payload.message || 'Password change failed.',
					true
				);
				return;
			}
			if ( passwordInput ) {
				passwordInput.value = '';
			}
			if ( confirmInput ) {
				confirmInput.value = '';
			}
			updateMeter();
			setStatus( securityStatus, 'Password changed.', false );
		} catch {
			setStatus(
				securityStatus,
				'Password change failed. Please try again.',
				true
			);
		} finally {
			securityBusy = false;
			if ( securitySubmit ) {
				securitySubmit.disabled = false;
			}
		}
	} );

	const signOutButton = securityForm.querySelector(
		'[data-bfc-signout-others]'
	);
	if ( ! signOutButton ) {
		return;
	}
	signOutButton.addEventListener( 'click', async () => {
		setStatus( securityStatus, '' );
		try {
			const response = await fetch(
				`${ config.restUrl }/profile/sign-out-others`,
				{
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce || '',
					},
				}
			);
			const payload = await response.json().catch( () => ( {} ) );
			if ( ! response.ok ) {
				setStatus(
					securityStatus,
					payload.message || 'Could not sign out other sessions.',
					true
				);
				return;
			}
			setStatus( securityStatus, 'Other sessions signed out.', false );
		} catch {
			setStatus(
				securityStatus,
				'Could not sign out other sessions. Please try again.',
				true
			);
		}
	} );
} );
