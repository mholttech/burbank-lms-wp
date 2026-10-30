<?php
/**
 * Pods pick sources for course authoring fields.
 *
 * @package Burbank_LMS
 */

use Burbank_LMS\Courses\Picks;
use Burbank_LMS\Courses\Schema;

/** Exercise Pods related-object registration for quiz, template, and matrix picks. */
class CoursePicksTest extends WP_UnitTestCase {
	/** Recreate schema between cases. */
	public function set_up(): void {
		parent::set_up();
		if ( post_type_exists( 'bc_requirement' ) ) {
			unregister_post_type( 'bc_requirement' );
		}
		pods_api()->cache_flush_pods();
		delete_option( Schema::VERSION_OPTION );
		delete_option( Schema::EDITORIAL_UI_OPTION );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/** Drop optional matrix type and posts registered by individual cases. */
	public function tear_down(): void {
		$requirement_ids = get_posts(
			array(
				'post_type'              => 'bc_requirement',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);
		foreach ( $requirement_ids as $requirement_id ) {
			wp_delete_post( (int) $requirement_id, true );
		}
		if ( post_type_exists( 'bc_requirement' ) ) {
			unregister_post_type( 'bc_requirement' );
		}
		parent::tear_down();
	}

	/** Related-object callbacks expose only published quizzes. */
	public function test_quiz_pick_lists_published_quizzes_only(): void {
		PressPrimer_Quiz_Activator::activate();
		$published = PressPrimer_Quiz_Quiz::create(
			array(
				'title'  => 'Published quiz',
				'status' => 'published',
			)
		);
		$draft     = PressPrimer_Quiz_Quiz::create(
			array(
				'title'  => 'Draft quiz',
				'status' => 'draft',
			)
		);
		$archived  = PressPrimer_Quiz_Quiz::create(
			array(
				'title'  => 'Archived quiz',
				'status' => 'archived',
			)
		);

		$picks = new Picks();
		$picks->register();
		$data = $picks->quiz_data();

		$this->assertArrayHasKey( (string) $published, $data );
		$this->assertSame( 'Published quiz', $data[ (string) $published ] );
		$this->assertArrayNotHasKey( (string) $draft, $data );
		$this->assertArrayNotHasKey( (string) $archived, $data );
	}

	/** Related-object callbacks expose only published certificate templates. */
	public function test_template_pick_lists_published_templates_only(): void {
		PressPrimer_Certificate_Activator::activate();
		$published = PressPrimer_Certificate_Template::create(
			array(
				'title'  => 'Published template',
				'status' => 'published',
				'layout' => array( 'page' => array( 'size' => 'a4' ) ),
			)
		);
		$draft     = PressPrimer_Certificate_Template::create(
			array(
				'title'  => 'Draft template',
				'status' => 'draft',
				'layout' => array( 'page' => array( 'size' => 'a4' ) ),
			)
		);
		$archived  = PressPrimer_Certificate_Template::create(
			array(
				'title'  => 'Archived template',
				'status' => 'archived',
				'layout' => array( 'page' => array( 'size' => 'a4' ) ),
			)
		);

		$picks = new Picks();
		$picks->register();
		$data = $picks->template_data();

		$this->assertArrayHasKey( (string) $published, $data );
		$this->assertSame( 'Published template', $data[ (string) $published ] );
		$this->assertArrayNotHasKey( (string) $draft, $data );
		$this->assertArrayNotHasKey( (string) $archived, $data );
	}

	/** Matrix requirement picks store published post_name keys when the type exists. */
	public function test_requirement_pick_uses_published_slugs(): void {
		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		$published = self::factory()->post->create(
			array(
				'post_type'   => 'bc_requirement',
				'post_status' => 'publish',
				'post_name'   => 'scene-safety',
				'post_title'  => 'Scene Safety',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'   => 'bc_requirement',
				'post_status' => 'draft',
				'post_name'   => 'draft-req',
				'post_title'  => 'Draft Requirement',
			)
		);

		$picks = new Picks();
		$picks->register();
		$data = $picks->requirement_data();

		$this->assertArrayHasKey( 'scene-safety', $data );
		$this->assertSame( 'Scene Safety', $data['scene-safety'] );
		$this->assertArrayNotHasKey( 'draft-req', $data );
		$this->assertArrayNotHasKey( (string) $published, $data );
	}

	/** Schema editorial UI wires quiz and template fields to registered pick objects. */
	public function test_schema_wires_quiz_and_template_pick_objects(): void {
		( new Picks() )->register();
		( new Schema() )->register();

		$quiz     = pods_api()->load_field(
			array(
				'name' => 'pressprimer_quiz_id',
				'pod'  => 'bfc_course',
			)
		);
		$template = pods_api()->load_field(
			array(
				'name' => 'certificate_template_id',
				'pod'  => 'bfc_course',
			)
		);

		$this->assertSame( 'pick', $quiz['type'] );
		$this->assertSame( Picks::QUIZ_OBJECT, $quiz['pick_object'] );
		$this->assertSame( 'pick', $template['type'] );
		$this->assertSame( Picks::TEMPLATE_OBJECT, $template['pick_object'] );
	}

	/** Matrix field becomes a slug pick only when bc_requirement exists; otherwise stays text. */
	public function test_matrix_field_pick_when_requirement_type_exists(): void {
		( new Picks() )->register();
		( new Schema() )->register();
		$without = pods_api()->load_field(
			array(
				'name' => 'matrix_requirement_slug',
				'pod'  => 'bfc_course',
			)
		);
		$this->assertSame( 'text', $without['type'] );

		register_post_type(
			'bc_requirement',
			array(
				'public' => true,
				'label'  => 'Requirements',
			)
		);
		delete_option( Schema::EDITORIAL_UI_OPTION );
		( new Picks() )->register();
		( new Schema() )->register();
		$with = pods_api()->load_field(
			array(
				'name' => 'matrix_requirement_slug',
				'pod'  => 'bfc_course',
			)
		);
		$this->assertSame( 'pick', $with['type'] );
		$this->assertSame( Picks::REQUIREMENT_OBJECT, $with['pick_object'] );
	}
}
