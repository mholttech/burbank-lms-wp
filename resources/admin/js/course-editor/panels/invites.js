/**
 * Course Invites panel — create, list, and revoke via admin REST.
 */

import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Notice,
	SelectControl,
	TextControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { getConfig } from '../config';

const KIND_OPTIONS = [
	{ label: __( 'Multi-use', 'burbank-lms' ), value: 'multi' },
	{ label: __( 'One-time', 'burbank-lms' ), value: 'once' },
];

const EMPTY_FORM = {
	label: '',
	kind: 'multi',
	maxUses: '',
	expiresAt: '',
	boundEmail: '',
};

/**
 * Client-side invite activity check mirroring Invites::is_active.
 *
 * @param {Object} invite Serialized invite row.
 * @return {boolean} Whether the invite is still redeemable.
 */
function isInviteActive( invite ) {
	if ( invite?.revoked_at ) {
		return false;
	}
	if ( invite?.expires_at ) {
		const expiresMs = Date.parse( `${ invite.expires_at } UTC` );
		if ( Number.isFinite( expiresMs ) && expiresMs <= Date.now() ) {
			return false;
		}
	}
	const useCount = Number( invite?.use_count ) || 0;
	if ( invite?.kind === 'once' ) {
		return useCount === 0;
	}
	if ( invite?.kind !== 'multi' ) {
		return false;
	}
	if ( invite?.max_uses === null || invite?.max_uses === undefined ) {
		return true;
	}
	return useCount < Number( invite.max_uses );
}

/**
 * Status label for a list row.
 *
 * @param {Object} invite Serialized invite row.
 * @return {string} Status text.
 */
function inviteStatus( invite ) {
	if ( invite?.revoked_at ) {
		return __( 'Revoked', 'burbank-lms' );
	}
	return isInviteActive( invite )
		? __( 'Active', 'burbank-lms' )
		: __( 'Inactive', 'burbank-lms' );
}

/**
 * Format uses as "count / max" with infinity when unbounded.
 *
 * @param {Object} invite Serialized invite row.
 * @return {string} Uses display.
 */
function formatUses( invite ) {
	const used = Number( invite?.use_count ) || 0;
	const max =
		invite?.max_uses === null || invite?.max_uses === undefined
			? '∞'
			: String( Number( invite.max_uses ) );
	return `${ used } / ${ max }`;
}

/**
 * Create, list, and revoke course invites.
 *
 * @return {Object} Invites panel UI.
 */
