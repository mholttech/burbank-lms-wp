/**
 * Login appearance: honor CERT theme preference + toggle.
 *
 * Storage key matches burbankcert-fse `appearance.js`.
 */
( () => {
	const STORAGE_KEY = 'burbankcert-appearance';
	const root = document.documentElement;
	const preference = window.matchMedia( '(prefers-color-scheme: dark)' );

	const chosenMode = () => {
		try {
			const saved = localStorage.getItem( STORAGE_KEY );
			if ( saved === 'light' || saved === 'dark' ) {
				return saved;
			}
		} catch {
			/* private browsing may block storage */
		}
		return preference.matches ? 'dark' : 'light';
	};

	const render = ( mode ) => {
		root.dataset.bcTheme = mode;
		document
			.querySelectorAll( '.bfc-login__theme-toggle' )
			.forEach( ( button ) => {
				const isDark = mode === 'dark';
				button.setAttribute( 'aria-pressed', String( isDark ) );
				button.setAttribute(
					'aria-label',
					isDark ? 'Switch to light mode' : 'Switch to dark mode'
				);
				const icon = button.querySelector( '.bfc-login__theme-icon' );
				if ( icon ) {
					icon.textContent = isDark ? '☀' : '☾';
				}
			} );
	};

	document
		.querySelectorAll( '.bfc-login__theme-toggle' )
		.forEach( ( button ) => {
			button.addEventListener( 'click', () => {
				const next = root.dataset.bcTheme === 'dark' ? 'light' : 'dark';
				try {
					localStorage.setItem( STORAGE_KEY, next );
				} catch {
					/* ignore */
				}
				render( next );
			} );
		} );

	preference.addEventListener?.( 'change', () => {
		try {
			const saved = localStorage.getItem( STORAGE_KEY );
			if ( saved === 'light' || saved === 'dark' ) {
				return;
			}
		} catch {
			/* ignore */
		}
		render( preference.matches ? 'dark' : 'light' );
	} );

	render( chosenMode() );
} )();
