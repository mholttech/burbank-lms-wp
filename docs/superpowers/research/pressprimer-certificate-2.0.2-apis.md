# PressPrimer Certificate 2.0.2 — REST, shortcodes, capabilities, public methods

## REST routes

| Namespace | Route | File |
| --- | --- | --- |
| `ppcert/v1` | `/certificates` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| `ppcert/v1` | `/certificates/(?P<credential_id>[A-Za-z0-9\-]+)/pdf` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| `ppcert/v1` | `/certificates/(?P<id>\d+)` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| `ppcert/v1` | `/dashboard` | `includes/api/class-ppcert-rest-dashboard-controller.php` |
| `ppcert/v1` | `/dashboard/chart` | `includes/api/class-ppcert-rest-dashboard-controller.php` |
| `ppcert/v1` | `/email-optin` | `includes/api/class-ppcert-rest-email-optin-controller.php` |
| `ppcert/v1` | `/merge-fields` | `includes/api/class-ppcert-rest-merge-fields-controller.php` |
| `ppcert/v1` | `/merge-fields/post-meta-keys` | `includes/api/class-ppcert-rest-merge-fields-controller.php` |
| `ppcert/v1` | `/merge-fields/user-meta-keys` | `includes/api/class-ppcert-rest-merge-fields-controller.php` |
| `ppcert/v1` | `/settings` | `includes/api/class-ppcert-rest-settings-controller.php` |
| `ppcert/v1` | `/settings/test-email` | `includes/api/class-ppcert-rest-settings-controller.php` |
| `ppcert/v1` | `/templates` | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert/v1` | `/templates/(?P<id>\d+)` | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert/v1` | `/templates/(?P<id>\d+)/preview` | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert/v1` | `/templates/(?P<id>\d+)/test-email` | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert/v1` | `/templates/(?P<id>\d+)/triggers` | `includes/api/class-ppcert-rest-triggers-controller.php` |
| `ppcert/v1` | `/trigger-types` | `includes/api/class-ppcert-rest-triggers-controller.php` |
| `ppcert/v1` | `/users/search` | `includes/api/class-ppcert-rest-certificates-controller.php` |
| `ppcert/v1` | `/verify/(?P<credential_id>[A-Za-z0-9\-_.]{1,40})` | `includes/api/class-ppcert-rest-verification-controller.php` |

## Shortcodes

- `[ppcert_certificate_link]` — `includes/frontend/class-ppcert-certificate-link.php`
- `[ppcert_my_certificates]` — `includes/frontend/class-ppcert-my-certificates.php`
- `[ppcert_verify]` — `includes/frontend/class-ppcert-verification-page.php`

## Capabilities

- *(none via add_cap/remove_cap)*

## Public methods

### `PressPrimer_Certificate_Addon_Manager`

- `public static function get_instance()` — `class-ppcert-addon-manager.php`
- `public function init()` — `class-ppcert-addon-manager.php`
- `public function register_addon($addon_id, $version = '', $features = [], $args = [])` — `class-ppcert-addon-manager.php`
- `public function render_refusal_notices()` — `class-ppcert-addon-manager.php`
- `public function get_refused()` — `class-ppcert-addon-manager.php`
- `public function is_tier_active($tier)` — `class-ppcert-addon-manager.php`
- `public function get_upgrade_tiers()` — `class-ppcert-addon-manager.php`
- `public function get_upsell_touchpoints()` — `class-ppcert-addon-manager.php`
- `public function has_addon($addon_id)` — `class-ppcert-addon-manager.php`
- `public function feature_enabled($feature)` — `class-ppcert-addon-manager.php`
- `public function get_addons()` — `class-ppcert-addon-manager.php`
- `public function reset()` — `class-ppcert-addon-manager.php`

### `PressPrimer_Certificate_Admin`

- `public function init()` — `admin/class-ppcert-admin.php`
- `public function handle_duplicate_action()` — `admin/class-ppcert-admin.php`
- `public function handle_trash_action()` — `admin/class-ppcert-admin.php`
- `public function maybe_redirect_to_setup()` — `admin/class-ppcert-admin.php`
- `public static function branding($refresh = false)` — `admin/class-ppcert-admin.php`
- `public static function branded_name($shipped)` — `admin/class-ppcert-admin.php`
- `public function register_menus()` — `admin/class-ppcert-admin.php`
- `public function announce_menu_registered()` — `admin/class-ppcert-admin.php`
- `public function render_templates_page()` — `admin/class-ppcert-admin.php`
- `public function enqueue_assets($hook_suffix)` — `admin/class-ppcert-admin.php`
- `public static function build_font_face_css(array $fonts)` — `admin/class-ppcert-admin.php`

### `PressPrimer_Certificate_Admin_Certificates`

