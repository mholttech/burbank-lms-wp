<?php
/**
 * Course schema application coverage.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Schema;
use Burbank_LMS\Courses\Capabilities;

/** Tests the required Pods schema and editorial boundaries. */
class CourseSchemaTest extends WP_UnitTestCase {
	/** WordPress clears schema posts between classes; flush Pods' process cache too. */
	public function set_up(): void {
		parent::set_up();
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
	}

	/** The course uses native editor and REST support. */
	public function test_course_registration(): void {
		$schema = new Schema();
		$schema->register();
		$type = get_post_type_object( 'bfc_course' );
		$this->assertNotNull( $type );
		$this->assertTrue( $type->show_in_rest );
		foreach ( array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ) as $feature ) {
			$this->assertTrue( post_type_supports( 'bfc_course', $feature ), $feature );
		}
		$this->assertSame( 'edit_bfc_courses', $type->cap->edit_posts );
		$pod = pods_api()->load_pod( array( 'name' => 'bfc_course' ) );
		$this->assertSame( 'meta', $pod['storage'] );
		$registered = get_registered_meta_keys( 'post', 'bfc_course' );
		foreach ( Schema::FIELDS as $field => $type ) {
			$this->assertArrayHasKey( $field, $pod['fields'] );
			$this->assertArrayHasKey( $field, $registered );
			$this->assertTrue( $registered[ $field ]['show_in_rest'] );
		}
	}

	/** Repeated provisioning retains data and unrelated settings. */
	public function test_repeated_setup_preserves_data(): void {
		$schema = new Schema();
		$schema->register();
		$id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		update_post_meta( $id, 'bfc_code', 'BFC-210' );
		$pod = pods_api()->load_pod( array( 'name' => 'bfc_course' ) );
		pods_api()->save_field(
			array(
				'pod'  => 'bfc_course',
				'name' => 'site_annotation',
				'type' => 'text',
			)
		);
		pods_api()->save_pod(
			array(
				'id'        => $pod['id'],
				'name'      => 'bfc_course',
				'menu_icon' => 'dashicons-heart',
			)
		);
		delete_option( Schema::VERSION_OPTION );
		$schema->register();
		$this->assertSame( 'BFC-210', get_post_meta( $id, 'bfc_code', true ) );
		$pod = pods_api()->load_pod( array( 'name' => 'bfc_course' ) );
		$this->assertSame( 'dashicons-heart', $pod['menu_icon'] );
		$this->assertArrayHasKey( 'site_annotation', $pod['fields'] );
		$schema->register();
		$this->assertSame( $pod['id'], pods_api()->load_pod( array( 'name' => 'bfc_course' ) )['id'] );
	}

	/** Existing foreign post types cannot be adopted. */
	public function test_conflicting_type_is_retained(): void {
		$schema = new Schema();
		$schema->register();
		unregister_post_type( 'bfc_course' );
		register_post_type( 'bfc_course', array( 'label' => 'Foreign courses' ) );
		$schema->register();
		$this->assertSame( 'Foreign courses', get_post_type_object( 'bfc_course' )->label );
		$this->assertSame( 'burbank_course_type_conflict', $schema->get_error()->get_error_code() );
		unregister_post_type( 'bfc_course' );
		PodsInit::$content_types_registered['post_types'] = array_diff( PodsInit::$content_types_registered['post_types'], array( 'bfc_course' ) );
		pods_init()->setup_content_types( true );
	}

	/** Runtime API failure is actionable and preserves stored data. */
	public function test_missing_pods_fails_closed(): void {
		( new Schema() )->register();
		$id = self::factory()->post->create( array( 'post_type' => 'bfc_course' ) );
		update_post_meta( $id, 'bfc_code', 'BFC-210' );
		$schema = new Schema( static fn (): bool => false );
		$schema->register();
		$this->assertSame( 'burbank_pods_required', $schema->get_error()->get_error_code() );
		$this->assertSame( 'BFC-210', get_post_meta( $id, 'bfc_code', true ) );
	}

	/** Only administrators receive approval; subscribers cannot edit courses. */
	public function test_role_boundaries(): void {
		( new Capabilities() )->install();
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'approve_bfc_courses' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_bfc_courses' ) );
		$this->assertFalse( get_role( 'editor' )->has_cap( 'approve_bfc_courses' ) );
		$this->assertFalse( get_role( 'subscriber' )->has_cap( 'edit_bfc_courses' ) );
		$this->assertFalse( get_role( 'subscriber' )->has_cap( 'publish_bfc_courses' ) );
		( new Schema() )->register();
		$editor  = self::factory()->user->create( array( 'role' => 'editor' ) );
		$learner = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$course  = self::factory()->post->create(
			array(
				'post_type'   => 'bfc_course',
				'post_author' => $editor,
			)
		);
		$this->assertTrue( user_can( $editor, 'edit_post', $course ) );
		$this->assertFalse( user_can( $learner, 'edit_post', $course ) );
	}
}
