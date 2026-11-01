import { __ } from '@wordpress/i18n';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

const ALLOWED_BLOCKS = [
	'core/group',
	'core/heading',
	'core/paragraph',
	'core/post-title',
	'core/post-content',
	'core/post-featured-image',
	'burbank-lms/catalog',
	'burbank-lms/course',
	'burbank-lms/my-courses',
	'burbank-lms/profile',
	'burbank-lms/enroll',
];

const TEMPLATE = [
	[
		'core/group',
		{ layout: { type: 'constrained' } },
		[
			[
				'core/paragraph',
				{ placeholder: __( 'Add learner content…', 'burbank-lms' ) },
			],
		],
	],
];

export default function Edit() {
	return (
		<div { ...useBlockProps( { className: 'bfc-shell-block' } ) }>
			<p className="bfc-shell-block__hint">
				{ __(
					'Learner shell: training nav prints once around the blocks below.',
					'burbank-lms'
				) }
			</p>
			<InnerBlocks
				allowedBlocks={ ALLOWED_BLOCKS }
				template={ TEMPLATE }
			/>
		</div>
	);
}
