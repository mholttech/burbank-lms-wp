# PressPrimer Quiz 3.1.3 — REST, shortcodes, capabilities, public methods

## REST routes

| Namespace | Route | File |
| --- | --- | --- |
| `ppq/v1` | `/banks` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/banks/(?P<id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/dashboard-page` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/email/test` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/learndash/navigation` | `includes/integrations/class-ppq-learndash.php` |
| `ppq/v1` | `/learndash/quizzes/search` | `includes/integrations/class-ppq-learndash.php` |
| `ppq/v1` | `/learndash/settings` | `includes/integrations/class-ppq-learndash.php` |
| `ppq/v1` | `/learndash/status` | `includes/integrations/class-ppq-learndash.php` |
| `ppq/v1` | `/learnpress/quizzes/search` | `includes/integrations/class-ppq-learnpress.php` |
| `ppq/v1` | `/learnpress/status` | `includes/integrations/class-ppq-learnpress.php` |
| `ppq/v1` | `/lifterlms/lesson-quiz` | `includes/integrations/class-ppq-lifterlms.php` |
| `ppq/v1` | `/lifterlms/quizzes/search` | `includes/integrations/class-ppq-lifterlms.php` |
| `ppq/v1` | `/lifterlms/status` | `includes/integrations/class-ppq-lifterlms.php` |
| `ppq/v1` | `/my-attempts` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/questions` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/questions/(?P<id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/questions/(?P<id>\d+)/upload-image` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quiz-templates` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quiz-templates/(?P<id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quiz-templates/default` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<quiz_id>\d+)/items` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<quiz_id>\d+)/items/(?P<item_id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<quiz_id>\d+)/items/reorder` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<quiz_id>\d+)/rules` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<quiz_id>\d+)/rules/(?P<rule_id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/(?P<quiz_id>\d+)/rules/reorder` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/quizzes/published` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings/ai-provider` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings/api-key` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings/api-key/clear` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings/api-key/validate` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings/api-model` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/settings/api-models` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/activity-chart` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/attempts` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/attempts/(?P<id>\d+)` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/dashboard` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/overview` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/quiz-options` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/statistics/quiz-performance` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/status/schema` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/status/schema/repair` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/taxonomies` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/tools/reset-progress` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/tools/reset-progress/log` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/tools/reset-progress/preview` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/tools/users` | `includes/api/class-ppq-rest-controller.php` |
| `ppq/v1` | `/tutorlms/lesson-quiz` | `includes/integrations/class-ppq-tutorlms.php` |
| `ppq/v1` | `/tutorlms/quizzes/search` | `includes/integrations/class-ppq-tutorlms.php` |
| `ppq/v1` | `/tutorlms/status` | `includes/integrations/class-ppq-tutorlms.php` |
| `ppq/v1` | `/upload-image` | `includes/api/class-ppq-rest-controller.php` |

## Shortcodes

- `[pressprimer_quiz]` — `includes/frontend/class-ppq-shortcodes.php`
- `[pressprimer_quiz_dashboard]` — `includes/frontend/class-ppq-shortcodes.php`
- `[pressprimer_quiz_my_attempts]` — `includes/frontend/class-ppq-shortcodes.php`

## Capabilities

- `pressprimer_quiz_manage_all`
- `pressprimer_quiz_manage_own`
- `pressprimer_quiz_manage_settings`
- `pressprimer_quiz_take_quiz`
- `pressprimer_quiz_view_results_all`
- `pressprimer_quiz_view_results_own`

## Public methods

### `PressPrimer_Quiz_AI_Provider_Anthropic`

- `public function get_id()` — `services/ai/class-ppq-ai-provider-anthropic.php`
- `public function get_label()` — `services/ai/class-ppq-ai-provider-anthropic.php`
- `public function get_default_model()` — `services/ai/class-ppq-ai-provider-anthropic.php`
- `public function get_models()` — `services/ai/class-ppq-ai-provider-anthropic.php`
- `public function fetch_models(string $api_key)` — `services/ai/class-ppq-ai-provider-anthropic.php`
- `public function validate_key(string $api_key)` — `services/ai/class-ppq-ai-provider-anthropic.php`
- `public function send(array $messages, array $options)` — `services/ai/class-ppq-ai-provider-anthropic.php`

### `PressPrimer_Quiz_AI_Provider_OpenAI`

- `public function get_id()` — `services/ai/class-ppq-ai-provider-openai.php`
- `public function get_label()` — `services/ai/class-ppq-ai-provider-openai.php`
- `public function get_default_model()` — `services/ai/class-ppq-ai-provider-openai.php`
- `public function get_models()` — `services/ai/class-ppq-ai-provider-openai.php`
- `public function validate_key(string $api_key)` — `services/ai/class-ppq-ai-provider-openai.php`
- `public function send(array $messages, array $options)` — `services/ai/class-ppq-ai-provider-openai.php`

### `PressPrimer_Quiz_AJAX_Handler`

- `public function init()` — `frontend/class-ppq-ajax-handler.php`
- `public function start_quiz()` — `frontend/class-ppq-ajax-handler.php`
- `public function save_answers()` — `frontend/class-ppq-ajax-handler.php`
- `public function sync_time()` — `frontend/class-ppq-ajax-handler.php`
- `public function submit_quiz()` — `frontend/class-ppq-ajax-handler.php`
- `public function email_results()` — `frontend/class-ppq-ajax-handler.php`
- `public function check_answer()` — `frontend/class-ppq-ajax-handler.php`
- `public function load_more_attempts()` — `frontend/class-ppq-ajax-handler.php`

### `PressPrimer_Quiz_Activator`

- `public static function activate($network_wide = false)` — `class-ppq-activator.php`
- `public static function activate_new_site($new_site)` — `class-ppq-activator.php`

### `PressPrimer_Quiz_Addon_Manager`

- `public static function get_instance()` — `class-ppq-addon-manager.php`
- `public function init()` — `class-ppq-addon-manager.php`
- `public function register($slug, $config)` — `class-ppq-addon-manager.php`
- `public function is_registered($slug)` — `class-ppq-addon-manager.php`
- `public function is_active($slug)` — `class-ppq-addon-manager.php`
- `public function is_compatible($slug)` — `class-ppq-addon-manager.php`
- `public function get_addon($slug)` — `class-ppq-addon-manager.php`
- `public function get_all()` — `class-ppq-addon-manager.php`
- `public function get_by_tier($tier)` — `class-ppq-addon-manager.php`
- `public function has_active_addons()` — `class-ppq-addon-manager.php`
- `public function get_active_count()` — `class-ppq-addon-manager.php`

### `PressPrimer_Quiz_Admin`

- `public function init()` — `admin/class-ppq-admin.php`
- `public function register_menus()` — `admin/class-ppq-admin.php`
- `public function enqueue_assets($hook)` — `admin/class-ppq-admin.php`
- `public function render_dashboard()` — `admin/class-ppq-admin.php`
- `public function render_quizzes()` — `admin/class-ppq-admin.php`
- `public function render_questions()` — `admin/class-ppq-admin.php`
- `public function render_banks()` — `admin/class-ppq-admin.php`
- `public function render_categories()` — `admin/class-ppq-admin.php`
- `public function render_tags()` — `admin/class-ppq-admin.php`
- `public function render_reports()` — `admin/class-ppq-admin.php`
- `public function render_settings()` — `admin/class-ppq-admin.php`
- `public function ajax_search_questions()` — `admin/class-ppq-admin.php`
- `public function ajax_get_recent_questions()` — `admin/class-ppq-admin.php`
- `public function ajax_remove_question_from_bank()` — `admin/class-ppq-admin.php`

### `PressPrimer_Quiz_Admin_AI_Generation`

- `public function init()` — `admin/class-ppq-admin-ai-generation.php`
- `public function enqueue_scripts($hook)` — `admin/class-ppq-admin-ai-generation.php`
- `public function render_panel($bank_id)` — `admin/class-ppq-admin-ai-generation.php`
- `public function ajax_generate_questions()` — `admin/class-ppq-admin-ai-generation.php`
- `public function ajax_upload_file()` — `admin/class-ppq-admin-ai-generation.php`
- `public function ajax_save_questions()` — `admin/class-ppq-admin-ai-generation.php`
- `public function ajax_check_status()` — `admin/class-ppq-admin-ai-generation.php`

### `PressPrimer_Quiz_Admin_Banks`

- `public function init()` — `admin/class-ppq-admin-banks.php`
- `public function maybe_add_screen_options()` — `admin/class-ppq-admin-banks.php`
- `public function screen_options()` — `admin/class-ppq-admin-banks.php`
- `public function set_screen_option($status, $option, $value)` — `admin/class-ppq-admin-banks.php`
- `public function render()` — `admin/class-ppq-admin-banks.php`
- `public function handle_save()` — `admin/class-ppq-admin-banks.php`
- `public function handle_delete()` — `admin/class-ppq-admin-banks.php`
- `public function handle_add_question()` — `admin/class-ppq-admin-banks.php`
- `public function handle_remove_question()` — `admin/class-ppq-admin-banks.php`
- `public function admin_notices()` — `admin/class-ppq-admin-banks.php`

### `PressPrimer_Quiz_Admin_Categories`

- `public function init()` — `admin/class-ppq-admin-categories.php`
- `public function render()` — `admin/class-ppq-admin-categories.php`
- `public function render_tags()` — `admin/class-ppq-admin-categories.php`
- `public function handle_save()` — `admin/class-ppq-admin-categories.php`
- `public function handle_delete()` — `admin/class-ppq-admin-categories.php`
- `public function admin_notices()` — `admin/class-ppq-admin-categories.php`

### `PressPrimer_Quiz_Admin_Questions`

- `public function init()` — `admin/class-ppq-admin-questions.php`
- `public function maybe_add_screen_options()` — `admin/class-ppq-admin-questions.php`
- `public function screen_options()` — `admin/class-ppq-admin-questions.php`
- `public function set_screen_option($status, $option, $value)` — `admin/class-ppq-admin-questions.php`
- `public function admin_notices()` — `admin/class-ppq-admin-questions.php`
- `public function render()` — `admin/class-ppq-admin-questions.php`
- `public function handle_actions()` — `admin/class-ppq-admin-questions.php`
- `public function __construct()` — `admin/class-ppq-admin-questions.php`
- `public function get_columns()` — `admin/class-ppq-admin-questions.php`
- `public function column_default($item, $column_name)` — `admin/class-ppq-admin-questions.php`
- `public function get_hidden_columns()` — `admin/class-ppq-admin-questions.php`
- `public function get_sortable_columns()` — `admin/class-ppq-admin-questions.php`
- `public function get_bulk_actions()` — `admin/class-ppq-admin-questions.php`
- `public function prepare_items()` — `admin/class-ppq-admin-questions.php`
- `public function column_cb($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_id($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_question($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_type($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_difficulty($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_categories($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_banks($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_author($item)` — `admin/class-ppq-admin-questions.php`
- `public function column_date($item)` — `admin/class-ppq-admin-questions.php`
- `public function no_items()` — `admin/class-ppq-admin-questions.php`

### `PressPrimer_Quiz_Admin_Quizzes`

- `public function init()` — `admin/class-ppq-admin-quizzes.php`
- `public function maybe_add_screen_options()` — `admin/class-ppq-admin-quizzes.php`
- `public function screen_options()` — `admin/class-ppq-admin-quizzes.php`
- `public function set_screen_option($status, $option, $value)` — `admin/class-ppq-admin-quizzes.php`
- `public function handle_actions()` — `admin/class-ppq-admin-quizzes.php`
- `public function admin_notices()` — `admin/class-ppq-admin-quizzes.php`
- `public function render()` — `admin/class-ppq-admin-quizzes.php`
- `public function ajax_get_available_questions()` — `admin/class-ppq-admin-quizzes.php`
- `public function ajax_add_quiz_questions()` — `admin/class-ppq-admin-quizzes.php`
- `public function ajax_remove_quiz_question()` — `admin/class-ppq-admin-quizzes.php`
- `public function __construct()` — `admin/class-ppq-admin-quizzes.php`
- `public function get_columns()` — `admin/class-ppq-admin-quizzes.php`
- `public function get_hidden_columns()` — `admin/class-ppq-admin-quizzes.php`
- `public function get_sortable_columns()` — `admin/class-ppq-admin-quizzes.php`
- `public function get_bulk_actions()` — `admin/class-ppq-admin-quizzes.php`
- `public function prepare_items()` — `admin/class-ppq-admin-quizzes.php`
- `public function column_cb($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_id($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_title($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_questions($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_mode($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_status($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_author($item)` — `admin/class-ppq-admin-quizzes.php`
- `public function column_date($item)` — `admin/class-ppq-admin-quizzes.php`

### `PressPrimer_Quiz_Admin_Settings`

- `public function init()` — `admin/class-ppq-admin-settings.php`
- `public function register_settings()` — `admin/class-ppq-admin-settings.php`
- `public function render_general_section()` — `admin/class-ppq-admin-settings.php`
- `public function render_quiz_defaults_section()` — `admin/class-ppq-admin-settings.php`
- `public function render_email_section()` — `admin/class-ppq-admin-settings.php`
- `public function render_api_keys_section()` — `admin/class-ppq-admin-settings.php`
- `public function render_social_sharing_section()` — `admin/class-ppq-admin-settings.php`
- `public function render_advanced_section()` — `admin/class-ppq-admin-settings.php`
- `public function render_passing_score_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_quiz_mode_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_email_from_name_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_email_from_address_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_email_auto_send_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_email_subject_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_email_body_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_site_openai_api_key_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_openai_api_key_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_social_twitter_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_social_facebook_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_social_linkedin_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_social_include_score_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_social_message_field()` — `admin/class-ppq-admin-settings.php`
- `public function render_remove_data_field()` — `admin/class-ppq-admin-settings.php`
- `public function sanitize_settings($input)` — `admin/class-ppq-admin-settings.php`
- `public function render_page()` — `admin/class-ppq-admin-settings.php`
- `public static function get($key, $default = null)` — `admin/class-ppq-admin-settings.php`
- `public function ajax_save_user_api_key()` — `admin/class-ppq-admin-settings.php`
- `public function ajax_validate_api_key()` — `admin/class-ppq-admin-settings.php`
- `public function ajax_clear_user_api_key()` — `admin/class-ppq-admin-settings.php`
- `public function ajax_get_api_models()` — `admin/class-ppq-admin-settings.php`
- `public function ajax_repair_database_tables()` — `admin/class-ppq-admin-settings.php`
- `public function ajax_save_site_api_key()` — `admin/class-ppq-admin-settings.php`
- `public function ajax_clear_site_api_key()` — `admin/class-ppq-admin-settings.php`

### `PressPrimer_Quiz_Attempt`

- `public static function create_for_user(int $quiz_id, int $user_id, string $source_url = '')` — `models/class-ppq-attempt.php`
- `public static function create_for_guest(int $quiz_id, string $email, string $source_url = '', array $consent_args = array()` — `models/class-ppq-attempt.php`
- `public function get_items(bool $force_refresh = false)` — `models/class-ppq-attempt.php`
- `public function save_answer(int $item_or_revision_id, array $selected_answers, $confidence = null)` — `models/class-ppq-attempt.php`
- `public function update_confidence(int $item_id, $confidence)` — `models/class-ppq-attempt.php`
- `public function submit()` — `models/class-ppq-attempt.php`
- `public function is_timed_out()` — `models/class-ppq-attempt.php`
- `public function can_resume()` — `models/class-ppq-attempt.php`
- `public function get_quiz()` — `models/class-ppq-attempt.php`
- `public function has_marketing_consent()` — `models/class-ppq-attempt.php`
- `public function get_consent_timestamp()` — `models/class-ppq-attempt.php`
- `public function get_all_meta()` — `models/class-ppq-attempt.php`
- `public function get_meta($key, $default = null)` — `models/class-ppq-attempt.php`
- `public function set_meta($key, $value)` — `models/class-ppq-attempt.php`
- `public function is_late()` — `models/class-ppq-attempt.php`
- `public function mark_as_late($is_late = true)` — `models/class-ppq-attempt.php`
- `public static function get_user_attempts(int $quiz_id, int $user_id)` — `models/class-ppq-attempt.php`
- `public static function get_user_in_progress(int $quiz_id, int $user_id)` — `models/class-ppq-attempt.php`
- `public static function get_last_user_attempt(int $quiz_id, int $user_id)` — `models/class-ppq-attempt.php`
- `public static function get_user_best_attempt(int $quiz_id, int $user_id)` — `models/class-ppq-attempt.php`
- `public static function get_guest_in_progress(int $quiz_id, string $email)` — `models/class-ppq-attempt.php`
- `public static function get_by_token(string $token)` — `models/class-ppq-attempt.php`
- `public function is_token_expired()` — `models/class-ppq-attempt.php`
- `public function get_results_url($base_url = '')` — `models/class-ppq-attempt.php`
- `public function regenerate_token()` — `models/class-ppq-attempt.php`
- `public function delete()` — `models/class-ppq-attempt.php`

### `PressPrimer_Quiz_Attempt_Item`

- `public function get_question_revision()` — `models/class-ppq-attempt-item.php`
- `public function get_question()` — `models/class-ppq-attempt-item.php`
- `public function get_selected_answers()` — `models/class-ppq-attempt-item.php`
- `public function get_answer_order()` — `models/class-ppq-attempt-item.php`
- `public function set_answer_order(array $order)` — `models/class-ppq-attempt-item.php`
- `public function calculate_time_spent()` — `models/class-ppq-attempt-item.php`
- `public function record_first_view()` — `models/class-ppq-attempt-item.php`
- `public function check_answer()` — `models/class-ppq-attempt-item.php`
- `public static function get_by_attempt_and_question(int $attempt_id, int $question_revision_id)` — `models/class-ppq-attempt-item.php`
- `public static function get_by_attempt(int $attempt_id)` — `models/class-ppq-attempt-item.php`
- `public static function get_or_create(int $attempt_id, int $question_revision_id, int $order_index = 0)` — `models/class-ppq-attempt-item.php`

### `PressPrimer_Quiz_Automator_Loader`

- `public function init()` — `integrations/uncanny-automator/class-ppq-automator-loader.php`
- `public function add_integration()` — `integrations/uncanny-automator/class-ppq-automator-loader.php`

### `PressPrimer_Quiz_Bank`

- `public static function create(array $data)` — `models/class-ppq-bank.php`
- `public function save()` — `models/class-ppq-bank.php`
- `public function add_question($question_id)` — `models/class-ppq-bank.php`
- `public function remove_question($question_id)` — `models/class-ppq-bank.php`
- `public function get_questions(array $args = [])` — `models/class-ppq-bank.php`
- `public function get_question_count()` — `models/class-ppq-bank.php`
- `public function count_questions(array $args = [])` — `models/class-ppq-bank.php`
- `public function update_question_count()` — `models/class-ppq-bank.php`
- `public function has_question($question_id)` — `models/class-ppq-bank.php`
- `public static function get_for_user($user_id, array $args = [])` — `models/class-ppq-bank.php`
- `public static function get_all(array $args = [])` — `models/class-ppq-bank.php`
- `public function get_quiz_usage()` — `models/class-ppq-bank.php`
- `public function is_in_use()` — `models/class-ppq-bank.php`
- `public function delete()` — `models/class-ppq-bank.php`
- `public function can_access($user_id = null)` — `models/class-ppq-bank.php`

### `PressPrimer_Quiz_Banks_List_Table`

- `public function __construct()` — `admin/class-ppq-banks-list-table.php`
- `public function get_columns()` — `admin/class-ppq-banks-list-table.php`
- `public function prepare_items()` — `admin/class-ppq-banks-list-table.php`
- `public function no_items()` — `admin/class-ppq-banks-list-table.php`
- `public function search_box($text, $input_id)` — `admin/class-ppq-banks-list-table.php`

### `PressPrimer_Quiz_Blocks`

- `public function init()` — `blocks/class-ppq-blocks.php`
- `public function register_block_category($categories, $context)` — `blocks/class-ppq-blocks.php`
- `public function register_blocks()` — `blocks/class-ppq-blocks.php`
- `public function render_quiz_block($attributes)` — `blocks/class-ppq-blocks.php`
- `public function render_my_attempts_block($attributes)` — `blocks/class-ppq-blocks.php`
- `public function render_dashboard_block($attributes)` — `blocks/class-ppq-blocks.php`

### `PressPrimer_Quiz_Capabilities`

- `public static function setup_capabilities()` — `utilities/class-ppq-capabilities.php`
- `public static function create_teacher_role()` — `utilities/class-ppq-capabilities.php`
- `public static function remove_capabilities()` — `utilities/class-ppq-capabilities.php`
- `public static function get_all_capabilities()` — `utilities/class-ppq-capabilities.php`
- `public static function user_can_manage_quiz($user_id, $owner_id)` — `utilities/class-ppq-capabilities.php`
- `public static function user_can_view_results($user_id, $student_id)` — `utilities/class-ppq-capabilities.php`

### `PressPrimer_Quiz_Categories_List_Table`

- `public function __construct($taxonomy = 'category')` — `admin/class-ppq-categories-list-table.php`
- `public function get_columns()` — `admin/class-ppq-categories-list-table.php`
- `public function prepare_items()` — `admin/class-ppq-categories-list-table.php`
- `public function no_items()` — `admin/class-ppq-categories-list-table.php`

### `PressPrimer_Quiz_Category`

- `public static function create(array $data)` — `models/class-ppq-category.php`
- `public static function get_for_question($question_id, $taxonomy = 'category')` — `models/class-ppq-category.php`
- `public static function get_all($taxonomy = 'category', $args = [])` — `models/class-ppq-category.php`
- `public static function get_hierarchy($parent_id = null)` — `models/class-ppq-category.php`
- `public static function update_counts($category_id = null)` — `models/class-ppq-category.php`
- `public function assign_to_question($question_id)` — `models/class-ppq-category.php`
- `public function remove_from_question($question_id)` — `models/class-ppq-category.php`
- `public static function get_by_slug($slug, $taxonomy = 'category')` — `models/class-ppq-category.php`
- `public function delete()` — `models/class-ppq-category.php`

### `PressPrimer_Quiz_Deactivator`

- `public static function deactivate()` — `class-ppq-deactivator.php`

### `PressPrimer_Quiz_Default_Template`

- `public static function get_raw()` — `services/class-ppq-default-template.php`
- `public static function get_validated()` — `services/class-ppq-default-template.php`
- `public static function set_value($value)` — `services/class-ppq-default-template.php`
- `public static function resolve()` — `services/class-ppq-default-template.php`
- `public static function resolve_value($value)` — `services/class-ppq-default-template.php`

### `PressPrimer_Quiz_Email_Service`

- `public static function send_results($attempt_id, $to_email)` — `services/class-ppq-email-service.php`
- `public static function send_test_email($to_email, $type = 'results')` — `services/class-ppq-email-service.php`
- `public static function maybe_send_on_completion($attempt_id)` — `services/class-ppq-email-service.php`

### `PressPrimer_Quiz_Exposure_Service`

- `public static function get_seen_map($quiz_id, $user_id)` — `services/class-ppq-exposure-service.php`
- `public static function reset_cache()` — `services/class-ppq-exposure-service.php`

### `PressPrimer_Quiz_File_Processor`

- `public function __construct()` — `services/class-ppq-file-processor.php`
- `public function process_upload($file)` — `services/class-ppq-file-processor.php`
- `public function extract_pdf_text($file_path)` — `services/class-ppq-file-processor.php`
- `public function extract_docx_text($file_path)` — `services/class-ppq-file-processor.php`
- `public function cleanup_temp_files()` — `services/class-ppq-file-processor.php`
- `public static function get_supported_types()` — `services/class-ppq-file-processor.php`
- `public static function get_max_file_size()` — `services/class-ppq-file-processor.php`
- `public static function get_extraction_capabilities()` — `services/class-ppq-file-processor.php`
- `public function process_file($file_path)` — `services/class-ppq-file-processor.php`

### `PressPrimer_Quiz_Guest_Captured`

- `public function options()` — `integrations/uncanny-automator/triggers/class-ppq-guest-captured.php`
- `public function validate($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-guest-captured.php`
- `public function define_tokens($trigger, $tokens)` — `integrations/uncanny-automator/triggers/class-ppq-guest-captured.php`
- `public function hydrate_tokens($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-guest-captured.php`

### `PressPrimer_Quiz_Helpers`

- `public static function get_client_ip()` — `utilities/class-ppq-helpers.php`
- `public static function generate_uuid()` — `utilities/class-ppq-helpers.php`
- `public static function encrypt($data)` — `utilities/class-ppq-helpers.php`
- `public static function decrypt($data)` — `utilities/class-ppq-helpers.php`
- `public static function format_duration($seconds, $short_form = false)` — `utilities/class-ppq-helpers.php`
- `public static function sanitize_array($array, $type = 'text')` — `utilities/class-ppq-helpers.php`
- `public static function is_lms_active($lms)` — `utilities/class-ppq-helpers.php`
- `public static function format_percentage($value, $decimals = 1)` — `utilities/class-ppq-helpers.php`
- `public static function truncate($text, $length = 100, $suffix = '...')` — `utilities/class-ppq-helpers.php`
- `public static function time_ago($time)` — `utilities/class-ppq-helpers.php`
- `public static function format_local_datetime($mysql_datetime, $format = '')` — `utilities/class-ppq-helpers.php`
- `public static function local_datetime_to_timestamp($mysql_datetime)` — `utilities/class-ppq-helpers.php`
- `public static function array_get($array, $key, $default = null)` — `utilities/class-ppq-helpers.php`
- `public static function is_ajax()` — `utilities/class-ppq-helpers.php`
- `public static function is_rest()` — `utilities/class-ppq-helpers.php`
- `public static function is_non_body_content_pass()` — `utilities/class-ppq-helpers.php`
- `public static function random_string($length = 32, $alphanumeric = true)` — `utilities/class-ppq-helpers.php`
- `public static function current_user_can_edit_post($post_id)` — `utilities/class-ppq-helpers.php`
- `public static function content_has_math($html)` — `utilities/class-ppq-helpers.php`
- `public static function trim_trailing_empty_html($html)` — `utilities/class-ppq-helpers.php`

### `PressPrimer_Quiz_LearnDash`

- `public function init()` — `integrations/class-ppq-learndash.php`
- `public function register_meta_boxes()` — `integrations/class-ppq-learndash.php`
- `public function render_meta_box($post)` — `integrations/class-ppq-learndash.php`
- `public function save_meta_box($post_id, $post)` — `integrations/class-ppq-learndash.php`
- `public function register_meta_fields()` — `integrations/class-ppq-learndash.php`
- `public function enqueue_block_editor_assets()` — `integrations/class-ppq-learndash.php`
- `public function maybe_display_quiz($content)` — `integrations/class-ppq-learndash.php`
- `public function maybe_add_course_quiz_tab($tabs, $context, $course_id, $user_id)` — `integrations/class-ppq-learndash.php`
- `public function maybe_hide_mark_complete($button, $args)` — `integrations/class-ppq-learndash.php`
- `public function handle_quiz_passed($attempt, $quiz)` — `integrations/class-ppq-learndash.php`
- `public function maybe_prevent_lesson_completion($can_complete, $post_id, $course_id, $user_id)` — `integrations/class-ppq-learndash.php`
- `public function maybe_prevent_course_completion($mark_complete, $post, $current_user)` — `integrations/class-ppq-learndash.php`
- `public function maybe_undo_course_completion($data)` — `integrations/class-ppq-learndash.php`
- `public function check_course_restriction($allowed, $quiz, $attempt)` — `integrations/class-ppq-learndash.php`
- `public function ajax_search_quizzes()` — `integrations/class-ppq-learndash.php`
- `public function check_instructor_capability($has_capability, $user_id)` — `integrations/class-ppq-learndash.php`
- `public function register_rest_routes()` — `integrations/class-ppq-learndash.php`
- `public function rest_search_quizzes($request)` — `integrations/class-ppq-learndash.php`
- `public function rest_get_status($request)` — `integrations/class-ppq-learndash.php`
- `public function rest_save_settings($request)` — `integrations/class-ppq-learndash.php`
- `public function rest_get_navigation($request)` — `integrations/class-ppq-learndash.php`
- `public function get_next_step_url($post_id)` — `integrations/class-ppq-learndash.php`
- `public function add_navigation_data($data, $attempt)` — `integrations/class-ppq-learndash.php`

### `PressPrimer_Quiz_LearnPress`

- `public function init()` — `integrations/class-ppq-learnpress.php`
- `public function is_learnpress_active()` — `integrations/class-ppq-learnpress.php`
- `public function render_lesson_settings()` — `integrations/class-ppq-learnpress.php`
- `public function save_lesson_meta($post_id, $post)` — `integrations/class-ppq-learnpress.php`
- `public function register_meta_fields()` — `integrations/class-ppq-learnpress.php`
- `public function ajax_search_quizzes()` — `integrations/class-ppq-learnpress.php`
- `public function render_quiz_in_lesson()` — `integrations/class-ppq-learnpress.php`
- `public function maybe_hide_complete_button()` — `integrations/class-ppq-learnpress.php`
- `public function handle_quiz_completion($attempt, $data)` — `integrations/class-ppq-learnpress.php`
- `public function handle_quiz_passed($attempt, $quiz)` — `integrations/class-ppq-learnpress.php`
- `public function enqueue_styles()` — `integrations/class-ppq-learnpress.php`
- `public function check_instructor_capability($has_capability, $user_id)` — `integrations/class-ppq-learnpress.php`
- `public function register_rest_routes()` — `integrations/class-ppq-learnpress.php`
- `public function rest_search_quizzes($request)` — `integrations/class-ppq-learnpress.php`
- `public function rest_get_status($request)` — `integrations/class-ppq-learnpress.php`

### `PressPrimer_Quiz_LifterLMS`

- `public function init()` — `integrations/class-ppq-lifterlms.php`
- `public function register_meta_boxes()` — `integrations/class-ppq-lifterlms.php`
- `public function render_meta_box($post)` — `integrations/class-ppq-lifterlms.php`
- `public function save_meta_box($post_id, $post)` — `integrations/class-ppq-lifterlms.php`
- `public function enqueue_course_builder_assets($hook)` — `integrations/class-ppq-lifterlms.php`
- `public function display_quiz_in_lesson($lesson, $student)` — `integrations/class-ppq-lifterlms.php`
- `public function display_quiz_before_navigation_block()` — `integrations/class-ppq-lifterlms.php`
- `public function append_quiz_to_content($content)` — `integrations/class-ppq-lifterlms.php`
- `public function check_ppq_quiz_complete($is_complete, $user_id, $object_id, $type)` — `integrations/class-ppq-lifterlms.php`
- `public function maybe_hide_complete_button($show, $lesson)` — `integrations/class-ppq-lifterlms.php`
- `public function handle_quiz_passed($attempt, $quiz)` — `integrations/class-ppq-lifterlms.php`
- `public function check_instructor_capability($has_capability, $user_id)` — `integrations/class-ppq-lifterlms.php`
- `public function register_rest_routes()` — `integrations/class-ppq-lifterlms.php`
- `public function rest_search_quizzes($request)` — `integrations/class-ppq-lifterlms.php`
- `public function rest_save_lesson_quiz($request)` — `integrations/class-ppq-lifterlms.php`
- `public function ajax_search_quizzes()` — `integrations/class-ppq-lifterlms.php`
- `public function rest_get_status($request)` — `integrations/class-ppq-lifterlms.php`

### `PressPrimer_Quiz_Migrator`

- `public static function maybe_migrate()` — `database/class-ppq-migrator.php`
- `public static function migrate_to_3_1_0_2()` — `database/class-ppq-migrator.php`
- `public static function migrate_to_3_1_2()` — `database/class-ppq-migrator.php`
- `public static function get_current_version()` — `database/class-ppq-migrator.php`
- `public static function get_migration_history()` — `database/class-ppq-migrator.php`
- `public static function force_migration()` — `database/class-ppq-migrator.php`
- `public static function get_required_tables()` — `database/class-ppq-migrator.php`
- `public static function get_table_status()` — `database/class-ppq-migrator.php`
- `public static function has_missing_tables()` — `database/class-ppq-migrator.php`
- `public static function repair_tables()` — `database/class-ppq-migrator.php`

### `PressPrimer_Quiz_Onboarding`

- `public static function get_instance()` — `admin/class-ppq-onboarding.php`
- `public function should_show_onboarding($user_id = null)` — `admin/class-ppq-onboarding.php`
- `public function complete_onboarding($user_id = null)` — `admin/class-ppq-onboarding.php`
- `public function skip_onboarding($user_id = null, $permanent = false)` — `admin/class-ppq-onboarding.php`
- `public function reset_onboarding($user_id = null)` — `admin/class-ppq-onboarding.php`
- `public function get_onboarding_state($user_id = null)` — `admin/class-ppq-onboarding.php`
- `public function update_step($step, $user_id = null)` — `admin/class-ppq-onboarding.php`
- `public function start_onboarding($user_id = null)` — `admin/class-ppq-onboarding.php`
- `public function ajax_onboarding_progress()` — `admin/class-ppq-onboarding.php`
- `public function ajax_get_onboarding_state()` — `admin/class-ppq-onboarding.php`
- `public function get_js_data()` — `admin/class-ppq-onboarding.php`

### `PressPrimer_Quiz_Plugin`

- `public static function get_instance()` — `class-ppq-plugin.php`
- `public function run()` — `class-ppq-plugin.php`

### `PressPrimer_Quiz_Privacy`

- `public static function init()` — `utilities/class-ppq-privacy.php`
- `public static function register_exporter($exporters)` — `utilities/class-ppq-privacy.php`
- `public static function register_eraser($erasers)` — `utilities/class-ppq-privacy.php`
- `public static function export($email, $page = 1)` — `utilities/class-ppq-privacy.php`
- `public static function erase($email, $page = 1)` — `utilities/class-ppq-privacy.php`

### `PressPrimer_Quiz_Progress_Reset_Service`

- `public function sanitize_scope($user_id, $quiz_id)` — `services/class-ppq-progress-reset-service.php`
- `public function verify_scope_targets($scope)` — `services/class-ppq-progress-reset-service.php`
- `public function resolve_attempt_ids($scope, $limit = 0, $after_id = 0)` — `services/class-ppq-progress-reset-service.php`
- `public function get_preview($scope)` — `services/class-ppq-progress-reset-service.php`
- `public function get_confirm_token($scope)` — `services/class-ppq-progress-reset-service.php`
- `public function verify_token($scope, $submitted)` — `services/class-ppq-progress-reset-service.php`
- `public function count_attempts($scope, $after_id = 0)` — `services/class-ppq-progress-reset-service.php`
- `public function delete_batch($scope, $cursor = 0)` — `services/class-ppq-progress-reset-service.php`
- `public function guard_lock($scope)` — `services/class-ppq-progress-reset-service.php`
- `public function record_batch($scope, $attempts, $items)` — `services/class-ppq-progress-reset-service.php`
- `public function complete_operation($scope, $totals)` — `services/class-ppq-progress-reset-service.php`
- `public function get_log()` — `services/class-ppq-progress-reset-service.php`
- `public function release_lock()` — `services/class-ppq-progress-reset-service.php`

### `PressPrimer_Quiz_Question`

- `public static function get_by_uuid($uuid)` — `models/class-ppq-question.php`
- `public static function create(array $data)` — `models/class-ppq-question.php`
- `public function save()` — `models/class-ppq-question.php`
- `public static function validate_content($stem, $answers, $type = 'mc')` — `models/class-ppq-question.php`
- `public function delete()` — `models/class-ppq-question.php`
- `public function force_delete()` — `models/class-ppq-question.php`
- `public static function register_image_ownership($question_id, $html)` — `models/class-ppq-question.php`
- `public function restore()` — `models/class-ppq-question.php`
- `public function get_current_revision($force = false)` — `models/class-ppq-question.php`
- `public function get_categories($force = false)` — `models/class-ppq-question.php`
- `public function get_tags($force = false)` — `models/class-ppq-question.php`
- `public function set_categories(array $category_ids)` — `models/class-ppq-question.php`
- `public function set_tags(array $tag_ids)` — `models/class-ppq-question.php`
- `public function is_deleted()` — `models/class-ppq-question.php`
- `public function get_usage_count()` — `models/class-ppq-question.php`
- `public static function find_questions(array $args = [])` — `models/class-ppq-question.php`

### `PressPrimer_Quiz_Question_Revision`

- `public static function create_for_question($question_id, array $data)` — `models/class-ppq-question-revision.php`
- `public static function sanitize_content(array $data)` — `models/class-ppq-question-revision.php`
- `public static function generate_hash($stem, $answers)` — `models/class-ppq-question-revision.php`
- `public function get_answers($force = false)` — `models/class-ppq-question-revision.php`
- `public function get_settings()` — `models/class-ppq-question-revision.php`
- `public function get_correct_answers()` — `models/class-ppq-question-revision.php`
- `public function get_answer_by_id($answer_id)` — `models/class-ppq-question-revision.php`
- `public function matches_content(PressPrimer_Quiz_Question_Revision $other)` — `models/class-ppq-question-revision.php`
- `public static function find_by_hash($hash)` — `models/class-ppq-question-revision.php`
- `public static function get_for_question($question_id, array $args = [])` — `models/class-ppq-question-revision.php`
- `public function save()` — `models/class-ppq-question-revision.php`
- `public function delete()` — `models/class-ppq-question-revision.php`

### `PressPrimer_Quiz_Quiz`

- `public static function get_settings_sanitizers()` — `models/class-ppq-quiz.php`
- `public static function get_settings_keys()` — `models/class-ppq-quiz.php`
- `public static function sanitize_settings(array $settings)` — `models/class-ppq-quiz.php`
- `public static function get_by_uuid(string $uuid)` — `models/class-ppq-quiz.php`
- `public static function create(array $data)` — `models/class-ppq-quiz.php`
- `public function save()` — `models/class-ppq-quiz.php`
- `public function delete()` — `models/class-ppq-quiz.php`
- `public function get_items(bool $force_refresh = false)` — `models/class-ppq-quiz.php`
- `public function get_rules(bool $force_refresh = false)` — `models/class-ppq-quiz.php`
- `public function get_pool_size()` — `models/class-ppq-quiz.php`
- `public function get_questions_for_attempt($user_id = 0)` — `models/class-ppq-quiz.php`
- `public function duplicate()` — `models/class-ppq-quiz.php`
- `public function get_feedback_for_score(float $score_percent)` — `models/class-ppq-quiz.php`
- `public function get_effective_access_mode()` — `models/class-ppq-quiz.php`
- `public function get_login_message()` — `models/class-ppq-quiz.php`
- `public function can_user_access()` — `models/class-ppq-quiz.php`
- `public function is_guest_email_required()` — `models/class-ppq-quiz.php`
- `public function get_resolved_ma_scoring_mode()` — `models/class-ppq-quiz.php`
- `public function get_display_settings()` — `models/class-ppq-quiz.php`
- `public function set_display_settings(array $settings)` — `models/class-ppq-quiz.php`
- `public function resolve_display_option($key, array $instance_overrides = array()` — `models/class-ppq-quiz.php`
- `public function resolve_all_display_options(array $instance_overrides = array()` — `models/class-ppq-quiz.php`
- `public static function extract_display_overrides(array $raw_attributes)` — `models/class-ppq-quiz.php`
- `public function get_effective_display_density()` — `models/class-ppq-quiz.php`

### `PressPrimer_Quiz_Quiz_Completed`

- `public function options()` — `integrations/uncanny-automator/triggers/class-ppq-quiz-completed.php`
- `public function validate($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-completed.php`
- `public function define_tokens($trigger, $tokens)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-completed.php`
- `public function hydrate_tokens($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-completed.php`

### `PressPrimer_Quiz_Quiz_Failed`

- `public function options()` — `integrations/uncanny-automator/triggers/class-ppq-quiz-failed.php`
- `public function validate($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-failed.php`
- `public function define_tokens($trigger, $tokens)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-failed.php`
- `public function hydrate_tokens($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-failed.php`

### `PressPrimer_Quiz_Quiz_Item`

- `public static function create(array $data)` — `models/class-ppq-quiz-item.php`
- `public static function exists_for_quiz(int $quiz_id, int $question_id)` — `models/class-ppq-quiz-item.php`
- `public static function get_next_order_index(int $quiz_id)` — `models/class-ppq-quiz-item.php`
- `public static function get_for_quiz(int $quiz_id)` — `models/class-ppq-quiz-item.php`
- `public static function reorder(array $order_map)` — `models/class-ppq-quiz-item.php`
- `public function move_up()` — `models/class-ppq-quiz-item.php`
- `public function move_down()` — `models/class-ppq-quiz-item.php`
- `public function delete()` — `models/class-ppq-quiz-item.php`
- `public function get_question()` — `models/class-ppq-quiz-item.php`

### `PressPrimer_Quiz_Quiz_Passed`

- `public function options()` — `integrations/uncanny-automator/triggers/class-ppq-quiz-passed.php`
- `public function validate($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-passed.php`
- `public function define_tokens($trigger, $tokens)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-passed.php`
- `public function hydrate_tokens($trigger, $hook_args)` — `integrations/uncanny-automator/triggers/class-ppq-quiz-passed.php`

### `PressPrimer_Quiz_Quiz_Renderer`

- `public function render_landing($quiz, $is_retake = false, $instance_overrides = [])` — `frontend/class-ppq-quiz-renderer.php`
- `public function render_quiz($attempt)` — `frontend/class-ppq-quiz-renderer.php`
- `public function render_attempt_row($attempt)` — `frontend/class-ppq-quiz-renderer.php`

### `PressPrimer_Quiz_Quiz_Rule`

- `public static function create(array $data)` — `models/class-ppq-quiz-rule.php`
- `public static function get_next_rule_order(int $quiz_id)` — `models/class-ppq-quiz-rule.php`
- `public static function get_for_quiz(int $quiz_id)` — `models/class-ppq-quiz-rule.php`
- `public static function reorder(array $order_map)` — `models/class-ppq-quiz-rule.php`
- `public function delete()` — `models/class-ppq-quiz-rule.php`
- `public function get_matching_questions()` — `models/class-ppq-quiz-rule.php`
- `public function get_category_ids()` — `models/class-ppq-quiz-rule.php`
- `public function get_tag_ids()` — `models/class-ppq-quiz-rule.php`
- `public function get_difficulties()` — `models/class-ppq-quiz-rule.php`
- `public function get_matching_count()` — `models/class-ppq-quiz-rule.php`

### `PressPrimer_Quiz_Quiz_Template`

- `public static function create(array $data)` — `models/class-ppq-quiz-template.php`
- `public function save()` — `models/class-ppq-quiz-template.php`
- `public function get_settings()` — `models/class-ppq-quiz-template.php`
- `public function set_settings(array $settings)` — `models/class-ppq-quiz-template.php`

### `PressPrimer_Quiz_REST_Controller`

- `public function init()` — `api/class-ppq-rest-controller.php`
- `public function register_routes()` — `api/class-ppq-rest-controller.php`
- `public function check_own_attempts_permission()` — `api/class-ppq-rest-controller.php`
- `public function check_permission()` — `api/class-ppq-rest-controller.php`
- `public function check_settings_permission()` — `api/class-ppq-rest-controller.php`
- `public function check_reset_progress_permission()` — `api/class-ppq-rest-controller.php`
- `public function check_editor_permission()` — `api/class-ppq-rest-controller.php`
- `public function check_reports_permission()` — `api/class-ppq-rest-controller.php`
- `public function preview_reset_progress($request)` — `api/class-ppq-rest-controller.php`
- `public function reset_progress($request)` — `api/class-ppq-rest-controller.php`
- `public function get_reset_log()` — `api/class-ppq-rest-controller.php`
- `public function search_reset_users($request)` — `api/class-ppq-rest-controller.php`
- `public function get_questions($request)` — `api/class-ppq-rest-controller.php`
- `public function get_question($request)` — `api/class-ppq-rest-controller.php`
- `public function create_question($request)` — `api/class-ppq-rest-controller.php`
- `public function update_question($request)` — `api/class-ppq-rest-controller.php`
- `public function upload_question_image($request)` — `api/class-ppq-rest-controller.php`
- `public function upload_image($request)` — `api/class-ppq-rest-controller.php`
- `public function get_taxonomies($request)` — `api/class-ppq-rest-controller.php`
- `public function create_taxonomy($request)` — `api/class-ppq-rest-controller.php`
- `public function get_banks($request)` — `api/class-ppq-rest-controller.php`
- `public function get_bank($request)` — `api/class-ppq-rest-controller.php`
- `public function create_bank($request)` — `api/class-ppq-rest-controller.php`
- `public function update_bank($request)` — `api/class-ppq-rest-controller.php`
- `public function delete_bank($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quizzes($request)` — `api/class-ppq-rest-controller.php`
- `public function get_published_quizzes($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quiz($request)` — `api/class-ppq-rest-controller.php`
- `public function create_quiz($request)` — `api/class-ppq-rest-controller.php`
- `public function update_quiz($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quiz_items($request)` — `api/class-ppq-rest-controller.php`
- `public function add_quiz_items($request)` — `api/class-ppq-rest-controller.php`
- `public function update_quiz_item($request)` — `api/class-ppq-rest-controller.php`
- `public function delete_quiz_item($request)` — `api/class-ppq-rest-controller.php`
- `public function reorder_quiz_items($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quiz_rules($request)` — `api/class-ppq-rest-controller.php`
- `public function create_quiz_rule($request)` — `api/class-ppq-rest-controller.php`
- `public function update_quiz_rule($request)` — `api/class-ppq-rest-controller.php`
- `public function delete_quiz_rule($request)` — `api/class-ppq-rest-controller.php`
- `public function reorder_quiz_rules($request)` — `api/class-ppq-rest-controller.php`
- `public function get_settings($request)` — `api/class-ppq-rest-controller.php`
- `public function update_settings($request)` — `api/class-ppq-rest-controller.php`
- `public function save_api_key($request)` — `api/class-ppq-rest-controller.php`
- `public function delete_api_key($request)` — `api/class-ppq-rest-controller.php`
- `public function validate_api_key($request)` — `api/class-ppq-rest-controller.php`
- `public function get_api_models($request)` — `api/class-ppq-rest-controller.php`
- `public function save_api_model($request)` — `api/class-ppq-rest-controller.php`
- `public function set_ai_provider($request)` — `api/class-ppq-rest-controller.php`
- `public function get_dashboard_stats($request)` — `api/class-ppq-rest-controller.php`
- `public function get_overview_stats($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quiz_performance($request)` — `api/class-ppq-rest-controller.php`
- `public function get_attempts_stats($request)` — `api/class-ppq-rest-controller.php`
- `public function get_attempt_detail($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quiz_filter_options($request)` — `api/class-ppq-rest-controller.php`
- `public function get_activity_chart($request)` — `api/class-ppq-rest-controller.php`
- `public function send_test_email($request)` — `api/class-ppq-rest-controller.php`
- `public function get_schema_status($request)` — `api/class-ppq-rest-controller.php`
- `public function repair_schema_status($request)` — `api/class-ppq-rest-controller.php`
- `public function create_dashboard_page($request)` — `api/class-ppq-rest-controller.php`
- `public function get_my_attempts($request)` — `api/class-ppq-rest-controller.php`
- `public function get_quiz_templates($request)` — `api/class-ppq-rest-controller.php`
- `public function set_default_quiz_template($request)` — `api/class-ppq-rest-controller.php`
- `public function create_quiz_template($request)` — `api/class-ppq-rest-controller.php`
- `public function update_quiz_template($request)` — `api/class-ppq-rest-controller.php`
- `public function delete_quiz_template($request)` — `api/class-ppq-rest-controller.php`

### `PressPrimer_Quiz_Results_Renderer`

- `public function render_results($attempt, $instance_overrides = [])` — `frontend/class-ppq-results-renderer.php`
- `public function render_question_review($attempt, $instance_overrides = [])` — `frontend/class-ppq-results-renderer.php`

### `PressPrimer_Quiz_Review_Notice`

- `public function init()` — `admin/class-ppq-review-notice.php`
- `public function should_show_notice()` — `admin/class-ppq-review-notice.php`
- `public function maybe_display_notice()` — `admin/class-ppq-review-notice.php`
- `public function enqueue_scripts()` — `admin/class-ppq-review-notice.php`
- `public function handle_review()` — `admin/class-ppq-review-notice.php`
- `public function handle_feedback()` — `admin/class-ppq-review-notice.php`
- `public function handle_snooze()` — `admin/class-ppq-review-notice.php`
- `public function handle_dismiss()` — `admin/class-ppq-review-notice.php`

### `PressPrimer_Quiz_Schema`

- `public static function get_schema()` — `database/class-ppq-schema.php`
- `public static function get_table_sql(string $table)` — `database/class-ppq-schema.php`
- `public static function get_expected_schema()` — `database/class-ppq-schema.php`

### `PressPrimer_Quiz_Schema_Verifier`

- `public static function init()` — `database/class-ppq-schema-verifier.php`
- `public static function check(?string $table = null)` — `database/class-ppq-schema-verifier.php`
- `public static function verify_targets(array $targets)` — `database/class-ppq-schema-verifier.php`
- `public static function repair_findings()` — `database/class-ppq-schema-verifier.php`
- `public static function repair(array $problems, bool $respect_cap = false)` — `database/class-ppq-schema-verifier.php`
- `public static function maybe_self_heal()` — `database/class-ppq-schema-verifier.php`
- `public static function clear_self_heal_throttle()` — `database/class-ppq-schema-verifier.php`
- `public static function record_migration_problem(string $version, array $missing)` — `database/class-ppq-schema-verifier.php`
- `public static function maybe_render_notice()` — `database/class-ppq-schema-verifier.php`
- `public static function get_log()` — `database/class-ppq-schema-verifier.php`

### `PressPrimer_Quiz_Scoring_Copy`

- `public static function get_mode_keys()` — `utilities/class-ppq-scoring-copy.php`
- `public static function is_mode($mode)` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_label($mode)` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_description($mode)` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_examples($mode)` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_formula_template($mode)` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_mode($mode)` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_modes()` — `utilities/class-ppq-scoring-copy.php`
- `public static function get_modes_for_js()` — `utilities/class-ppq-scoring-copy.php`

### `PressPrimer_Quiz_Scoring_Service`

- `public function score_response($question, $revision, array $selected_answers, $quiz = null, $shown_indices = null)` — `services/class-ppq-scoring-service.php`
- `public function score_mc(array $correct_indices, array $selected_answers, float $max_points)` — `services/class-ppq-scoring-service.php`
- `public function score_ma(array $correct_indices, array $selected_answers, float $max_points, string $mode = 'right_minus_wrong')` — `services/class-ppq-scoring-service.php`
- `public function score_tf(array $correct_indices, array $selected_answers, float $max_points)` — `services/class-ppq-scoring-service.php`
- `public function calculate_attempt_score(int $attempt_id)` — `services/class-ppq-scoring-service.php`
- `public static function instance()` — `services/class-ppq-scoring-service.php`

### `PressPrimer_Quiz_Shell`

- `public static function init()` — `frontend/class-ppq-shell.php`
- `public static function render($atts = array()` — `frontend/class-ppq-shell.php`
- `public static function maybe_enqueue_assets()` — `frontend/class-ppq-shell.php`
- `public static function register_builtin_screens($screens)` — `frontend/class-ppq-shell.php`
- `public static function get_screens()` — `frontend/class-ppq-shell.php`
- `public static function get_groups()` — `frontend/class-ppq-shell.php`
- `public static function get_visible_screens()` — `frontend/class-ppq-shell.php`
- `public static function get_boot_payload($visible_screens = null)` — `frontend/class-ppq-shell.php`

### `PressPrimer_Quiz_Shortcodes`

- `public function init()` — `frontend/class-ppq-shortcodes.php`
- `public function register_shortcodes()` — `frontend/class-ppq-shortcodes.php`
- `public function render_dashboard($atts = [])` — `frontend/class-ppq-shortcodes.php`
- `public function parse_quiz_attributes($atts)` — `frontend/class-ppq-shortcodes.php`
- `public function render_quiz($atts)` — `frontend/class-ppq-shortcodes.php`
- `public function render_my_attempts($atts)` — `frontend/class-ppq-shortcodes.php`

### `PressPrimer_Quiz_Statistics_Service`

- `public function get_dashboard_stats($owner_id = null)` — `services/class-ppq-statistics-service.php`
- `public static function clear_dashboard_cache($owner_id = null)` — `services/class-ppq-statistics-service.php`
- `public function get_overview_stats($args = [])` — `services/class-ppq-statistics-service.php`
- `public function calculate_overview_stats($owner_id = null)` — `services/class-ppq-statistics-service.php`
- `public function calculate_and_cache_overview_stats()` — `services/class-ppq-statistics-service.php`
- `public static function schedule_cron()` — `services/class-ppq-statistics-service.php`
- `public static function unschedule_cron()` — `services/class-ppq-statistics-service.php`
- `public static function cron_recalculate_stats()` — `services/class-ppq-statistics-service.php`
- `public function get_quiz_performance($args = [])` — `services/class-ppq-statistics-service.php`
- `public function get_recent_attempts($args = [])` — `services/class-ppq-statistics-service.php`
- `public function get_attempt_detail($attempt_id, $owner_id = null)` — `services/class-ppq-statistics-service.php`
- `public function get_activity_chart_data($args = [])` — `services/class-ppq-statistics-service.php`
- `public static function clear_activity_chart_cache()` — `services/class-ppq-statistics-service.php`
- `public function get_quiz_filter_options($owner_id = null)` — `services/class-ppq-statistics-service.php`

### `PressPrimer_Quiz_Template_Presets`

- `public static function register()` — `services/class-ppq-template-presets.php`
- `public static function add_built_in_presets($presets)` — `services/class-ppq-template-presets.php`
- `public static function get_built_in_presets()` — `services/class-ppq-template-presets.php`

### `PressPrimer_Quiz_Theme_Loader`

- `public static function get_available_themes()` — `frontend/class-ppq-theme-loader.php`
- `public static function get_theme($theme_id)` — `frontend/class-ppq-theme-loader.php`
- `public static function get_quiz_theme($quiz)` — `frontend/class-ppq-theme-loader.php`
- `public static function get_theme_class($theme_id)` — `frontend/class-ppq-theme-loader.php`
- `public static function enqueue_theme($theme_id)` — `frontend/class-ppq-theme-loader.php`
- `public static function enqueue_quiz_theme($quiz)` — `frontend/class-ppq-theme-loader.php`
- `public static function get_custom_css($quiz)` — `frontend/class-ppq-theme-loader.php`
- `public static function output_custom_css($quiz)` — `frontend/class-ppq-theme-loader.php`
- `public static function get_appearance_css_vars($settings = null)` — `frontend/class-ppq-theme-loader.php`
- `public static function get_global_appearance_css()` — `frontend/class-ppq-theme-loader.php`

### `PressPrimer_Quiz_TutorLMS`

- `public function init()` — `integrations/class-ppq-tutorlms.php`
- `public function register_meta_boxes()` — `integrations/class-ppq-tutorlms.php`
- `public function render_meta_box($post)` — `integrations/class-ppq-tutorlms.php`
- `public function save_meta_box($post_id, $post)` — `integrations/class-ppq-tutorlms.php`
- `public function register_meta_fields()` — `integrations/class-ppq-tutorlms.php`
- `public function enqueue_block_editor_assets()` — `integrations/class-ppq-tutorlms.php`
- `public function enqueue_course_builder_assets($hook)` — `integrations/class-ppq-tutorlms.php`
- `public function ajax_search_quizzes()` — `integrations/class-ppq-tutorlms.php`
- `public function append_quiz_to_lesson_content($content)` — `integrations/class-ppq-tutorlms.php`
- `public function force_lesson_content_for_quiz($has_content, $lesson_id)` — `integrations/class-ppq-tutorlms.php`
- `public function display_topic_quizzes($course_id)` — `integrations/class-ppq-tutorlms.php`
- `public function inject_topic_quiz_content($contents, $topic_id)` — `integrations/class-ppq-tutorlms.php`
- `public function maybe_hide_complete_button($form)` — `integrations/class-ppq-tutorlms.php`
- `public function validate_lesson_complete($validated, $user_id, $lesson_id)` — `integrations/class-ppq-tutorlms.php`
- `public function handle_quiz_passed($attempt, $quiz)` — `integrations/class-ppq-tutorlms.php`
- `public function check_instructor_capability($has_capability, $user_id)` — `integrations/class-ppq-tutorlms.php`
- `public function register_rest_routes()` — `integrations/class-ppq-tutorlms.php`
- `public function rest_search_quizzes($request)` — `integrations/class-ppq-tutorlms.php`
- `public function rest_get_status($request)` — `integrations/class-ppq-tutorlms.php`
- `public function rest_save_lesson_quiz($request)` — `integrations/class-ppq-tutorlms.php`

### `PressPrimer_Quiz_Upgrade_Page`

- `public function init()` — `admin/class-ppq-upgrade-page.php`
- `public function register_menu()` — `admin/class-ppq-upgrade-page.php`
- `public function enterprise_addon_active()` — `admin/class-ppq-upgrade-page.php`
- `public function menu_styles()` — `admin/class-ppq-upgrade-page.php`
- `public function enqueue_assets($hook)` — `admin/class-ppq-upgrade-page.php`
- `public function render_page()` — `admin/class-ppq-upgrade-page.php`
- `public function render_cell_value($value)` — `admin/class-ppq-upgrade-page.php`
- `public static function get_comparison_features()` — `admin/class-ppq-upgrade-page.php`
- `public static function get_tiers()` — `admin/class-ppq-upgrade-page.php`
- `public static function get_premium_report_catalog()` — `admin/class-ppq-upgrade-page.php`
- `public static function get_premium_report_cards()` — `admin/class-ppq-upgrade-page.php`
