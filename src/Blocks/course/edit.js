import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-course' } ) }>
			<p>
				{ __(
					'Course detail: enroll CTA or invite field; quiz link when enrolled.',
					'burbank-lms'
				) }
			</p>
		</div>
	);
}