- `public function init()` — `admin/class-ppcert-admin-certificates.php`
- `public function handle_revoke_action()` — `admin/class-ppcert-admin-certificates.php`
- `public function handle_reinstate_action()` — `admin/class-ppcert-admin-certificates.php`
- `public function handle_delete_action()` — `admin/class-ppcert-admin-certificates.php`
- `public function handle_resend_action()` — `admin/class-ppcert-admin-certificates.php`
- `public function register_menu()` — `admin/class-ppcert-admin-certificates.php`
- `public function render_page()` — `admin/class-ppcert-admin-certificates.php`
- `public function enqueue_assets($hook_suffix)` — `admin/class-ppcert-admin-certificates.php`
- `public function handle_download()` — `admin/class-ppcert-admin-certificates.php`

### `PressPrimer_Certificate_Admin_Settings`

- `public function init()` — `admin/class-ppcert-admin-settings.php`
- `public function register_menu()` — `admin/class-ppcert-admin-settings.php`
- `public function render_page()` — `admin/class-ppcert-admin-settings.php`
- `public function enqueue_assets($hook_suffix)` — `admin/class-ppcert-admin-settings.php`
- `public static function table_status()` — `admin/class-ppcert-admin-settings.php`
- `public function ajax_repair_tables()` — `admin/class-ppcert-admin-settings.php`

### `PressPrimer_Certificate_Admin_User_Profile`

- `public function init()` — `admin/class-ppcert-admin-user-profile.php`
- `public function render_section($user)` — `admin/class-ppcert-admin-user-profile.php`
- `public function build_section(array $certificates, $page = 1, $total_pages = 1)` — `admin/class-ppcert-admin-user-profile.php`

### `PressPrimer_Certificate_Appearance_Service`

- `public static function get()` — `services/class-ppcert-appearance-service.php`
- `public static function apply_brand_colors(array $layout, array $roles)` — `services/class-ppcert-appearance-service.php`
- `public static function apply_image_slots(array $layout, array $slots)` — `services/class-ppcert-appearance-service.php`

### `PressPrimer_Certificate_Blocks`

- `public function init()` — `blocks/class-ppcert-blocks.php`
- `public function register_category($categories)` — `blocks/class-ppcert-blocks.php`
- `public function register_blocks()` — `blocks/class-ppcert-blocks.php`
- `public function render_verify_block()` — `blocks/class-ppcert-blocks.php`
- `public function render_my_certificates_block()` — `blocks/class-ppcert-blocks.php`
- `public static function certificate_link_trigger_types()` — `blocks/class-ppcert-blocks.php`
- `public function localize_certificate_link_block()` — `blocks/class-ppcert-blocks.php`
- `public function render_certificate_link_block($attributes = [])` — `blocks/class-ppcert-blocks.php`

### `PressPrimer_Certificate_Capabilities`

- `public static function setup_capabilities()` — `utilities/class-ppcert-capabilities.php`
- `public static function remove_capabilities()` — `utilities/class-ppcert-capabilities.php`
- `public static function get_all_capabilities()` — `utilities/class-ppcert-capabilities.php`

### `PressPrimer_Certificate_Certificate`

- `public static function table()` — `models/class-ppcert-certificate.php`
- `public static function events_table()` — `models/class-ppcert-certificate.php`
- `public static function get($id)` — `models/class-ppcert-certificate.php`
- `public static function query(array $args = [])` — `models/class-ppcert-certificate.php`
- `public static function get_by_credential_id($credential_id)` — `models/class-ppcert-certificate.php`
- `public static function get_for_verification($credential_id)` — `models/class-ppcert-certificate.php`
- `public static function effective_status($certificate)` — `models/class-ppcert-certificate.php`
- `public static function revoke($id, $reason = '')` — `models/class-ppcert-certificate.php`
- `public static function reinstate($id)` — `models/class-ppcert-certificate.php`
- `public static function set_directory_visibility($id, $visibility)` — `models/class-ppcert-certificate.php`
- `public static function delete($id)` — `models/class-ppcert-certificate.php`
- `public static function record_event($certificate_id, $event_type, $actor_id = null, $meta = [])` — `models/class-ppcert-certificate.php`
- `public static function find_duplicate($recipient_id, $template_id, $source_type, $source_ref)` — `models/class-ppcert-certificate.php`
- `public static function get_latest_for_recipient($recipient_id, array $args = [])` — `models/class-ppcert-certificate.php`
- `public static function display_title($row, $fallback = '')` — `models/class-ppcert-certificate.php`
- `public static function get_batch_for_recipient($recipient_id, $limit, $offset = 0)` — `models/class-ppcert-certificate.php`
- `public static function get_list_for_recipient($recipient_id, array $args = [])` — `models/class-ppcert-certificate.php`
- `public static function count_list_for_recipient($recipient_id, $status = 'all')` — `models/class-ppcert-certificate.php`
- `public static function get_recent_for_recipient($recipient_id, $limit, $offset = 0)` — `models/class-ppcert-certificate.php`
- `public static function count_for_recipient($recipient_id)` — `models/class-ppcert-certificate.php`
- `public static function count_all()` — `models/class-ppcert-certificate.php`
- `public static function count_issued_since($cutoff)` — `models/class-ppcert-certificate.php`
- `public static function count_events_since($event_type, $cutoff)` — `models/class-ppcert-certificate.php`
- `public static function get_daily_issue_counts($cutoff)` — `models/class-ppcert-certificate.php`
- `public static function get_top_templates($limit)` — `models/class-ppcert-certificate.php`
- `public static function source_types_for($user_id)` — `models/class-ppcert-certificate.php`
- `public static function scope_for($user_id)` — `models/class-ppcert-certificate.php`
- `public static function user_can_access($certificate, $user_id)` — `models/class-ppcert-certificate.php`

