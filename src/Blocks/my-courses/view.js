/**
 * My Courses list + withdraw via burbank-lms/v1 enrollments REST.
 * Catalog link, stats strip, and certificate history are server-rendered.
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

	/** @type {Array<Record<string, unknown>>} */
	let enrollments = [];

	const STATUS_ORDER = { active: 0, passed: 1, completed: 2, withdrawn: 3 };

	const sortEnrollments = ( items ) =>
		[ ...items ].sort( ( a, b ) => {
			const rankA = STATUS_ORDER[ a.status ] ?? 0;
			const rankB = STATUS_ORDER[ b.status ] ?? 0;
			if ( rankA !== rankB ) {
				return rankA - rankB;
			}
			if ( a.status === 'completed' ) {
				return String( b.completed_at || '' ).localeCompare(
					String( a.completed_at || '' )
				);
			}
			return 0;
		} );

	const setStat = ( name, value ) => {
		const el = root.querySelector( `[data-bfc-stat="${ name }"]` );
		if ( el ) {
			el.textContent = String( value );
		}
	};

	const renderStats = ( items ) => {
		const stats = root.querySelector( '[data-bfc-stats]' );
		if ( ! stats ) {
			return;
		}
		const count = ( ...statuses ) =>
			items.filter( ( item ) => statuses.includes( item.status ) ).length;
		setStat( 'enrolled', count( 'active', 'passed', 'completed' ) );
		setStat( 'in-progress', count( 'active', 'passed' ) );
		setStat( 'completed', count( 'completed' ) );

		const certStat = root.querySelector( '[data-bfc-stat="certificates"]' );
		const certStatLi = certStat?.closest( 'li' );
		const certRoot = root.querySelector( '.ppcert-my-certificates' );
		if ( certStatLi ) {
			if ( ! certRoot ) {
				certStatLi.hidden = true;
				stats.classList.add( 'bfc-my-courses__stats--no-certs' );
			} else {
				certStatLi.hidden = false;
				stats.classList.remove( 'bfc-my-courses__stats--no-certs' );
				const certItems = certRoot.querySelectorAll(
					'.ppcert-my-certificates__item'
				);
				setStat( 'certificates', certItems.length );
				const valid = certRoot.querySelectorAll(
					'.ppcert-my-certificates__item .ppcert-pill--valid'
				).length;
				const validEl = root.querySelector( '[data-bfc-cert-valid]' );
				if ( validEl && certItems.length > 0 ) {
					validEl.textContent =
						valid === 1 ? '1 valid' : `${ valid } valid`;
					validEl.hidden = false;
				}
			}
		}
		stats.hidden = false;
	};

	const render = ( items ) => {
		enrollments = items;
		list.replaceChildren();
		renderStats( enrollments );
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
		sortEnrollments( enrollments ).forEach( ( item ) => {
			const li = document.createElement( 'li' );
			li.className = 'bfc-my-courses__item';
			li.dataset.courseId = String( item.course_id );
			li.dataset.status = item.status || 'active';

			const badge = document.createElement( 'span' );
			badge.className = 'bfc-my-courses__badge';
			badge.dataset.status = item.status || 'active';
			badge.textContent = statusLabel( item.status );

			const title = document.createElement(
				item.permalink ? 'a' : 'span'
			);
			title.className = 'bfc-my-courses__title';
			title.textContent = item.title || `Course #${ item.course_id }`;
			if ( item.permalink ) {
				title.href = item.permalink;
			}

			li.append( badge, title );

			const meta = metaText( item );
			if ( meta ) {
				const metaEl = document.createElement( 'p' );
				metaEl.className = 'bfc-my-courses__meta';
				metaEl.textContent = meta;
				li.append( metaEl );
			}

			const footer = document.createElement( 'div' );
			footer.className = 'bfc-my-courses__foot';

			const left = document.createElement( 'div' );
			left.className = 'bfc-my-courses__foot-left';

			const footStatus = document.createElement( 'span' );
			footStatus.className = 'bfc-my-courses__foot-status';
			footStatus.setAttribute( 'aria-hidden', 'true' );
			footStatus.textContent = statusLabel( item.status );
			left.append( footStatus );

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
			}

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
						item.status = 'withdrawn';
						badge.textContent = 'Withdrawn';
						badge.dataset.status = 'withdrawn';
						footStatus.textContent = 'Withdrawn';
						li.dataset.status = 'withdrawn';
						if ( action ) {
							action.textContent = 'View course';
							action.classList.add( 'is-secondary' );
						}
						withdraw.remove();
						renderStats( enrollments );
					} catch {
						setStatus(
							status,
							'Withdraw failed. Please try again.',
							true
						);
						withdraw.disabled = false;
					}
				} );
				left.append( withdraw );
			}

			footer.append( left );
			if ( action ) {
				footer.append( action );
			}
			li.append( footer );

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
