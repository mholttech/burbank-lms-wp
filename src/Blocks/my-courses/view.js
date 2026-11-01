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

/* REST timestamps are UTC MySQL datetimes ("Y-m-d H:i:s"). */
function formatDate( value ) {
	if ( ! value ) {
		return '';
	}
	const date = new Date( String( value ).replace( ' ', 'T' ) + 'Z' );
	if ( Number.isNaN( date.getTime() ) ) {
		return '';
	}
	return date.toLocaleDateString( 'en-US', {
		month: 'long',
		day: 'numeric',
		year: 'numeric',
	} );
}

function metaText( item ) {
	const enrolled = formatDate( item.enrolled_at );
	const completed = formatDate( item.completed_at );
	if ( item.status === 'completed' && completed ) {
		return enrolled
			? `Completed ${ completed } · Enrolled ${ enrolled }`
			: `Completed ${ completed }`;
	}
	return enrolled ? `Enrolled ${ enrolled }` : '';
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
			li.dataset.status = item.status || 'active';

			const main = document.createElement( 'div' );
			main.className = 'bfc-my-courses__main';

			const title = document.createElement(
				item.permalink ? 'a' : 'span'
			);
			title.className = 'bfc-my-courses__title';
			title.textContent = item.title || `Course #${ item.course_id }`;
			if ( item.permalink ) {
				title.href = item.permalink;
			}
			main.append( title );

			const meta = metaText( item );
			if ( meta ) {
				const metaEl = document.createElement( 'p' );
				metaEl.className = 'bfc-my-courses__meta';
				metaEl.textContent = meta;
				main.append( metaEl );
			}

			const actions = document.createElement( 'div' );
			actions.className = 'bfc-my-courses__actions';

			const badge = document.createElement( 'span' );
			badge.className = 'bfc-my-courses__badge';
			badge.dataset.status = item.status || 'active';
			badge.textContent = statusLabel( item.status );
			actions.append( badge );

			let action = null;
			if ( item.permalink ) {
				action = document.createElement( 'a' );
				action.className = 'bfc-my-courses__action';
				action.href = item.permalink;
				const inProgress =
					item.status === 'active' || item.status === 'passed';
				action.textContent = inProgress
					? 'Continue course'
					: 'View course';
				action.classList.toggle( 'is-secondary', ! inProgress );
				actions.append( action );
			}

			li.append( main, actions );

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
						badge.dataset.status = 'withdrawn';
						li.dataset.status = 'withdrawn';
						if ( action ) {
							action.textContent = 'View course';
							action.classList.add( 'is-secondary' );
						}
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
				actions.append( withdraw );
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
