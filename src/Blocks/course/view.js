/**
 * Course open-enroll and invite redeem → POST /burbank-lms/v1/enrollments.
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

async function enroll( config, body ) {
	const response = await fetch( `${ config.restUrl }/enrollments`, {
		method: 'POST',
		credentials: 'same-origin',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce || '',
		},
		body: JSON.stringify( body ),
	} );
	const payload = await response.json().catch( () => ( {} ) );
	return { response, payload };
}

document.querySelectorAll( '.bfc-course' ).forEach( ( root ) => {
	const status = root.querySelector( '[data-bfc-status]' );
	const config = parseConfig( root );
	const courseId = Number(
		root.getAttribute( 'data-course-id' ) ||
			root.querySelector( '[data-bfc-enroll]' )?.getAttribute( 'data-bfc-enroll' )
	);

	const openButton = root.querySelector( '[data-bfc-enroll]' );
	if ( openButton ) {
		openButton.addEventListener( 'click', async () => {
			setStatus( status, '' );
			openButton.disabled = true;
			try {
				const { response, payload } = await enroll( config, {
					course_id: courseId,
				} );
				if ( ! response.ok ) {
					setStatus(
						status,
						payload.message || 'Enrollment failed.',
						true
					);
					openButton.disabled = false;
					return;
				}
				window.location.reload();
			} catch ( error ) {
				setStatus( status, 'Enrollment failed. Please try again.', true );
				openButton.disabled = false;
			}
		} );
	}

	const inviteForm = root.querySelector( '.bfc-course__invite-form' );
	if ( inviteForm ) {
		inviteForm.addEventListener( 'submit', async ( event ) => {
			event.preventDefault();
			setStatus( status, '' );
			const data = Object.fromEntries( new FormData( inviteForm ).entries() );
			try {
				const { response, payload } = await enroll( config, {
					course_id: courseId,
					course_invite: data.course_invite || '',
				} );
				if ( ! response.ok ) {
					setStatus(
						status,
						payload.message || 'Enrollment failed.',
						true
					);
					return;
				}
				window.location.reload();
			} catch ( error ) {
				setStatus( status, 'Enrollment failed. Please try again.', true );
			}
		} );
	}
} );
