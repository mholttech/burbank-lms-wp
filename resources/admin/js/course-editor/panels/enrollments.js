/**
 * Course Enrollments panel — admin enroll + list via REST.
 */

import apiFetch from '@wordpress/api-fetch';
import { Button, Notice, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

import { getConfig } from '../config';

/**
 * Display label for an enrollment row user.
 *
 * @param {Object} row Enrollment list row.
 * @return {string} User display.
 */
function userLabel( row ) {
	const id = Number( row?.user_id ) || 0;
	const name = row?.display_name ? String( row.display_name ) : '';
	if ( name ) {
		return sprintf(
			/* translators: 1: display name, 2: user ID. */
			__( '%1$s (#%2$d)', 'burbank-lms' ),
			name,
			id
		);
	}
	return `#${ id }`;
}

/**
 * Format a nullable datetime cell.
 *
 * @param {string|null|undefined} value Datetime or empty.
 * @return {string} Cell text.
 */
function cellDate( value ) {
	return value ? String( value ) : '—';
}

/**
 * Admin enroll by user ID and list course enrollments.
 *
 * @return {Object} Enrollments panel UI.
 */
export default function EnrollmentsPanel() {
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
	const [ enrollments, setEnrollments ] = useState( [] );
	const [ userId, setUserId ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ notices, setNotices ] = useState( [] );

	const loadEnrollments = useCallback( async () => {
		const data = await apiFetch( {
			path: `/${ config.restNamespace }/courses/${ postId }/enrollments`,
		} );
		setEnrollments(
			Array.isArray( data?.enrollments ) ? data.enrollments : []
		);
	}, [ config.restNamespace, postId ] );

	useEffect( () => {
		if ( disabled || ! canEnroll ) {
			setEnrollments( [] );
			return undefined;
		}

		let cancelled = false;
		setBusy( true );
		setNotices( [] );

		loadEnrollments()
			.catch( ( error ) => {
				if ( ! cancelled ) {
					setNotices( [
						{
							status: 'error',
							message:
								error?.message ||
								__(
									'Could not load enrollments.',
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
	}, [ postId, disabled, canEnroll, loadEnrollments ] );

	/**
	 * POST admin enroll then refresh the list.
	 * Success is shown before list refresh so a refresh failure
	 * cannot hide the enroll confirmation.
	 */
	const enrollUser = async () => {
		const parsed = parseInt( userId, 10 );
		if ( ! Number.isFinite( parsed ) || parsed < 1 ) {
			setNotices( [
				{
					status: 'error',
					message: __( 'Enter a valid user ID.', 'burbank-lms' ),
				},
			] );
			return;
		}

		setBusy( true );
		setNotices( [] );
		try {
			/* eslint-disable camelcase -- REST contract */
			await apiFetch( {
				path: `/${ config.restNamespace }/enrollments/admin`,
				method: 'POST',
				data: {
					user_id: parsed,
					course_id: postId,
				},
			} );
			/* eslint-enable camelcase */
			setUserId( '' );
			const successNotice = {
				status: 'success',
				message: __( 'Learner enrolled.', 'burbank-lms' ),
			};
			setNotices( [ successNotice ] );
			try {
				await loadEnrollments();
			} catch ( refreshError ) {
				setNotices( [
					successNotice,
					{
						status: 'warning',
						message:
							refreshError?.message ||
							__(
								'Learner enrolled, but the list could not be refreshed.',
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
						__( 'Could not enroll the learner.', 'burbank-lms' ),
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
					'You do not have permission to manage enrollments.',
					'burbank-lms'
				) }
			</p>
		);
	}

	if ( disabled ) {
		return (
			<p>
				{ __(
					'Save the course before managing enrollments.',
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
				label={ __( 'User ID', 'burbank-lms' ) }
				type="number"
				min={ 1 }
				value={ userId }
				onChange={ setUserId }
				disabled={ busy }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<p>
				<Button
					variant="primary"
					disabled={ busy || ! userId }
					onClick={ enrollUser }
				>
					{ __( 'Enroll user', 'burbank-lms' ) }
				</Button>
			</p>

			{ enrollments.length === 0 ? (
				<p>{ __( 'No enrollments yet.', 'burbank-lms' ) }</p>
			) : (
				<div className="burbank-lms-course-sidebar__table-scroll">
					<table className="burbank-lms-course-sidebar__table">
						<thead>
							<tr>
								<th>{ __( 'User', 'burbank-lms' ) }</th>
								<th>{ __( 'Status', 'burbank-lms' ) }</th>
								<th>{ __( 'Source', 'burbank-lms' ) }</th>
								<th>{ __( 'Enrolled', 'burbank-lms' ) }</th>
								<th>{ __( 'Passed', 'burbank-lms' ) }</th>
								<th>{ __( 'Completed', 'burbank-lms' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ enrollments.map( ( row ) => (
								<tr
									key={ `${ row.user_id }-${ row.enrolled_at }` }
								>
									<td>{ userLabel( row ) }</td>
									<td>{ row.status }</td>
									<td>{ row.source }</td>
									<td>{ cellDate( row.enrolled_at ) }</td>
									<td>{ cellDate( row.quiz_passed_at ) }</td>
									<td>{ cellDate( row.completed_at ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
		</>
	);
}