### `PressPrimer_Certificate_Certificate_Link`

- `public static function init()` — `frontend/class-ppcert-certificate-link.php`
- `public static function defaults()` — `frontend/class-ppcert-certificate-link.php`
- `public static function render_shortcode($atts = [])` — `frontend/class-ppcert-certificate-link.php`

### `PressPrimer_Certificate_Certificates_List_Table`

- `public function __construct()` — `admin/class-ppcert-certificates-list-table.php`
- `public function get_columns()` — `admin/class-ppcert-certificates-list-table.php`
- `public function prepare_items()` — `admin/class-ppcert-certificates-list-table.php`
- `public static function source_options($current)` — `admin/class-ppcert-certificates-list-table.php`
- `public function column_credential_id($item)` — `admin/class-ppcert-certificates-list-table.php`
- `public function column_default($item, $column_name)` — `admin/class-ppcert-certificates-list-table.php`
- `public function no_items()` — `admin/class-ppcert-certificates-list-table.php`

### `PressPrimer_Certificate_Credential_ID_Service`

- `public static function generate()` — `services/class-ppcert-credential-id-service.php`
- `public static function format_display($credential_id)` — `services/class-ppcert-credential-id-service.php`
- `public static function normalize($input)` — `services/class-ppcert-credential-id-service.php`
- `public static function is_well_formed($input)` — `services/class-ppcert-credential-id-service.php`
- `public static function is_credential_shaped($candidate)` — `services/class-ppcert-credential-id-service.php`

### `PressPrimer_Certificate_Deactivator`

- `public static function deactivate()` — `class-ppcert-deactivator.php`

### `PressPrimer_Certificate_Element_Types`

- `public static function get_types()` — `services/class-ppcert-element-types.php`

### `PressPrimer_Certificate_Email_Optin_Service`

- `public static function get_intake_url()` — `services/class-ppcert-email-optin-service.php`
- `public static function is_enabled()` — `services/class-ppcert-email-optin-service.php`
- `public static function is_valid_source($source)` — `services/class-ppcert-email-optin-service.php`
- `public static function get_consent($user_id)` — `services/class-ppcert-email-optin-service.php`
- `public static function has_answered($user_id)` — `services/class-ppcert-email-optin-service.php`
- `public static function record_opt_in($user_id, $source)` — `services/class-ppcert-email-optin-service.php`
- `public static function record_decline($user_id, $source)` — `services/class-ppcert-email-optin-service.php`
- `public static function record_dismissal($user_id, $surface)` — `services/class-ppcert-email-optin-service.php`
- `public static function is_dismissed($user_id, $surface)` — `services/class-ppcert-email-optin-service.php`
- `public static function is_eligible($user_id, $surface)` — `services/class-ppcert-email-optin-service.php`
- `public static function relay_opt_in($email, $source)` — `services/class-ppcert-email-optin-service.php`

### `PressPrimer_Certificate_Email_Service`

- `public static function send_issued($certificate_id, $context, $force_enabled = false)` — `services/class-ppcert-email-service.php`
- `public static function resend($certificate_id)` — `services/class-ppcert-email-service.php`
- `public static function build_recipient_email($certificate_id, $subject_template, $body_template, $email_type = 'recipient', array $context = [])` — `services/class-ppcert-email-service.php`
- `public static function from_header($email_type, array $context = [])` — `services/class-ppcert-email-service.php`
- `public static function apply_footer($body, $email_type, array $context = [])` — `services/class-ppcert-email-service.php`
- `public static function send_test($template)` — `services/class-ppcert-email-service.php`
- `public static function resolve_content($template, $email_context = 'issuance')` — `services/class-ppcert-email-service.php`
- `public static function template_tokens($template = null)` — `services/class-ppcert-email-service.php`
- `public static function settings()` — `services/class-ppcert-email-service.php`

### `PressPrimer_Certificate_Email_Template`

- `public static function table()` — `models/class-ppcert-email-template.php`
- `public static function get($id)` — `models/class-ppcert-email-template.php`
- `public static function exists($id)` — `models/class-ppcert-email-template.php`
- `public static function get_active($id)` — `models/class-ppcert-email-template.php`

### `PressPrimer_Certificate_Event_Pruner`

