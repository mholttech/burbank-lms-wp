const wordpressConfig = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpressConfig,
	{
		languageOptions: {
			globals: {
				demo_plugin: 'readonly',
				burbankLmsCourseEditor: 'readonly',
			},
		},
		rules: {
			'no-console': 'warn',
			camelcase: [
				'error',
				{
					allow: [
						'^demo_plugin',
						'^burbankLms',
						'^bfc_',
						'^pressprimer_',
						'^certificate_template_id$',
						'^matrix_requirement_slug$',
						'^course_revision$',
						'^subject_category$',
						'^course_outcome$',
						'^catalog_state$',
						'^source_revision$',
					],
				},
			],
			// @wordpress/* packages are webpack externals provided by WordPress;
			// they are not installed as local node_modules in this project.
			'import/no-unresolved': [
				'error',
				{ ignore: [ '^@wordpress/' ] },
			],
			'import/no-extraneous-dependencies': [
				'error',
				{
					peerDependencies: true,
					packageDir: __dirname,
					devDependencies: true,
				},
			],
		},
		settings: {
			'import/core-modules': [
				'@wordpress/components',
				'@wordpress/core-data',
				'@wordpress/data',
				'@wordpress/dom-ready',
				'@wordpress/edit-post',
				'@wordpress/editor',
				'@wordpress/element',
				'@wordpress/i18n',
				'@wordpress/plugins',
				'@wordpress/api-fetch',
				'@wordpress/block-editor',
				'@wordpress/blocks',
			],
		},
	},
];
