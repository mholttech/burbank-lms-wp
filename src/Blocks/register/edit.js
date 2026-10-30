import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-register' } ) }>
			<p>
				{ __(
					'Learner registration form (site invite, legal name, display name, email, password).',
					'burbank-lms'
				) }
			</p>
		</div>
	);
}
