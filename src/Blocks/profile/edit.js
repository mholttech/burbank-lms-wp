import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-profile' } ) }>
			<p>
				{ __(
					'Learner profile: editable first/last certificate name, display name select, email; separate security form for password and sessions.',
					'burbank-lms'
				) }
			</p>
		</div>
	);
}
