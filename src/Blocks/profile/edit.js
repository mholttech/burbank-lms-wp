import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-profile' } ) }>
			<p>
				{ __(
					'Learner profile: display name, email, password; legal name read-only.',
					'burbank-lms'
				) }
			</p>
		</div>
	);
}
