import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-my-courses' } ) }>
			<p>
				{ __(
					'My Courses list (REST-backed enrollments and withdraw).',
					'burbank-lms'
				) }
			</p>
		</div>
	);
}
