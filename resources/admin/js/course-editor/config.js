/**
 * Localized course-editor config from `Courses\Editor::LOCALIZE_OBJECT`.
 *
 * @return {Object} window.burbankLmsCourseEditor payload (or empty defaults).
 */
export function getConfig() {
	const raw =
		typeof window !== 'undefined' ? window.burbankLmsCourseEditor : null;

	return {
		subjects: raw?.subjects ?? {},
		outcomes: raw?.outcomes ?? {},
		catalogStates: raw?.catalogStates ?? {},
		quizzes: raw?.quizzes ?? {},
		templates: raw?.templates ?? {},
		requirements: raw?.requirements ?? {},
		hasRequirements: Boolean( raw?.hasRequirements ),
		caps: raw?.caps ?? { approve: false, enroll: false },
		restNamespace: raw?.restNamespace ?? 'burbank-lms/v1',
	};
}

/**
 * Convert an id/label map into SelectControl options.
 *
 * @param {Object.<string, string>} map        Value => label map.
 * @param {string}                  emptyLabel Label for the empty option.
 * @return {Array.<{label: string, value: string}>} SelectControl options.
 */
export function mapToOptions( map, emptyLabel ) {
	const options = [ { label: emptyLabel, value: '' } ];
	Object.entries( map || {} ).forEach( ( [ value, label ] ) => {
		options.push( { value: String( value ), label: String( label ) } );
	} );
	return options;
}
