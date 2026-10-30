import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-catalog' } ) }>
			<p>
				{ __(
					'Course catalog listing (enroll CTAs for verified learners).',
					'burbank-lms'
				) }
			</p>
		</div>
	);
}