- `public static function init()` — `services/class-ppcert-event-pruner.php`
- `public static function schedule()` — `services/class-ppcert-event-pruner.php`
- `public static function unschedule()` — `services/class-ppcert-event-pruner.php`
- `public static function prune()` — `services/class-ppcert-event-pruner.php`

### `PressPrimer_Certificate_Font_Cache_Service`

- `public static function ttf_path(array $variant)` — `services/class-ppcert-font-cache-service.php`
- `public static function ttf_url(array $variant)` — `services/class-ppcert-font-cache-service.php`
- `public static function delete_cache()` — `services/class-ppcert-font-cache-service.php`

### `PressPrimer_Certificate_LMS_Adapter`

- `public function supports_past_completions()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function register()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_label()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_source_group_label()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_integration_label()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_short_label()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_source_levels()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_source_post_types()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function has_sources()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function detect_embedded_sources($post)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_any_source_label()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function register_trigger_type($types)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function resolve_course_title(array $context)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function resolve_source_completed_date(array $context)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function resolve_course_instructor(array $context)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function resolve_source_quiz_title(array $context)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function resolve_source_score(array $context)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function resolve_source_grade(array $context)` — `integrations/abstract-ppcert-lms-adapter.php`
- `public function register_merge_fields($fields, $context = 'designer')` — `integrations/abstract-ppcert-lms-adapter.php`

### `PressPrimer_Certificate_Layout_Validator`

- `public static function validate(array $raw)` — `services/class-ppcert-layout-validator.php`
- `public static function layout_pages(array $layout)` — `services/class-ppcert-layout-validator.php`
- `public static function migrate(array $raw)` — `services/class-ppcert-layout-validator.php`
- `public static function migrate_v2_to_v3(array $raw)` — `services/class-ppcert-layout-validator.php`
- `public static function get_registered_fonts()` — `services/class-ppcert-layout-validator.php`
- `public static function get_registered_font_slugs()` — `services/class-ppcert-layout-validator.php`

### `PressPrimer_Certificate_LearnDash_Lesson_Adapter`

- `public function get_id()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_source_levels()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function handle_lesson_completed($data)` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function resolve_lesson_title(array $context)` — `integrations/class-ppcert-learndash-lesson-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-learndash-lesson-adapter.php`

### `PressPrimer_Certificate_LearnDash_Quiz_Adapter`

- `public function get_id()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_source_levels()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function handle_quiz_completed($quizdata, $user = null)` — `integrations/class-ppcert-learndash-quiz-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-learndash-quiz-adapter.php`

### `PressPrimer_Certificate_LearnDash_Topic_Adapter`

- `public function get_id()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_source_levels()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function handle_topic_completed($data)` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function resolve_topic_title(array $context)` — `integrations/class-ppcert-learndash-topic-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-learndash-topic-adapter.php`

### `PressPrimer_Certificate_LearnPress_Quiz_Adapter`

- `public function get_id()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_source_levels()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function handle_quiz_finished($quiz_id, $course_id, $user_id, $user_quiz = null)` — `integrations/class-ppcert-learnpress-quiz-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-learnpress-quiz-adapter.php`

### `PressPrimer_Certificate_LifterLMS_Quiz_Adapter`

