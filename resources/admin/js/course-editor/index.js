/**
 * Course block-editor sidebar entry.
 *
 * Registers the Course PluginSidebar and opens it once per editor load.
 */

import domReady from '@wordpress/dom-ready';
import { dispatch, select } from '@wordpress/data';
import { store as editPostStore } from '@wordpress/edit-post';
import { registerPlugin } from '@wordpress/plugins';

import CourseSidebar, { PLUGIN_NAME, SIDEBAR_ID } from './sidebar';

registerPlugin( PLUGIN_NAME, {
	render: CourseSidebar,
	icon: 'welcome-learn-more',
} );

domReady( () => {
	if (
		select( editPostStore ).getActiveGeneralSidebarName() !== SIDEBAR_ID
	) {
		dispatch( editPostStore ).openGeneralSidebar( SIDEBAR_ID );
	}
} );
