/**
 * Course PluginSidebar chrome with four PanelBody sections.
 */

import { PanelBody } from '@wordpress/components';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { __ } from '@wordpress/i18n';

import DetailsPanel from './panels/details';

export const PLUGIN_NAME = 'burbank-lms-course';
export const SIDEBAR_NAME = 'course';
export const SIDEBAR_ID = `${ PLUGIN_NAME }/${ SIDEBAR_NAME }`;

/**
 * Course document sidebar + more-menu item.
 *
 * @return {Object} Sidebar plugin UI.
 */
export default function CourseSidebar() {
	const title = __( 'Course', 'burbank-lms' );

	return (
		<>
			<PluginSidebarMoreMenuItem target={ SIDEBAR_NAME }>
				{ title }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar
				name={ SIDEBAR_NAME }
				title={ title }
				className="burbank-lms-course-sidebar"
				icon="welcome-learn-more"
			>
				<PanelBody
					title={ __( 'Details', 'burbank-lms' ) }
					initialOpen={ true }
					className="burbank-lms-course-sidebar__panel"
				>
					<DetailsPanel />
				</PanelBody>
				<PanelBody
					title={ __( 'Approval', 'burbank-lms' ) }
					initialOpen={ false }
					className="burbank-lms-course-sidebar__panel"
				/>
				<PanelBody
					title={ __( 'Invites', 'burbank-lms' ) }
					initialOpen={ false }
					className="burbank-lms-course-sidebar__panel"
				/>
				<PanelBody
					title={ __( 'Enrollments', 'burbank-lms' ) }
					initialOpen={ false }
					className="burbank-lms-course-sidebar__panel"
				/>
			</PluginSidebar>
		</>
	);
}