- `public function get_id()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_source_levels()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function handle_quiz_passed($student_id, $quiz_id, $attempt = null)` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-lifterlms-quiz-adapter.php`

### `PressPrimer_Certificate_Merge_Field_Registry`

- `public static function get_fields($context, $args = [])` — `services/class-ppcert-merge-field-registry.php`
- `public static function get_groups($context = 'designer')` — `services/class-ppcert-merge-field-registry.php`
- `public static function extract_tokens(array $layout)` — `services/class-ppcert-merge-field-registry.php`
- `public static function extract_tokens_from_text($text)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve($template_id, array $tokens, array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_title(array $context, array $values)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_title_fallback(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function get_last_resolution_failures()` — `services/class-ppcert-merge-field-registry.php`
- `public static function is_denylisted_user_meta_key($key)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_display_name(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_first_name(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_last_name(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_full_name(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_email(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_issue_date(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_credential_id(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_issuer_name(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_expiry_date(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_site_name(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_site_tagline(array $context)` — `services/class-ppcert-merge-field-registry.php`
- `public static function resolve_site_url(array $context)` — `services/class-ppcert-merge-field-registry.php`

### `PressPrimer_Certificate_Migrator`

- `public static function maybe_migrate()` — `database/class-ppcert-migrator.php`
- `public static function migrate_to_1_0_0()` — `database/class-ppcert-migrator.php`
- `public static function migrate_to_1_0_1()` — `database/class-ppcert-migrator.php`
- `public static function migrate_to_2_0_0()` — `database/class-ppcert-migrator.php`
- `public static function backfill_certificate_titles($batch_size = 500)` — `database/class-ppcert-migrator.php`

### `PressPrimer_Certificate_My_Certificates`

- `public static function init()` — `frontend/class-ppcert-my-certificates.php`
- `public static function render_shortcode($atts = [])` — `frontend/class-ppcert-my-certificates.php`

### `PressPrimer_Certificate_Onboarding`

- `public static function get_instance()` — `admin/class-ppcert-onboarding.php`
- `public static function get_relaunch_url()` — `admin/class-ppcert-onboarding.php`
- `public function should_show_onboarding()` — `admin/class-ppcert-onboarding.php`
- `public function complete_onboarding()` — `admin/class-ppcert-onboarding.php`
- `public function skip_onboarding($permanent = false)` — `admin/class-ppcert-onboarding.php`
- `public function reset_onboarding()` — `admin/class-ppcert-onboarding.php`
- `public function start_onboarding()` — `admin/class-ppcert-onboarding.php`
- `public function update_step($step)` — `admin/class-ppcert-onboarding.php`
- `public function get_onboarding_state()` — `admin/class-ppcert-onboarding.php`
- `public function get_js_data()` — `admin/class-ppcert-onboarding.php`
- `public function maybe_enqueue_assets($hook)` — `admin/class-ppcert-onboarding.php`
- `public function handle_progress_ajax()` — `admin/class-ppcert-onboarding.php`
- `public function handle_get_state_ajax()` — `admin/class-ppcert-onboarding.php`
- `public function maybe_handle_relaunch()` — `admin/class-ppcert-onboarding.php`

### `PressPrimer_Certificate_PPA_Adapter`

- `public function get_id()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_integration_label()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function is_available()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-ppa-adapter.php`
- `public function handle_submission_graded($submission_id, $score = 0)` — `integrations/class-ppcert-ppa-adapter.php`
- `public function resolve_assignment_title(array $context)` — `integrations/class-ppcert-ppa-adapter.php`
- `public function supports_past_completions()` — `integrations/class-ppcert-ppa-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-ppa-adapter.php`
- `public function detect_embedded_sources($post)` — `integrations/class-ppcert-ppa-adapter.php`

### `PressPrimer_Certificate_PPQ_Adapter`

- `public function get_id()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_integration_label()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function is_available()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-ppq-adapter.php`
- `public function handle_quiz_passed($attempt, $quiz)` — `integrations/class-ppcert-ppq-adapter.php`
- `public function supports_past_completions()` — `integrations/class-ppcert-ppq-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-ppq-adapter.php`
- `public function detect_embedded_sources($post)` — `integrations/class-ppcert-ppq-adapter.php`

### `PressPrimer_Certificate_Plugin`

- `public static function instance()` — `class-ppcert-plugin.php`
- `public function init()` — `class-ppcert-plugin.php`
- `public static function get_adapter_classes()` — `class-ppcert-plugin.php`
- `public static function get_integration_map()` — `class-ppcert-plugin.php`
- `public static function get_trigger_type_details()` — `class-ppcert-plugin.php`

### `PressPrimer_Certificate_Preview_Service`

- `public static function init()` — `services/class-ppcert-preview-service.php`
- `public static function delete_for_certificate($certificate_id)` — `services/class-ppcert-preview-service.php`
- `public static function delete_for_credential($certificate_id, $credential_id = '')` — `services/class-ppcert-preview-service.php`
- `public static function preview_path($credential_id)` — `services/class-ppcert-preview-service.php`
- `public static function preview_url($credential_id)` — `services/class-ppcert-preview-service.php`
- `public static function get_or_create($certificate)` — `services/class-ppcert-preview-service.php`
- `public static function delete($credential_id)` — `services/class-ppcert-preview-service.php`

### `PressPrimer_Certificate_Privacy`

- `public static function init()` — `utilities/class-ppcert-privacy.php`
- `public static function register_policy_content()` — `utilities/class-ppcert-privacy.php`
- `public static function policy_content()` — `utilities/class-ppcert-privacy.php`
- `public static function register_exporter($exporters)` — `utilities/class-ppcert-privacy.php`
- `public static function register_eraser($erasers)` — `utilities/class-ppcert-privacy.php`
- `public static function export($email, $page = 1)` — `utilities/class-ppcert-privacy.php`
- `public static function erase($email, $page = 1)` — `utilities/class-ppcert-privacy.php`

### `PressPrimer_Certificate_REST_Cache_Guard`

- `public static function init()` — `api/class-ppcert-rest-cache-guard.php`
- `public static function is_suite_route($route)` — `api/class-ppcert-rest-cache-guard.php`
- `public static function mark_uncacheable($response, $server, $request)` — `api/class-ppcert-rest-cache-guard.php`

### `PressPrimer_Certificate_REST_Certificates_Controller`

- `public function init()` — `api/class-ppcert-rest-certificates-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-certificates-controller.php`
- `public function can_view()` — `api/class-ppcert-rest-certificates-controller.php`
- `public function can_issue()` — `api/class-ppcert-rest-certificates-controller.php`
- `public function can_view_detail($request)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function get_list($request)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function validate_filter_date($value)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function validate_status_filter($value)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function get_detail($request)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function issue($request)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function download_pdf($request)` — `api/class-ppcert-rest-certificates-controller.php`
- `public function search_users($request)` — `api/class-ppcert-rest-certificates-controller.php`
- `public static function user_matches($search)` — `api/class-ppcert-rest-certificates-controller.php`

### `PressPrimer_Certificate_REST_Dashboard_Controller`

- `public function init()` — `api/class-ppcert-rest-dashboard-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-dashboard-controller.php`
- `public function can_view()` — `api/class-ppcert-rest-dashboard-controller.php`
- `public function get_dashboard()` — `api/class-ppcert-rest-dashboard-controller.php`
- `public function get_chart($request)` — `api/class-ppcert-rest-dashboard-controller.php`

### `PressPrimer_Certificate_REST_Email_Optin_Controller`

- `public function init()` — `api/class-ppcert-rest-email-optin-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-email-optin-controller.php`
- `public function check_permission()` — `api/class-ppcert-rest-email-optin-controller.php`
- `public function handle($request)` — `api/class-ppcert-rest-email-optin-controller.php`

### `PressPrimer_Certificate_REST_Merge_Fields_Controller`

- `public function init()` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public function can_manage()` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public function can_discover()` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public static function sanitize_search($value)` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public static function sanitize_trigger_types($value)` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public function get_registry($request)` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public function get_user_meta_keys($request)` — `api/class-ppcert-rest-merge-fields-controller.php`
- `public function get_post_meta_keys($request)` — `api/class-ppcert-rest-merge-fields-controller.php`

### `PressPrimer_Certificate_REST_Settings_Controller`

- `public function init()` — `api/class-ppcert-rest-settings-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-settings-controller.php`
- `public function send_test_email()` — `api/class-ppcert-rest-settings-controller.php`
- `public function can_manage()` — `api/class-ppcert-rest-settings-controller.php`
- `public function get_settings()` — `api/class-ppcert-rest-settings-controller.php`
- `public static function current_settings()` — `api/class-ppcert-rest-settings-controller.php`
- `public function update_settings($request)` — `api/class-ppcert-rest-settings-controller.php`

### `PressPrimer_Certificate_REST_Triggers_Controller`

- `public function init()` — `api/class-ppcert-rest-triggers-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-triggers-controller.php`
- `public function can_manage()` — `api/class-ppcert-rest-triggers-controller.php`
- `public static function sanitize_parents($value)` — `api/class-ppcert-rest-triggers-controller.php`
- `public function get_types($request)` — `api/class-ppcert-rest-triggers-controller.php`
- `public function get_triggers($request)` — `api/class-ppcert-rest-triggers-controller.php`
- `public function replace_triggers($request)` — `api/class-ppcert-rest-triggers-controller.php`

### `PressPrimer_Certificate_REST_Verification_Controller`

- `public function init()` — `api/class-ppcert-rest-verification-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-verification-controller.php`
- `public function verify($request)` — `api/class-ppcert-rest-verification-controller.php`
- `public static function lookup($raw)` — `api/class-ppcert-rest-verification-controller.php`
- `public static function build_actions($certificate, $status)` — `api/class-ppcert-rest-verification-controller.php`
- `public static function check_rate_limit()` — `api/class-ppcert-rest-verification-controller.php`

### `PressPrimer_Certificate_Rate_Limiter`

- `public static function allow($transient_key, $limit, $window)` — `utilities/class-ppcert-rate-limiter.php`

### `PressPrimer_Certificate_Schema`

- `public static function get_schema()` — `database/class-ppcert-schema.php`
- `public static function get_table_names()` — `database/class-ppcert-schema.php`

### `PressPrimer_Certificate_Template`

- `public static function table()` — `models/class-ppcert-template.php`
- `public static function get_all()` — `models/class-ppcert-template.php`
- `public static function query(array $args = [])` — `models/class-ppcert-template.php`
- `public static function duplicate($id, $author_id)` — `models/class-ppcert-template.php`
- `public static function create(array $args)` — `models/class-ppcert-template.php`
- `public static function update($id, array $args)` — `models/class-ppcert-template.php`
- `public static function trash($id)` — `models/class-ppcert-template.php`
- `public static function get_certificate_filter_templates()` — `models/class-ppcert-template.php`
- `public static function get_starters()` — `models/class-ppcert-template.php`
- `public static function get($id)` — `models/class-ppcert-template.php`
- `public static function sanitize_settings($settings)` — `models/class-ppcert-template.php`

### `PressPrimer_Certificate_Templates_List_Table`

- `public function __construct()` — `admin/class-ppcert-templates-list-table.php`
- `public function get_columns()` — `admin/class-ppcert-templates-list-table.php`
- `public function prepare_items()` — `admin/class-ppcert-templates-list-table.php`
- `public function column_title($item)` — `admin/class-ppcert-templates-list-table.php`
- `public function column_default($item, $column_name)` — `admin/class-ppcert-templates-list-table.php`
- `public function no_items()` — `admin/class-ppcert-templates-list-table.php`

### `PressPrimer_Certificate_Touchpoints`

- `public function init()` — `admin/class-ppcert-touchpoints.php`
- `public static function register_default_touchpoints($touchpoints)` — `admin/class-ppcert-touchpoints.php`
- `public static function get_eligible_for_surface($surface)` — `admin/class-ppcert-touchpoints.php`

### `PressPrimer_Certificate_Trigger`

- `public static function table()` — `models/class-ppcert-trigger.php`
- `public static function get_used_types()` — `models/class-ppcert-trigger.php`
- `public static function get($id)` — `models/class-ppcert-trigger.php`
- `public static function get_for_template($template_id)` — `models/class-ppcert-trigger.php`
- `public static function get_for_templates(array $template_ids)` — `models/class-ppcert-trigger.php`
- `public static function find_active($trigger_type, $source_ref)` — `models/class-ppcert-trigger.php`
- `public static function replace_for_template($template_id, array $triggers)` — `models/class-ppcert-trigger.php`
- `public static function delete_for_template($template_id)` — `models/class-ppcert-trigger.php`

### `PressPrimer_Certificate_Trigger_Registry`

- `public static function get_types()` — `services/class-ppcert-trigger-registry.php`
- `public static function get_type($id)` — `services/class-ppcert-trigger-registry.php`
- `public static function get_types_for_post_type($post_type)` — `services/class-ppcert-trigger-registry.php`
- `public static function sanitize_conditions($trigger_type, $conditions)` — `services/class-ppcert-trigger-registry.php`
- `public static function validate_trigger_source($trigger_type, $source_ref, array $conditions)` — `services/class-ppcert-trigger-registry.php`

### `PressPrimer_Certificate_TutorLMS_Quiz_Adapter`

- `public function get_id()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_source_levels()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_level_options(string $level, array $parents, string $search = '')` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_sources_for_parents(array $parents, string $search = '')` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_scope_condition_keys()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function handle_attempt_ended($attempt_id, $course_id = 0, $user_id = 0)` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function handle_attempt_reviewed($attempt_id)` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-tutorlms-quiz-adapter.php`

### `PressPrimer_Certificate_Upgrade_Page`

- `public function init()` — `admin/class-ppcert-upgrade-page.php`
- `public static function register_default_tiers($tiers)` — `admin/class-ppcert-upgrade-page.php`
- `public static function get_tiers()` — `admin/class-ppcert-upgrade-page.php`
- `public static function featured_tier(array $tiers)` — `admin/class-ppcert-upgrade-page.php`
- `public static function all_tiers_active(array $tiers)` — `admin/class-ppcert-upgrade-page.php`
- `public function enterprise_addon_active()` — `admin/class-ppcert-upgrade-page.php`
- `public function register_menu()` — `admin/class-ppcert-upgrade-page.php`
- `public function enqueue_menu_styles()` — `admin/class-ppcert-upgrade-page.php`
- `public function enqueue_page_styles($hook_suffix)` — `admin/class-ppcert-upgrade-page.php`
- `public function render_page()` — `admin/class-ppcert-upgrade-page.php`
- `public static function utm_url($url, $position, $campaign = 'upgrade-page')` — `admin/class-ppcert-upgrade-page.php`
- `public static function get_pricing_url($position, $campaign = 'upgrade-page')` — `admin/class-ppcert-upgrade-page.php`
- `public function render_cell_value($value)` — `admin/class-ppcert-upgrade-page.php`
- `public static function get_comparison_features()` — `admin/class-ppcert-upgrade-page.php`

### `PressPrimer_Certificate_Verification_Page`

- `public static function init()` — `frontend/class-ppcert-verification-page.php`
- `public static function register_assets()` — `frontend/class-ppcert-verification-page.php`
- `public static function render_shortcode($atts = [])` — `frontend/class-ppcert-verification-page.php`
- `public static function render_result($result)` — `frontend/class-ppcert-verification-page.php`
- `public static function create_page()` — `frontend/class-ppcert-verification-page.php`
- `public static function maybe_deleted_page_notice()` — `frontend/class-ppcert-verification-page.php`

### `PressPrimer_Certificate_View_Page`

- `public static function init()` — `frontend/class-ppcert-view-page.php`
- `public static function fire_head_action()` — `frontend/class-ppcert-view-page.php`
- `public static function register_rewrites()` — `frontend/class-ppcert-view-page.php`
- `public static function register_query_var($vars)` — `frontend/class-ppcert-view-page.php`
- `public static function inject_virtual_page($posts, $query)` — `frontend/class-ppcert-view-page.php`
- `public static function filter_content($content)` — `frontend/class-ppcert-view-page.php`
- `public static function render_content($certificate)` — `frontend/class-ppcert-view-page.php`
- `public static function render_action_links(array $actions)` — `frontend/class-ppcert-view-page.php`
- `public static function pdf_url($credential_id)` — `frontend/class-ppcert-view-page.php`
- `public static function view_url($credential_id)` — `frontend/class-ppcert-view-page.php`
- `public static function reset()` — `frontend/class-ppcert-view-page.php`

### `PressPrimer_Certificate_Virtual_Page`

- `public static function guard()` — `frontend/class-ppcert-virtual-page.php`
- `public static function is_active()` — `frontend/class-ppcert-virtual-page.php`
- `public static function reset()` — `frontend/class-ppcert-virtual-page.php`
- `public static function closed_for_stub($open, $post_id)` — `frontend/class-ppcert-virtual-page.php`
- `public static function zero_for_stub($count, $post_id)` — `frontend/class-ppcert-virtual-page.php`
- `public static function empty_comments_template($template)` — `frontend/class-ppcert-virtual-page.php`
- `public static function no_edit_link_for_stub($link, $post_id)` — `frontend/class-ppcert-virtual-page.php`
- `public static function remove_edit_node($wp_admin_bar)` — `frontend/class-ppcert-virtual-page.php`

### `is`

- `public function get_id()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_integration_label()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function is_available()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function handle_course_completed($user_id, $course_id)` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function supports_past_completions()` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-lifterlms-adapter.php`
- `public function get_id()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_integration_label()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function is_available()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function handle_course_completed($course_id, $user_id)` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function supports_past_completions()` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-tutorlms-adapter.php`
- `public function get_id()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_integration_label()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function is_available()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function handle_course_finished($course_id, $user_id, $user_item_id = 0)` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function supports_past_completions()` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-learnpress-adapter.php`
- `public function get_id()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_label()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_source_group_label()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_any_source_label()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_integration_label()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_short_label()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_source_post_types()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function is_available()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function register_listeners()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_sources(string $search = '')` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_conditions_schema()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_merge_fields()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function resolve_merge_data(array $context)` — `integrations/class-ppcert-learndash-adapter.php`
- `public function handle_course_completed($course_data)` — `integrations/class-ppcert-learndash-adapter.php`
- `public function supports_past_completions()` — `integrations/class-ppcert-learndash-adapter.php`
- `public function get_past_completions(string $source_ref)` — `integrations/class-ppcert-learndash-adapter.php`
- `public static function issue(array $args)` — `services/class-ppcert-issuance-service.php`

### `names`

- `public static function register()` — `class-ppcert-autoloader.php`
- `public static function autoload($class)` — `class-ppcert-autoloader.php`

### `never`

- `public static function activate($network_wide = false)` — `class-ppcert-activator.php`
- `public static function activate_new_site($new_site)` — `class-ppcert-activator.php`

### `renders`

- `public function render_page()` — `admin/class-ppcert-admin-dashboard.php`
- `public function enqueue()` — `admin/class-ppcert-admin-dashboard.php`
- `public function boot_data()` — `admin/class-ppcert-admin-dashboard.php`

### `that`

- `public function init()` — `api/class-ppcert-rest-templates-controller.php`
- `public function register_routes()` — `api/class-ppcert-rest-templates-controller.php`
- `public function can_manage_template($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function send_test_email($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function can_manage()` — `api/class-ppcert-rest-templates-controller.php`
- `public function list_templates($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function create_template($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function get_template($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function update_template($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function trash_template($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function preview_template($request)` — `api/class-ppcert-rest-templates-controller.php`
- `public function render_pdf(array $layout, array $merge_data, array $args = [])` — `services/class-ppcert-pdf-renderer.php`
- `public function get_last_render_warnings()` — `services/class-ppcert-pdf-renderer.php`
- `public static function render_certificate($certificate_id, $context = 'download')` — `services/class-ppcert-pdf-renderer.php`
- `public static function fit_text($text, $box_w, $box_h, $font_size, $line_height, $measure, $thresholds)` — `services/class-ppcert-pdf-renderer.php`
- `public static function is_unsupported_image_mime($mime, array $allowed)` — `services/class-ppcert-pdf-renderer.php`
- `public static function renderable_image_mimes()` — `services/class-ppcert-pdf-renderer.php`
- `public static function fit_box($image_w, $image_h, $box_w, $box_h, $fit)` — `services/class-ppcert-pdf-renderer.php`
- `public static function interpolate_tokens($content, array $merge_data)` — `services/class-ppcert-pdf-renderer.php`
- `public static function text_content_for_render(array $layout, array $element, array $merge_data)` — `services/class-ppcert-pdf-renderer.php`
- `public function render_png(array $layout, array $merge_data, array $args = [])` — `services/class-ppcert-pdf-renderer.php`
- `public static function parity_color($index)` — `services/class-ppcert-pdf-renderer.php`
- `public static function fitting_thresholds()` — `services/class-ppcert-pdf-renderer.php`

### `touches`

- `public static function generate($url, $opts = [])` — `services/class-ppcert-qr-service.php`
- `public static function generate_png($url, $opts = [])` — `services/class-ppcert-qr-service.php`
