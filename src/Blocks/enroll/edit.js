import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { courseId } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Enroll settings', 'burbank-lms' ) }>
					<TextControl
						label={ __( 'Default course ID', 'burbank-lms' ) }
						type="number"
						value={ courseId || '' }
						onChange={ ( value ) =>
							setAttributes( {
								courseId: value ? Number( value ) : 0,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'bfc-enroll' } ) }>
				<p>
					{ __(
						'Standalone enroll / course-invite form (REST-backed).',
						'burbank-lms'
					) }
				</p>
			</div>
		</>
	);
}
