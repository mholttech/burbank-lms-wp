# PressPrimer Quiz — hooks

Pinned version: **3.1.3**

Total unique hooks: **126**

- Actions only: 55
- Filters only: 71
- Used as both: 0

## Actions

| Hook | Kind | Occurrences | Example file |
| --- | --- | --- | --- |
| `pressprimer_quiz_addon_registered` | action | 1 | `includes/class-ppq-addon-manager.php` |
| `pressprimer_quiz_admin_menu` | action | 1 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_after_quiz_meta` | action | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_after_results` | action | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_attempt_deleted` | action | 1 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_attempt_started` | action | 2 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_attempt_submitted` | action | 1 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_bank_created` | action | 1 | `includes/models/class-ppq-bank.php` |
| `pressprimer_quiz_bank_deleted` | action | 1 | `includes/models/class-ppq-bank.php` |
| `pressprimer_quiz_bank_detail_after_header` | action | 1 | `includes/admin/class-ppq-admin-banks.php` |
| `pressprimer_quiz_bank_list_column` | action | 1 | `includes/admin/class-ppq-banks-list-table.php` |
| `pressprimer_quiz_bank_questions_actions` | action | 1 | `includes/admin/class-ppq-admin-banks.php` |
| `pressprimer_quiz_bank_updated` | action | 1 | `includes/models/class-ppq-bank.php` |
| `pressprimer_quiz_banks_toolbar` | action | 1 | `includes/admin/class-ppq-admin-banks.php` |
| `pressprimer_quiz_before_progress_reset` | action | 1 | `includes/services/class-ppq-progress-reset-service.php` |
| `pressprimer_quiz_before_quiz_interface` | action | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_before_quiz_meta` | action | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_before_results` | action | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_guest_data_erased` | action | 1 | `includes/utilities/class-ppq-privacy.php` |
| `pressprimer_quiz_guest_email_captured` | action | 2 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_learnpress_lesson_completed` | action | 1 | `includes/integrations/class-ppq-learnpress.php` |
| `pressprimer_quiz_lifterlms_lesson_completed` | action | 1 | `includes/integrations/class-ppq-lifterlms.php` |
| `pressprimer_quiz_onboarding_completed` | action | 1 | `includes/admin/class-ppq-onboarding.php` |
| `pressprimer_quiz_onboarding_reset` | action | 1 | `includes/admin/class-ppq-onboarding.php` |
| `pressprimer_quiz_onboarding_skipped` | action | 1 | `includes/admin/class-ppq-onboarding.php` |
| `pressprimer_quiz_onboarding_started` | action | 1 | `includes/admin/class-ppq-onboarding.php` |
| `pressprimer_quiz_progress_reset` | action | 1 | `includes/services/class-ppq-progress-reset-service.php` |
| `pressprimer_quiz_question_created` | action | 1 | `includes/models/class-ppq-question.php` |
| `pressprimer_quiz_question_deleted` | action | 1 | `includes/models/class-ppq-question.php` |
| `pressprimer_quiz_question_editor_enqueue_scripts` | action | 1 | `includes/admin/class-ppq-admin-questions.php` |
| `pressprimer_quiz_question_restored` | action | 1 | `includes/models/class-ppq-question.php` |
| `pressprimer_quiz_question_updated` | action | 1 | `includes/models/class-ppq-question.php` |
| `pressprimer_quiz_questions_list_column` | action | 1 | `includes/admin/class-ppq-admin-questions.php` |
| `pressprimer_quiz_questions_list_filters` | action | 1 | `includes/admin/class-ppq-admin-questions.php` |
| `pressprimer_quiz_quiz_completed` | action | 1 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_quiz_created` | action | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_quiz_deleted` | action | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_quiz_failed` | action | 1 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_quiz_interface_start` | action | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_quiz_meta_items` | action | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_quiz_passed` | action | 1 | `includes/models/class-ppq-attempt.php` |
| `pressprimer_quiz_quiz_published` | action | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_quiz_updated` | action | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_register_addons` | action | 1 | `includes/class-ppq-addon-manager.php` |
| `pressprimer_quiz_rest_quiz_saved` | action | 2 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_results_actions_end` | action | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_actions_start` | action | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_after_review` | action | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_after_score` | action | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_email_sent` | action | 1 | `includes/services/class-ppq-email-service.php` |
| `pressprimer_quiz_settings_updated` | action | 1 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_shell_rendered` | action | 1 | `includes/frontend/class-ppq-shell.php` |
| `pressprimer_quiz_template_applied_defaults` | action | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_theme_enqueued` | action | 1 | `includes/frontend/class-ppq-theme-loader.php` |
| `pressprimer_quiz_tutorlms_lesson_completed` | action | 1 | `includes/integrations/class-ppq-tutorlms.php` |

## Filters

| Hook | Kind | Occurrences | Example file |
| --- | --- | --- | --- |
| `pressprimer_quiz_accessible_bank_ids` | filter | 2 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_ai_models_anthropic` | filter | 2 | `includes/services/ai/class-ppq-ai-provider-anthropic.php` |
| `pressprimer_quiz_ai_models_openai` | filter | 1 | `includes/services/ai/class-ppq-ai-provider-openai.php` |
| `pressprimer_quiz_ai_providers` | filter | 1 | `includes/services/class-ppq-ai-service.php` |
| `pressprimer_quiz_allow_backward` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_allow_custom_theme_css` | filter | 1 | `includes/frontend/class-ppq-theme-loader.php` |
| `pressprimer_quiz_available_themes` | filter | 1 | `includes/frontend/class-ppq-theme-loader.php` |
| `pressprimer_quiz_bank_list_columns` | filter | 1 | `includes/admin/class-ppq-banks-list-table.php` |
| `pressprimer_quiz_bank_questions_per_page` | filter | 1 | `includes/admin/class-ppq-admin-banks.php` |
| `pressprimer_quiz_bank_row_actions` | filter | 1 | `includes/admin/class-ppq-banks-list-table.php` |
| `pressprimer_quiz_before_submit` | filter | 1 | `includes/frontend/class-ppq-ajax-handler.php` |
| `pressprimer_quiz_can_access_bank` | filter | 1 | `includes/models/class-ppq-bank.php` |
| `pressprimer_quiz_can_edit_bank` | filter | 1 | `includes/admin/class-ppq-admin-banks.php` |
| `pressprimer_quiz_can_start` | filter | 2 | `includes/frontend/class-ppq-ajax-handler.php` |
| `pressprimer_quiz_dashboard_logo` | filter | 1 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_dashboard_page_slug` | filter | 1 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_dashboard_page_title` | filter | 1 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_dashboard_stats` | filter | 1 | `includes/services/class-ppq-statistics-service.php` |
| `pressprimer_quiz_dashboard_welcome_text` | filter | 1 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_email_footer` | filter | 1 | `includes/services/class-ppq-email-service.php` |
| `pressprimer_quiz_email_header` | filter | 1 | `includes/services/class-ppq-email-service.php` |
| `pressprimer_quiz_expected_schema` | filter | 1 | `includes/database/class-ppq-schema.php` |
| `pressprimer_quiz_exposure_scope` | filter | 1 | `includes/services/class-ppq-exposure-service.php` |
| `pressprimer_quiz_exposure_seen_questions` | filter | 1 | `includes/services/class-ppq-exposure-service.php` |
| `pressprimer_quiz_generation_candidates` | filter | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_global_appearance_css` | filter | 1 | `includes/frontend/class-ppq-theme-loader.php` |
| `pressprimer_quiz_guest_consent_args` | filter | 1 | `includes/frontend/class-ppq-ajax-handler.php` |
| `pressprimer_quiz_image_upload_allowed_mimes` | filter | 2 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_image_upload_max_size` | filter | 2 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_interface_data_attributes` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_localize_strings` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_login_url` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_math_auto_selectors` | filter | 1 | `pressprimer-quiz.php` |
| `pressprimer_quiz_math_delimiters` | filter | 1 | `pressprimer-quiz.php` |
| `pressprimer_quiz_overview_stats` | filter | 2 | `includes/services/class-ppq-statistics-service.php` |
| `pressprimer_quiz_page_mode` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_plugin_icon` | filter | 1 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_plugin_name` | filter | 2 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_question_editor_tabs` | filter | 1 | `includes/admin/class-ppq-admin-questions.php` |
| `pressprimer_quiz_question_score` | filter | 1 | `includes/services/class-ppq-scoring-service.php` |
| `pressprimer_quiz_questions_list_columns` | filter | 1 | `includes/admin/class-ppq-admin-questions.php` |
| `pressprimer_quiz_questions_list_constrain_ids` | filter | 1 | `includes/admin/class-ppq-admin-questions.php` |
| `pressprimer_quiz_quiz_custom_css` | filter | 1 | `includes/frontend/class-ppq-theme-loader.php` |
| `pressprimer_quiz_quiz_density` | filter | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_quiz_editor_data` | filter | 1 | `includes/admin/class-ppq-admin-quizzes.php` |
| `pressprimer_quiz_quiz_theme` | filter | 1 | `includes/frontend/class-ppq-theme-loader.php` |
| `pressprimer_quiz_randomize_questions` | filter | 1 | `includes/models/class-ppq-quiz.php` |
| `pressprimer_quiz_register_url` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_render_question` | filter | 1 | `includes/frontend/class-ppq-quiz-renderer.php` |
| `pressprimer_quiz_reports_addon_reports` | filter | 1 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_reports_header_mascot` | filter | 1 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_reset_preview` | filter | 1 | `includes/services/class-ppq-progress-reset-service.php` |
| `pressprimer_quiz_results_data` | filter | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_display_options` | filter | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_score_html` | filter | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_results_sections` | filter | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_sanitize_settings` | filter | 1 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_score_explanation` | filter | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_scoring_totals` | filter | 1 | `includes/services/class-ppq-scoring-service.php` |
| `pressprimer_quiz_search_question_author_ids` | filter | 2 | `includes/admin/class-ppq-admin.php` |
| `pressprimer_quiz_settings_data` | filter | 1 | `includes/admin/class-ppq-admin-settings.php` |
| `pressprimer_quiz_settings_header_mascot` | filter | 1 | `includes/admin/class-ppq-admin-settings.php` |
| `pressprimer_quiz_settings_tabs` | filter | 1 | `includes/admin/class-ppq-admin-settings.php` |
| `pressprimer_quiz_settings_template_presets` | filter | 2 | `includes/api/class-ppq-rest-controller.php` |
| `pressprimer_quiz_shell_branding` | filter | 1 | `includes/frontend/class-ppq-shell.php` |
| `pressprimer_quiz_shell_groups` | filter | 1 | `includes/frontend/class-ppq-shell.php` |
| `pressprimer_quiz_shell_screens` | filter | 1 | `includes/frontend/class-ppq-shell.php` |
| `pressprimer_quiz_shell_suppress_upsells` | filter | 1 | `includes/frontend/class-ppq-shell.php` |
| `pressprimer_quiz_show_category_breakdown` | filter | 1 | `includes/frontend/class-ppq-results-renderer.php` |
| `pressprimer_quiz_status_tables` | filter | 1 | `includes/database/class-ppq-migrator.php` |
| `pressprimer_quiz_template_settings_keys` | filter | 1 | `includes/models/class-ppq-quiz.php` |
