/* eslint-disable camelcase -- REST contract field names. */
import { createRoot, useEffect, useState } from '@wordpress/element';
import {
	Button,
	Notice,
	TextControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const config = window.burbankLmsTraining;
async function request( path, method = 'GET', body ) {
	const response = await fetch( config.restUrl + path, {
		method,
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': config.nonce,
		},
		body: body ? JSON.stringify( body ) : undefined,
	} );
	const data = await response.json();
	if ( ! response.ok ) {
		throw new Error(
			data.message || __( 'Request failed.', 'burbank-lms' )
		);
	}
	return data;
}
function inviteStatus( row ) {
	if ( row.revoked ) {
		return __( 'Revoked', 'burbank-lms' );
	}
	return row.active
		? __( 'Active', 'burbank-lms' )
		: __( 'Inactive', 'burbank-lms' );
}
function accessStatus( row ) {
	if ( row.suspended ) {
		return __( 'Suspended', 'burbank-lms' );
	}
	return row.access
		? __( 'Granted', 'burbank-lms' )
		: __( 'Not granted', 'burbank-lms' );
}
function App() {
	const [ tab, setTab ] = useState(
		new URLSearchParams( window.location.search ).get( 'page' ) ===
			'burbank-lms-learners'
			? 'learners'
			: 'invites'
	);
	const [ rows, setRows ] = useState( [] );
	const [ search, setSearch ] = useState( '' );
	const [ query, setQuery ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ total, setTotal ] = useState( 0 );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );
	const [ code, setCode ] = useState( '' );
	const [ reload, setReload ] = useState( 0 );
	const [ invite, setInvite ] = useState( {
		label: '',
		kind: 'multi',
		max_uses: '',
		expires_at: '',
		bound_email: '',
	} );
	const [ editing, setEditing ] = useState( null );
	const [ name, setName ] = useState( '' );
	useEffect( () => {
		let active = true;
		setBusy( true );
		setError( '' );
		request(
			tab === 'invites'
				? '/invites'
				: `/learners?page=${ page }&search=${ encodeURIComponent(
						query
					) }`
		)
			.then( ( data ) => {
				if ( active ) {
					setRows( tab === 'invites' ? data : data.items );
					setTotal( data.total || 0 );
				}
			} )
			.catch( ( err ) => {
				if ( active ) {
					setError( err.message );
				}
			} )
			.finally( () => {
				if ( active ) {
					setBusy( false );
				}
			} );
		return () => {
			active = false;
		};
	}, [ tab, page, query, reload ] );
	async function mutate( path, method, body ) {
		setBusy( true );
		setError( '' );
		setNotice( '' );
		try {
			const data = await request( path, method, body );
			setNotice( __( 'Saved.', 'burbank-lms' ) );
			setReload( reload + 1 );
			return data;
		} catch ( err ) {
			setError( err.message );
			return null;
		} finally {
			setBusy( false );
		}
	}
	function changeTab( value ) {
		if ( value === tab || busy ) {
			return;
		}
		setTab( value );
		setRows( [] );
		setPage( 1 );
		setCode( '' );
		setEditing( null );
		setNotice( '' );
	}
	return (
		<div className="bfc-training">
			<h1>{ __( 'Training', 'burbank-lms' ) }</h1>
			<nav
				className="bfc-training__tabs"
				aria-label={ __( 'Training management', 'burbank-lms' ) }
			>
				<Button
					variant={ tab === 'invites' ? 'primary' : 'secondary' }
					onClick={ () => changeTab( 'invites' ) }
				>
					{ __( 'Site invites', 'burbank-lms' ) }
				</Button>
				<Button
					variant={ tab === 'learners' ? 'primary' : 'secondary' }
					onClick={ () => changeTab( 'learners' ) }
				>
					{ __( 'Training access', 'burbank-lms' ) }
				</Button>
			</nav>
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }
			{ notice && (
				<Notice status="success" isDismissible={ false }>
					{ notice }
				</Notice>
			) }
			{ tab === 'invites' ? (
				<>
					<section className="bfc-training__panel">
						<h2>{ __( 'Create site invite', 'burbank-lms' ) }</h2>
						<form
							onSubmit={ async ( event ) => {
								event.preventDefault();
								setCode( '' );
								const data = await mutate(
									'/invites',
									'POST',
									invite
								);
								if ( data ) {
									setCode( data.plaintext );
								}
							} }
						>
							<div className="bfc-training__fields">
								<TextControl
									label={ __( 'Label', 'burbank-lms' ) }
									value={ invite.label }
									onChange={ ( value ) =>
										setInvite( { ...invite, label: value } )
									}
								/>
								<SelectControl
									label={ __( 'Kind', 'burbank-lms' ) }
									value={ invite.kind }
									options={ [
										{
											label: __(
												'Multi-use',
												'burbank-lms'
											),
											value: 'multi',
										},
										{
											label: __(
												'One-time',
												'burbank-lms'
											),
											value: 'once',
										},
									] }
									onChange={ ( value ) =>
										setInvite( { ...invite, kind: value } )
									}
								/>
								{ invite.kind === 'multi' && (
									<TextControl
										type="number"
										min="1"
										label={ __(
											'Maximum uses (blank for unlimited)',
											'burbank-lms'
										) }
										value={ invite.max_uses }
										onChange={ ( value ) =>
											setInvite( {
												...invite,
												max_uses: value,
											} )
										}
									/>
								) }
								<TextControl
									type="email"
									label={ __(
										'Bound email (optional)',
										'burbank-lms'
									) }
									value={ invite.bound_email }
									onChange={ ( value ) =>
										setInvite( {
											...invite,
											bound_email: value,
										} )
									}
								/>
								<TextControl
									label={ __(
										'Expires at (UTC, YYYY-MM-DD HH:MM:SS)',
										'burbank-lms'
									) }
									value={ invite.expires_at }
									onChange={ ( value ) =>
										setInvite( {
											...invite,
											expires_at: value,
										} )
									}
								/>
							</div>
							<Button
								type="submit"
								variant="primary"
								disabled={ busy }
							>
								{ __( 'Create invite', 'burbank-lms' ) }
							</Button>
						</form>
						{ code && (
							<div role="status">
								<p>
									{ __(
										'Copy this code now. It is shown only once.',
										'burbank-lms'
									) }
								</p>
								<code>{ code }</code>{ ' ' }
								<Button
									variant="secondary"
									onClick={ async () => {
										try {
											await navigator.clipboard.writeText(
												code
											);
											setNotice(
												__(
													'Code copied.',
													'burbank-lms'
												)
											);
										} catch {
											setError(
												__(
													'Copy the displayed code manually.',
													'burbank-lms'
												)
											);
										}
									} }
								>
									{ __( 'Copy code', 'burbank-lms' ) }
								</Button>
								<Button onClick={ () => setCode( '' ) }>
									{ __( 'Dismiss', 'burbank-lms' ) }
								</Button>
							</div>
						) }
					</section>
					<section className="bfc-training__panel">
						<h2>{ __( 'Site invites', 'burbank-lms' ) }</h2>
						<div className="bfc-training__scroll">
							<table className="widefat">
								<thead>
									<tr>
										{ [
											__( 'Label', 'burbank-lms' ),
											__( 'Kind', 'burbank-lms' ),
											__( 'Uses', 'burbank-lms' ),
											__( 'Bound email', 'burbank-lms' ),
											__( 'Expiry (UTC)', 'burbank-lms' ),
											__( 'Status', 'burbank-lms' ),
											__( 'Actions', 'burbank-lms' ),
										].map( ( label ) => (
											<th key={ label }>{ label }</th>
										) ) }
									</tr>
								</thead>
								<tbody>
									{ rows.map( ( row ) => (
										<tr key={ row.id }>
											<td>{ row.label || row.id }</td>
											<td>{ row.kind }</td>
											<td>
												{ row.uses } /{ ' ' }
												{ row.max_uses ||
													( row.kind === 'once'
														? 1
														: '∞' ) }
											</td>
											<td>{ row.bound_email || '—' }</td>
											<td>{ row.expires_at || '—' }</td>
											<td>{ inviteStatus( row ) }</td>
											<td>
												{ ! row.revoked && (
													<Button
														isDestructive
														disabled={ busy }
														onClick={ () =>
															mutate(
																`/invites/${ row.id }`,
																'DELETE'
															)
														}
													>
														{ __(
															'Revoke',
															'burbank-lms'
														) }
													</Button>
												) }
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					</section>
				</>
			) : (
				<section className="bfc-training__panel">
					<h2>{ __( 'Learners', 'burbank-lms' ) }</h2>
					<form
						className="bfc-training__search"
						onSubmit={ ( event ) => {
							event.preventDefault();
							setPage( 1 );
							setQuery( search );
						} }
					>
						<TextControl
							label={ __( 'Search accounts', 'burbank-lms' ) }
							value={ search }
							onChange={ setSearch }
						/>
						<Button
							type="submit"
							variant="secondary"
							disabled={ busy }
						>
							{ __( 'Search', 'burbank-lms' ) }
						</Button>
					</form>
					<div className="bfc-training__scroll">
						<table className="widefat">
							<thead>
								<tr>
									{ [
										__( 'Name / email', 'burbank-lms' ),
										__( 'Certificate name', 'burbank-lms' ),
										__( 'Access', 'burbank-lms' ),
										__( 'Verified', 'burbank-lms' ),
										__( 'Actions', 'burbank-lms' ),
									].map( ( label ) => (
										<th key={ label }>{ label }</th>
									) ) }
								</tr>
							</thead>
							<tbody>
								{ rows.map( ( row ) => (
									<tr key={ row.id }>
										<td>
											{ row.name }
											<br />
											{ row.email }
										</td>
										<td>
											{ row.legal_name || '—' }
											<Button
												disabled={ busy }
												onClick={ () => {
													setEditing( row.id );
													setName( row.legal_name );
												} }
											>
												{ __(
													'Correct name',
													'burbank-lms'
												) }
											</Button>
										</td>
										<td>{ accessStatus( row ) }</td>
										<td>
											{ row.verified
												? __( 'Yes', 'burbank-lms' )
												: __( 'No', 'burbank-lms' ) }
										</td>
										<td>
											<div className="bfc-training__actions">
												<Button
													disabled={ busy }
													variant="secondary"
													onClick={ () =>
														mutate(
															`/learners/${ row.id }`,
															'POST',
															{
																operation:
																	row.access
																		? 'revoke'
																		: 'grant',
															}
														)
													}
												>
													{ row.access
														? __(
																'Revoke access',
																'burbank-lms'
															)
														: __(
																'Grant access',
																'burbank-lms'
															) }
												</Button>
												<Button
													disabled={ busy }
													onClick={ () =>
														mutate(
															`/learners/${ row.id }`,
															'POST',
															{
																operation:
																	row.suspended
																		? 'reactivate'
																		: 'suspend',
															}
														)
													}
												>
													{ row.suspended
														? __(
																'Reactivate',
																'burbank-lms'
															)
														: __(
																'Suspend',
																'burbank-lms'
															) }
												</Button>
												{ ! row.verified &&
													row.access &&
													! row.suspended && (
														<Button
															disabled={ busy }
															onClick={ () =>
																mutate(
																	`/learners/${ row.id }`,
																	'POST',
																	{
																		operation:
																			'resend',
																	}
																)
															}
														>
															{ __(
																'Resend verification',
																'burbank-lms'
															) }
														</Button>
													) }
											</div>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
					{ editing && (
						<form
							onSubmit={ async ( event ) => {
								event.preventDefault();
								const data = await mutate(
									`/learners/${ editing }`,
									'POST',
									{
										operation: 'legal_name',
										legal_name: name,
									}
								);
								if ( data ) {
									setEditing( null );
								}
							} }
						>
							<TextControl
								label={ __(
									'Correct certificate name (audited)',
									'burbank-lms'
								) }
								value={ name }
								onChange={ setName }
								required
							/>
							<Button
								type="submit"
								variant="primary"
								disabled={ busy }
							>
								{ __( 'Save correction', 'burbank-lms' ) }
							</Button>
							<Button onClick={ () => setEditing( null ) }>
								{ __( 'Cancel', 'burbank-lms' ) }
							</Button>
						</form>
					) }
					<div className="bfc-training__actions">
						<Button
							disabled={ busy || page === 1 }
							onClick={ () => setPage( page - 1 ) }
						>
							{ __( 'Previous', 'burbank-lms' ) }
						</Button>
						<span>{ page }</span>
						<Button
							disabled={ busy || page * 20 >= total }
							onClick={ () => setPage( page + 1 ) }
						>
							{ __( 'Next', 'burbank-lms' ) }
						</Button>
					</div>
				</section>
			) }
			{ busy && <Spinner /> }
			{ ! busy && ! rows.length && (
				<p>{ __( 'No results.', 'burbank-lms' ) }</p>
			) }
		</div>
	);
}
createRoot( document.getElementById( 'burbank-lms-training' ) ).render(
	<App />
);
