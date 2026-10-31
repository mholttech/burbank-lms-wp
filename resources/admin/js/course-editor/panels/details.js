/**
 * Course Details panel — editable post meta via useEntityProp.
 */

import { TextControl, SelectControl } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

import { getConfig, mapToOptions } from '../config';

const HELP = {
	bfc_code: __(
		'Use BFC-100 through BFC-999. The first published code is permanent.',
		'burbank-lms'
	),
	course_revision: __(
		'Use a new revision for material assessed changes. Approval must be renewed.',
		'burbank-lms'
	),
	course_outcome: __(
		'Use independent_study for a knowledge course.',
		'burbank-lms'
	),
	catalog_state: __(
		'Use draft, published, or archived. Archive retains identity and approval history; WordPress post status controls publication.',
		'burbank-lms'
	),
	pressprimer_quiz_id: __(
		'Select a published PressPrimer quiz.',
		'burbank-lms'
	),
	certificate_template_id: __(
		'Select a published certificate template with an active quiz trigger for this course.',
		'burbank-lms'
	),
	matrix_requirement_slug: __(
		'Optional published matrix requirement slug when bc_requirement exists.',
		'burbank-lms'
	),
};

/**
 * Coerce a select value to a positive integer ID, or null when cleared.
 *
 * Registered meta accepts integer|null in REST; null deletes persisted meta so
 * reload does not restore a prior ID. Omitted keys alone leave meta unchanged.
 *
 * @param {string} value SelectControl value.
 * @return {number|null} Positive integer ID, or null when cleared.
 */
function toIntegerId( value ) {
	if ( value === '' || value === null || value === undefined ) {
		return null;
	}
	const parsed = parseInt( value, 10 );
	return Number.isFinite( parsed ) && parsed > 0 ? parsed : null;
}

/**
 * Stringify meta for SelectControl (always string values).
 *
 * @param {*} value Meta value.
 * @return {string} SelectControl-compatible value.
 */
function asSelectValue( value ) {
	if (
		value === null ||
		value === undefined ||
		value === 0 ||
		value === ''
	) {
		return '';
	}
	return String( value );
}

/**
 * Details fields bound to bfc_course post meta.
 *
 * @return {Object} Details panel controls.
 */
export default function DetailsPanel() {
	const config = getConfig();
	const [ meta, setMeta ] = useEntityProp( 'postType', 'bfc_course', 'meta' );

	const safeMeta = meta && typeof meta === 'object' ? meta : {};

	/**
	 * Update a meta key. Pass null for integer fields to delete via REST.
	 *
	 * @param {string} key   Meta key.
	 * @param {*}      value Next value (null deletes integer meta).
	 */
	const update = ( key, value ) => {
		setMeta( { ...safeMeta, [ key ]: value } );
	};

	const subjectHelp =
		__(
			'Choose the subject matching the first digit of the BFC code:',
			'burbank-lms'
		) +
		' ' +
		Object.keys( config.subjects ).join( ', ' );

	const selectEmpty = __( '— Select —', 'burbank-lms' );

	return (
		<>
			<TextControl
				label={ __( 'BFC code', 'burbank-lms' ) }
				value={ safeMeta.bfc_code ?? '' }
				onChange={ ( value ) => update( 'bfc_code', value ) }
				help={ HELP.bfc_code }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Course revision', 'burbank-lms' ) }
				value={ safeMeta.course_revision ?? '' }
				onChange={ ( value ) => update( 'course_revision', value ) }
				help={ HELP.course_revision }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<SelectControl
				label={ __( 'Subject category', 'burbank-lms' ) }
				value={ asSelectValue( safeMeta.subject_category ) }
				options={ mapToOptions( config.subjects, selectEmpty ) }
				onChange={ ( value ) => update( 'subject_category', value ) }
				help={ subjectHelp }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<SelectControl
				label={ __( 'Course outcome', 'burbank-lms' ) }
				value={ asSelectValue( safeMeta.course_outcome ) }
				options={ mapToOptions( config.outcomes, selectEmpty ) }
				onChange={ ( value ) => update( 'course_outcome', value ) }
				help={ HELP.course_outcome }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<SelectControl
				label={ __( 'PressPrimer quiz', 'burbank-lms' ) }
				value={ asSelectValue( safeMeta.pressprimer_quiz_id ) }
				options={ mapToOptions( config.quizzes, selectEmpty ) }
				onChange={ ( value ) =>
					update( 'pressprimer_quiz_id', toIntegerId( value ) )
				}
				help={ HELP.pressprimer_quiz_id }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<SelectControl
				label={ __( 'Certificate template', 'burbank-lms' ) }
				value={ asSelectValue( safeMeta.certificate_template_id ) }
				options={ mapToOptions( config.templates, selectEmpty ) }
				onChange={ ( value ) =>
					update( 'certificate_template_id', toIntegerId( value ) )
				}
				help={ HELP.certificate_template_id }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			{ config.hasRequirements &&
			Object.keys( config.requirements ).length > 0 ? (
				<SelectControl
					label={ __( 'Matrix requirement', 'burbank-lms' ) }
					value={ asSelectValue( safeMeta.matrix_requirement_slug ) }
					options={ mapToOptions( config.requirements, selectEmpty ) }
					onChange={ ( value ) =>
						update( 'matrix_requirement_slug', value )
					}
					help={ HELP.matrix_requirement_slug }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) : (
				<TextControl
					label={ __( 'Matrix requirement slug', 'burbank-lms' ) }
					value={ safeMeta.matrix_requirement_slug ?? '' }
					onChange={ ( value ) =>
						update( 'matrix_requirement_slug', value )
					}
					help={ HELP.matrix_requirement_slug }
					__nextHasNoMarginBottom
					__next40pxDefaultSize
				/>
			) }
			<SelectControl
				label={ __( 'Catalog state', 'burbank-lms' ) }
				value={ asSelectValue( safeMeta.catalog_state ) }
				options={ mapToOptions( config.catalogStates, selectEmpty ) }
				onChange={ ( value ) => update( 'catalog_state', value ) }
				help={ HELP.catalog_state }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
			<TextControl
				label={ __( 'Source revision', 'burbank-lms' ) }
				value={ safeMeta.source_revision ?? '' }
				onChange={ ( value ) => update( 'source_revision', value ) }
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</>
	);
}
