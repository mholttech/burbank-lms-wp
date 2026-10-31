/**
 * My Courses list + withdraw via burbank-lms/v1 enrollments REST.
 * Catalog CTA and certificate history are server-rendered in render.php.
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

function statusLabel( status ) {
	switch ( status ) {
		case 'passed':
			return 'Passed';
		case 'completed':
			return 'Completed';
		case 'withdrawn':
			return 'Withdrawn';
		default:
			return 'Active';
	}
}

document.querySelectorAll( '.bfc-my-courses' ).forEach( ( root ) => {
	const list = root.querySelector( '[data-bfc-list]' );
	if ( ! list ) {
		return;
	}
	const config = parseConfig( root );
	const loading = root.querySelector( '[data-bfc-loading]' );
	const empty = root.querySelector( '[data-bfc-empty]' );
	const status = root.querySelector( '[data-bfc-status]' );

	const render = ( enrollments ) => {
		list.replaceChildren();
		if ( ! enrollments.length ) {
			if ( empty ) {
				empty.hidden = false;
			}
			list.hidden = true;
			return;
		}
		if ( empty ) {
			empty.hidden = true;
		}
		list.hidden = false;
		enrollments.forEach( ( item ) => {
			const li = document.createElement( 'li' );
			li.className = 'bfc-my-courses__item';
			li.dataset.courseId = String( item.course_id );

			const title = document.createElement(
				item.permalink ? 'a' : 'span'
			);
			title.className = 'bfc-my-courses__title';
			title.textContent = item.title || `Course #${ item.course_id }`;
			if ( item.permalink ) {
				title.href = item.permalink;
			}

			const badge = document.createElement( 'span' );
			badge.className = 'bfc-my-courses__badge';
			badge.textContent = statusLabel( item.status );

			li.append( title, badge );

			if ( item.status === 'active' || item.status === 'passed' ) {
				const withdraw = document.createElement( 'button' );
				withdraw.type = 'button';
				withdraw.className = 'bfc-my-courses__withdraw';
				withdraw.textContent = 'Withdraw';
				withdraw.addEventListener( 'click', async () => {
					setStatus( status, '' );
					withdraw.disabled = true;
					try {
						const response = await fetch(
							`${ config.restUrl }/enrollments/${ item.course_id }`,
							{
								method: 'DELETE',
								credentials: 'same-origin',
								headers: {
									'X-WP-Nonce': config.nonce || '',
								},
							}
						);
						const payload = await response
							.json()
							.catch( () => ( {} ) );
						if ( ! response.ok ) {
							setStatus(
								status,
								payload.message || 'Withdraw failed.',
								true
							);
							withdraw.disabled = false;
							return;
						}
						badge.textContent = 'Withdrawn';
						withdraw.remove();
					} catch {
						setStatus(
							status,
							'Withdraw failed. Please try again.',
							true
						);
						withdraw.disabled = false;
					}
				} );
				li.append( withdraw );
			}

			list.append( li );
		} );
	};

	( async () => {
		try {
			const response = await fetch( `${ config.restUrl }/enrollments`, {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': config.nonce || '',
				},
			} );
			const payload = await response.json().catch( () => ( {} ) );
			if ( loading ) {
				loading.hidden = true;
			}
			if ( ! response.ok ) {
				setStatus(
					status,
					payload.message || 'Could not load courses.',
					true
				);
				return;
			}
			render( payload.enrollments || [] );
		} catch {
			if ( loading ) {
				loading.hidden = true;
			}
			setStatus( status, 'Could not load courses.', true );
		}
	} )();
} );
