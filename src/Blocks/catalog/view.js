/**
 * Catalog open-enroll CTAs → POST /burbank-lms/v1/enrollments.
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

document.querySelectorAll( '.bfc-catalog' ).forEach( ( root ) => {
	const status = root.querySelector( '[data-bfc-status]' );
	const config = parseConfig( root );

	root.querySelectorAll( '[data-bfc-enroll]' ).forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			const courseId = Number( button.getAttribute( 'data-bfc-enroll' ) );
			setStatus( status, '' );
			button.disabled = true;
			try {
				const response = await fetch( `${ config.restUrl }/enrollments`, {
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': config.nonce || '',
					},
					body: JSON.stringify( { course_id: courseId } ),
				} );
				const payload = await response.json().catch( () => ( {} ) );
				if ( ! response.ok ) {
					setStatus(
						status,
						payload.message || 'Enrollment failed.',
						true
					);
					button.disabled = false;
					return;
				}
				button.replaceWith(
					Object.assign( document.createElement( 'p' ), {
						className: 'bfc-catalog__enrolled',
						textContent: 'Enrolled',
					} )
				);
				setStatus( status, 'Enrolled successfully.', false );
			} catch ( error ) {
				setStatus( status, 'Enrollment failed. Please try again.', true );
				button.disabled = false;
			}
		} );
	} );
} );
