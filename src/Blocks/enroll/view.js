/**
 * Standalone enroll form → POST /burbank-lms/v1/enrollments.
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

document.querySelectorAll( '.bfc-enroll' ).forEach( ( root ) => {
	const form = root.querySelector( '.bfc-enroll__form' );
	if ( ! form ) {
		return;
	}
	const config = parseConfig( root );
	const status = root.querySelector( '[data-bfc-status]' );
	const select = root.querySelector( '[data-bfc-course-select]' );
	const inviteWrap = root.querySelector( '[data-bfc-invite-wrap]' );

	const syncInvite = () => {
		if ( ! select || ! inviteWrap ) {
			return;
		}
		const option = select.selectedOptions[ 0 ];
		const restricted = option?.getAttribute( 'data-restricted' ) === '1';
		inviteWrap.hidden = ! restricted;
	};
	select?.addEventListener( 'change', syncInvite );
	syncInvite();

	form.addEventListener( 'submit', async ( event ) => {
		event.preventDefault();
		const submittedUrl = select?.selectedOptions[ 0 ]?.dataset.courseUrl;
		setStatus( status, '' );
		const data = Object.fromEntries( new FormData( form ).entries() );
		/* eslint-disable camelcase -- REST contract field names. */
		const body = {
			course_id: Number( data.course_id ),
		};
		if ( data.course_invite ) {
			body.course_invite = data.course_invite;
		}
		/* eslint-enable camelcase */
		try {
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
			if ( ! response.ok ) {
				setStatus(
					status,
					payload.message || 'Enrollment failed.',
					true
				);
				return;
			}
			setStatus( status, 'Enrolled successfully.', false );
			const badge = root.querySelector( '.bfc-enroll__badge' );
			if ( badge ) {
				badge.textContent = 'Enrolled';
			}
			if ( submittedUrl ) {
				const link = document.createElement( 'a' );
				link.href = submittedUrl;
				link.className = 'bfc-enroll__action wp-element-button';
				link.textContent = 'Continue course';
				form.replaceWith( link );
			}
		} catch {
			setStatus( status, 'Enrollment failed. Please try again.', true );
		}
	} );
} );
