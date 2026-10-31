/**
 * Catalog open-enroll CTAs → POST /burbank-lms/v1/enrollments.
 * Client-side search + subject filter.
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

function applyFilters( root ) {
	const q = (
		root.querySelector( '[data-bfc-catalog-search]' )?.value || ''
	)
		.toLowerCase()
		.trim();
	const subject =
		root.querySelector( '[data-bfc-catalog-subject]' )?.value || '';
	let visible = 0;
	root.querySelectorAll( '.bfc-catalog__item' ).forEach( ( item ) => {
		const title = ( item.dataset.bfcTitle || '' ).toLowerCase();
		const code = ( item.dataset.bfcCode || '' ).toLowerCase();
		const subj = item.dataset.bfcSubject || '';
		const matchQ = ! q || title.includes( q ) || code.includes( q );
		const matchS = ! subject || subj === subject;
		const show = matchQ && matchS;
		item.hidden = ! show;
		if ( show ) {
			visible += 1;
		}
	} );
	const empty = root.querySelector( '[data-bfc-catalog-empty-filter]' );
	if ( empty ) {
		empty.hidden = visible > 0;
	}
}

document.querySelectorAll( '.bfc-catalog' ).forEach( ( root ) => {
	const status = root.querySelector( '[data-bfc-status]' );
	const config = parseConfig( root );

	const search = root.querySelector( '[data-bfc-catalog-search]' );
	const subject = root.querySelector( '[data-bfc-catalog-subject]' );
	if ( search ) {
		search.addEventListener( 'input', () => applyFilters( root ) );
	}
	if ( subject ) {
		subject.addEventListener( 'change', () => applyFilters( root ) );
	}

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
				const chip = document.createElement( 'span' );
				chip.className =
					'bfc-catalog__chip bfc-catalog__chip--enrolled';
				chip.textContent = 'Enrolled';
				button.replaceWith( chip );
				setStatus( status, 'Enrolled successfully.', false );
			} catch ( error ) {
				setStatus( status, 'Enrollment failed. Please try again.', true );
				button.disabled = false;
			}
		} );
	} );
} );