export default function InvitesPanel() {
	const config = getConfig();
	const { postId, isAutoDraft } = useSelect( ( select ) => {
		const editor = select( editorStore );
		return {
			postId: editor.getCurrentPostId(),
			isAutoDraft:
				editor.getEditedPostAttribute( 'status' ) === 'auto-draft',
		};
	}, [] );

	const canEnroll = Boolean( config.caps.enroll );
	const disabled = ! postId || isAutoDraft;
	const [ invites, setInvites ] = useState( [] );
	const [ form, setForm ] = useState( EMPTY_FORM );
	const [ busy, setBusy ] = useState( false );
	const [ notices, setNotices ] = useState( [] );

	const loadInvites = useCallback( async () => {
		const data = await apiFetch( {
			path: `/${ config.restNamespace }/courses/${ postId }/invites`,
		} );
		setInvites( Array.isArray( data?.invites ) ? data.invites : [] );
	}, [ config.restNamespace, postId ] );

	useEffect( () => {
		if ( disabled || ! canEnroll ) {
			setInvites( [] );
			return undefined;
		}

		let cancelled = false;
		setBusy( true );
		setNotices( [] );

		loadInvites()
			.catch( ( error ) => {
				if ( ! cancelled ) {
					setNotices( [
						{
							status: 'error',
							message:
								error?.message ||
								__(
									'Could not load course invites.',
									'burbank-lms'
								),
						},
					] );
				}
			} )
			.finally( () => {
				if ( ! cancelled ) {
					setBusy( false );
				}
			} );

		return () => {
			cancelled = true;
		};
	}, [ postId, disabled, canEnroll, loadInvites ] );

	/**
	 * Update one create-form field.
	 *
	 * @param {string} key   Form field key.
	 * @param {string} value New value.
	 */
	const setField = ( key, value ) => {
		setForm( ( current ) => ( { ...current, [ key ]: value } ) );
	};

	/**
	 * POST a new course invite and surface plaintext once.
	 * Plaintext success is shown before list refresh so a refresh failure
	 * cannot hide the one-time code.
	 */
	const createInvite = async () => {
		setBusy( true );
		setNotices( [] );
		try {
			/* REST create args use snake_case field names. */
			/* eslint-disable camelcase -- REST contract */
			const body = {
				label: form.label.trim(),
				kind: form.kind,
			};
			const maxUses = parseInt( form.maxUses, 10 );
			if ( Number.isFinite( maxUses ) && maxUses > 0 ) {
				body.max_uses = maxUses;
			}
			if ( form.expiresAt.trim() ) {
				body.expires_at = form.expiresAt.trim();
			}
			if ( form.boundEmail.trim() ) {
				body.bound_email = form.boundEmail.trim();
			}
			/* eslint-enable camelcase */

			const created = await apiFetch( {
				path: `/${ config.restNamespace }/courses/${ postId }/invites`,
				method: 'POST',
				data: body,
			} );
			setForm( EMPTY_FORM );
			const successNotice = {
				status: 'success',
				message: sprintf(
					/* translators: %s: plaintext invite code shown once. */
					__(
						'Invite created. Copy this code now; it will not be shown again: %s',
						'burbank-lms'
					),
					created?.plaintext || ''
				),
			};
			setNotices( [ successNotice ] );
			try {
				await loadInvites();
			} catch ( refreshError ) {
				setNotices( [
					successNotice,
					{
						status: 'warning',
						message:
							refreshError?.message ||
							__(
								'Invite created, but the list could not be refreshed.',
								'burbank-lms'
							),
					},
				] );
			}
		} catch ( error ) {
			setNotices( [
				{
					status: 'error',
					message:
						error?.message ||
						__( 'Could not create the invite.', 'burbank-lms' ),
				},
			] );
		} finally {
			setBusy( false );
		}
	};

	/**
	 * DELETE an invite and refresh the list.
	 *
	 * @param {number} inviteId Invite row ID.
	 */
	const revokeInvite = async ( inviteId ) => {
		setBusy( true );
		setNotices( [] );
		try {
			await apiFetch( {
				path: `/${ config.restNamespace }/invites/${ inviteId }`,
				method: 'DELETE',
			} );
			await loadInvites();
			setNotices( [
				{
					status: 'success',
					message: __( 'Invite revoked.', 'burbank-lms' ),
				},
			] );
		} catch ( error ) {
			setNotices( [
				{
					status: 'error',
					message:
						error?.message ||
						__( 'Could not revoke the invite.', 'burbank-lms' ),
				},
			] );
		} finally {
			setBusy( false );
		}
	};

	if ( ! canEnroll ) {
		return (
			<p>
				{ __(
					'You do not have permission to manage course invites.',
					'burbank-lms'
				) }
			</p>
		);
	}

	if ( disabled ) {
		return (
			<p>
				{ __(
					'Save the course before issuing invites.',
					'burbank-lms'
				) }
			</p>
		);
	}

	return (
		<>
			{ notices.map( ( item, index ) => (
				<Notice
					key={ `${ item.status }-${ index }` }
					status={ item.status }
					isDismissible
					onRemove={ () =>
						setNotices( ( current ) =>
							current.filter( ( _, i ) => i !== index )
						)
					}
				>
					{ item.message }
				</Notice>
			) ) }

			<TextControl
				label={ __( 'Label', 'burbank-lms' ) }
				value={ form.label }
				onChange={ ( value ) => setField( 'label', value ) }
				disabled={ busy }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<SelectControl
				label={ __( 'Kind', 'burbank-lms' ) }
				value={ form.kind }
				options={ KIND_OPTIONS }
				onChange={ ( value ) => setField( 'kind', value ) }
				disabled={ busy }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Max uses (multi)', 'burbank-lms' ) }
				type="number"
				min={ 1 }
				value={ form.maxUses }
				onChange={ ( value ) => setField( 'maxUses', value ) }
				disabled={ busy || form.kind !== 'multi' }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Expires at (UTC)', 'burbank-lms' ) }
				value={ form.expiresAt }
				onChange={ ( value ) => setField( 'expiresAt', value ) }
				placeholder="YYYY-MM-DD HH:MM:SS"
				disabled={ busy }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Bound email', 'burbank-lms' ) }
				type="email"
				value={ form.boundEmail }
				onChange={ ( value ) => setField( 'boundEmail', value ) }
				disabled={ busy }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<p>
				<Button
					variant="primary"
					disabled={ busy }
					onClick={ createInvite }
				>
					{ __( 'Create invite', 'burbank-lms' ) }
				</Button>
			</p>

			{ invites.length === 0 ? (
				<p>{ __( 'No invites yet.', 'burbank-lms' ) }</p>
			) : (
				<div className="burbank-lms-course-sidebar__table-scroll">
					<table className="burbank-lms-course-sidebar__table">
						<thead>
							<tr>
								<th>{ __( 'ID', 'burbank-lms' ) }</th>
								<th>{ __( 'Label', 'burbank-lms' ) }</th>
								<th>{ __( 'Kind', 'burbank-lms' ) }</th>
								<th>{ __( 'Uses', 'burbank-lms' ) }</th>
								<th>{ __( 'Bound email', 'burbank-lms' ) }</th>
								<th>{ __( 'Status', 'burbank-lms' ) }</th>
								<th>{ __( 'Actions', 'burbank-lms' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ invites.map( ( invite ) => (
								<tr key={ invite.id }>
									<td>{ invite.id }</td>
									<td>{ invite.label || '—' }</td>
									<td>{ invite.kind }</td>
									<td>{ formatUses( invite ) }</td>
									<td>{ invite.bound_email || '—' }</td>
									<td>{ inviteStatus( invite ) }</td>
									<td>
										{ invite.revoked_at ? (
											'—'
										) : (
											<Button
												variant="link"
												isDestructive
												disabled={ busy }
												onClick={ () =>
													revokeInvite( invite.id )
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
			) }
		</>
	);
}
