/**
 * Course Approval panel — editorial status and actions via admin REST.
 */

import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, TextControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { dispatch, select, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { getConfig } from '../config';

const EMPTY_EDITORIAL = {
	approved: false,
	approvedBy: null,
	approvedAt: null,
	canApprove: false,
	snapshots: [],
};

/**
 * Normalize GET/POST editorial payload into camelCase panel state.
 *
 * @param {Object} data REST editorial payload.
 * @return {Object} Panel state.
 */
function normalizeEditorial( data ) {
	return {
		approved: Boolean( data?.approved ),
		approvedBy: data?.approved_by ?? null,
		approvedAt: data?.approved_at ?? null,
		canApprove: Boolean( data?.can_approve ),
		snapshots: Array.isArray( data?.snapshots ) ? data.snapshots : [],
	};
}

/**
 * Format one retained approval snapshot for the history list.
 *
 * @param {Object} snapshot Snapshot row from editorial payload.
 * @return {string} Display line.
 */
function formatSnapshot( snapshot ) {
	return sprintf(
		/* translators: 1: code, 2: revision, 3: historical title, 4: approval date, 5: approver ID. */
		__(
			'%1$s revision %2$s — %3$s; %4$s UTC; approver #%5$d',
			'burbank-lms'
		),
		snapshot.bfc_code ?? '',
		snapshot.course_revision ?? '',
		snapshot.title ?? '',
		snapshot.approved_at ?? '',
		Number( snapshot.approved_by ) || 0
	);
}

/**
 * Approval status, approve / revision / archive actions, and history.
 *
 * @return {Object} Approval panel UI.
 */
export default function ApprovalPanel() {
	const config = getConfig();
	const { postId, isAutoDraft } = useSelect( ( selectStore ) => {
		const editor = selectStore( editorStore );
		return {
			postId: editor.getCurrentPostId(),
			isAutoDraft:
				editor.getEditedPostAttribute( 'status' ) === 'auto-draft',
		};
	}, [] );

	const disabled = ! postId || isAutoDraft;
	const [ , setMeta ] = useEntityProp( 'postType', 'bfc_course', 'meta' );
	const [ editorial, setEditorial ] = useState( EMPTY_EDITORIAL );
	const [ revision, setRevision ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ notice, setNotice ] = useState( null );

	/**
	 * Keep Details-panel entity meta aligned after Admin REST mutations.
	 *
	 * Revision/archive update the saved course server-side; without this, a
	 * later Save would push stale useEntityProp values back to the server.
	 * Read live edited meta from the store at sync time so concurrent Details
	 * edits are not overwritten by a stale render-time closure.
	 *
	 * @param {Object} patch Meta keys to merge into the edited entity.
	 */
	const syncEntityMeta = ( patch ) => {
		const current = select( editorStore ).getEditedPostAttribute( 'meta' );
		const base = current && typeof current === 'object' ? current : {};
		setMeta( { ...base, ...patch } );
	};

	/**
	 * Align the document status with the saved course after editorial actions.
	 *
	 * Material revision drafts a published/future course server-side; without
	 * this the document panel still shows Publish.
	 *
	 * @param {string|undefined} status Post status from the editorial payload.
	 */
	const syncPostStatus = ( status ) => {
		if ( typeof status !== 'string' || status === '' ) {
			return;
		}
		const current =
			select( editorStore ).getEditedPostAttribute( 'status' );
		if ( current !== status ) {
			dispatch( editorStore ).editPost( { status } );
		}
	};

	useEffect( () => {
		if ( disabled ) {
			setEditorial( EMPTY_EDITORIAL );
			return undefined;
		}

		let cancelled = false;
		setBusy( true );
		setNotice( null );

		apiFetch( {
			path: `/${ config.restNamespace }/courses/${ postId }/editorial`,
		} )
			.then( ( data ) => {
				if ( ! cancelled ) {
					setEditorial( normalizeEditorial( data ) );
				}
			} )
			.catch( ( error ) => {
				if ( ! cancelled ) {
					setNotice( {
						status: 'error',
						message:
							error?.message ||
							__(
								'Could not load approval state.',
								'burbank-lms'
							),
					} );
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
	}, [ postId, disabled, config.restNamespace ] );

	/**
	 * POST an editorial action and replace local state from the response.
	 *
	 * @param {string}  action  Route segment: approve | revision | archive.
	 * @param {Object=} options Optional body and success message.
	 */
	const runAction = async ( action, options = {} ) => {
		setBusy( true );
		setNotice( null );
		try {
			const data = await apiFetch( {
				path: `/${ config.restNamespace }/courses/${ postId }/${ action }`,
				method: 'POST',
				data: options.data,
			} );
			setEditorial( normalizeEditorial( data ) );
			if ( action === 'revision' && options.data?.revision ) {
				syncEntityMeta( { course_revision: options.data.revision } );
			}
			if ( action === 'archive' ) {
				syncEntityMeta( { catalog_state: 'archived' } );
			}
			syncPostStatus( data?.status );
			if ( options.clearRevision ) {
				setRevision( '' );
			}
			setNotice( {
				status: 'success',
				message:
					options.successMessage ||
					__( 'Course editorial action saved.', 'burbank-lms' ),
			} );
		} catch ( error ) {
			setNotice( {
				status: 'error',
				message:
					error?.message ||
					__( 'The request failed.', 'burbank-lms' ),
			} );
		} finally {
			setBusy( false );
		}
	};

	if ( disabled ) {
		return (
			<p>
				{ __(
					'Save a draft before requesting approval.',
					'burbank-lms'
				) }
			</p>
		);
	}

	const showApprove = Boolean( config.caps.approve );
	const statusLabel = editorial.approved
		? __( 'Approved', 'burbank-lms' )
		: __( 'Approval required', 'burbank-lms' );

	return (
		<>
			{ notice ? (
				<Notice
					status={ notice.status }
					isDismissible
					onRemove={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) : null }
			<p>
				{ __(
					'Save your edits first. These actions apply to the saved course. Use a material revision for assessed content changes; editorial corrections may retain approval under administrator policy.',
					'burbank-lms'
				) }
			</p>
			<p>{ statusLabel }</p>
			{ showApprove ? (
				<p>
					<Button
						variant="primary"
						disabled={ busy || ! editorial.canApprove }
						onClick={ () => runAction( 'approve' ) }
					>
						{ __( 'Approve saved course', 'burbank-lms' ) }
					</Button>
				</p>
			) : null }
			<TextControl
				label={ __( 'New revision', 'burbank-lms' ) }
				value={ revision }
				onChange={ setRevision }
				maxLength={ 32 }
				disabled={ busy }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<p>
				<Button
					variant="secondary"
					disabled={ busy || ! revision.trim() }
					onClick={ () =>
						runAction( 'revision', {
							data: { revision: revision.trim() },
							clearRevision: true,
						} )
					}
				>
					{ __( 'Start material revision', 'burbank-lms' ) }
				</Button>
			</p>
			<p>
				<Button
					variant="secondary"
					disabled={ busy }
					onClick={ () => runAction( 'archive' ) }
				>
					{ __( 'Archive', 'burbank-lms' ) }
				</Button>
			</p>
			<details>
				<summary>{ __( 'Approval history', 'burbank-lms' ) }</summary>
				<ul>
					{ editorial.snapshots.map( ( snapshot, index ) => (
						<li
							key={ `${ snapshot.approved_at ?? '' }-${ index }` }
						>
							{ formatSnapshot( snapshot ) }
						</li>
					) ) }
				</ul>
			</details>
		</>
	);
}
