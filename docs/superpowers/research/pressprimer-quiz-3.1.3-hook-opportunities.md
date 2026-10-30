# PressPrimer Quiz 3.1.3 — every hook annotated for Burbank

Every hook from the inventory, tagged with a Burbank opportunity bucket.

| Pri | Bucket | Hook | Kind | File |
| --- | --- | --- | --- | --- |
| 10 | ACCESS | `pressprimer_quiz_before_submit` | filter | `includes/frontend/class-ppq-ajax-handler.php` |
| 10 | ACCESS | `pressprimer_quiz_can_start` | filter | `includes/frontend/class-ppq-ajax-handler.php` |
| 10 | ACCESS | `pressprimer_quiz_login_url` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 10 | ACCESS | `pressprimer_quiz_register_url` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 10 | OUTCOME | `pressprimer_quiz_attempt_started` | action | `includes/models/class-ppq-attempt.php` |
| 10 | OUTCOME | `pressprimer_quiz_attempt_submitted` | action | `includes/models/class-ppq-attempt.php` |
| 10 | OUTCOME | `pressprimer_quiz_quiz_completed` | action | `includes/models/class-ppq-attempt.php` |
| 10 | OUTCOME | `pressprimer_quiz_quiz_failed` | action | `includes/models/class-ppq-attempt.php` |
| 10 | OUTCOME | `pressprimer_quiz_quiz_passed` | action | `includes/models/class-ppq-attempt.php` |
| 9 | AUTHORING_SYNC | `pressprimer_quiz_quiz_created` | action | `includes/models/class-ppq-quiz.php` |
| 9 | AUTHORING_SYNC | `pressprimer_quiz_quiz_deleted` | action | `includes/models/class-ppq-quiz.php` |
| 9 | AUTHORING_SYNC | `pressprimer_quiz_quiz_published` | action | `includes/models/class-ppq-quiz.php` |
| 9 | AUTHORING_SYNC | `pressprimer_quiz_quiz_updated` | action | `includes/models/class-ppq-quiz.php` |
| 9 | AUTHORING_SYNC | `pressprimer_quiz_rest_quiz_saved` | action | `includes/api/class-ppq-rest-controller.php` |
| 8 | EMBED_UX | `pressprimer_quiz_after_quiz_meta` | action | `includes/frontend/class-ppq-quiz-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_after_results` | action | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_before_quiz_interface` | action | `includes/frontend/class-ppq-quiz-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_before_quiz_meta` | action | `includes/frontend/class-ppq-quiz-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_before_results` | action | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_interface_data_attributes` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_quiz_interface_start` | action | `includes/frontend/class-ppq-quiz-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_render_question` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_actions_end` | action | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_actions_start` | action | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_after_review` | action | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_after_score` | action | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_data` | filter | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_display_options` | filter | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_email_sent` | action | `includes/services/class-ppq-email-service.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_score_html` | filter | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_results_sections` | filter | `includes/frontend/class-ppq-results-renderer.php` |
| 8 | EMBED_UX | `pressprimer_quiz_shell_branding` | filter | `includes/frontend/class-ppq-shell.php` |
| 8 | EMBED_UX | `pressprimer_quiz_shell_groups` | filter | `includes/frontend/class-ppq-shell.php` |
| 8 | EMBED_UX | `pressprimer_quiz_shell_rendered` | action | `includes/frontend/class-ppq-shell.php` |
| 8 | EMBED_UX | `pressprimer_quiz_shell_screens` | filter | `includes/frontend/class-ppq-shell.php` |
| 8 | EMBED_UX | `pressprimer_quiz_shell_suppress_upsells` | filter | `includes/frontend/class-ppq-shell.php` |
| 7 | IDENTITY_CAPS | `pressprimer_quiz_accessible_bank_ids` | filter | `includes/api/class-ppq-rest-controller.php` |
| 7 | IDENTITY_CAPS | `pressprimer_quiz_can_access_bank` | filter | `includes/models/class-ppq-bank.php` |
| 7 | IDENTITY_CAPS | `pressprimer_quiz_can_edit_bank` | filter | `includes/admin/class-ppq-admin-banks.php` |
| 7 | IDENTITY_CAPS | `pressprimer_quiz_exposure_scope` | filter | `includes/services/class-ppq-exposure-service.php` |
| 7 | IDENTITY_CAPS | `pressprimer_quiz_exposure_seen_questions` | filter | `includes/services/class-ppq-exposure-service.php` |
| 6 | SCORING | `pressprimer_quiz_question_score` | filter | `includes/services/class-ppq-scoring-service.php` |
| 6 | SCORING | `pressprimer_quiz_score_explanation` | filter | `includes/frontend/class-ppq-results-renderer.php` |
| 6 | SCORING | `pressprimer_quiz_scoring_totals` | filter | `includes/services/class-ppq-scoring-service.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_admin_menu` | action | `includes/admin/class-ppq-admin.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_bank_list_column` | action | `includes/admin/class-ppq-banks-list-table.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_bank_list_columns` | filter | `includes/admin/class-ppq-banks-list-table.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_banks_toolbar` | action | `includes/admin/class-ppq-admin-banks.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_dashboard_logo` | filter | `includes/admin/class-ppq-admin.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_dashboard_page_slug` | filter | `includes/api/class-ppq-rest-controller.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_dashboard_page_title` | filter | `includes/api/class-ppq-rest-controller.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_dashboard_stats` | filter | `includes/services/class-ppq-statistics-service.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_dashboard_welcome_text` | filter | `includes/admin/class-ppq-admin.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_onboarding_completed` | action | `includes/admin/class-ppq-onboarding.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_onboarding_reset` | action | `includes/admin/class-ppq-onboarding.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_onboarding_skipped` | action | `includes/admin/class-ppq-onboarding.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_onboarding_started` | action | `includes/admin/class-ppq-onboarding.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_questions_list_column` | action | `includes/admin/class-ppq-admin-questions.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_questions_list_columns` | filter | `includes/admin/class-ppq-admin-questions.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_questions_list_constrain_ids` | filter | `includes/admin/class-ppq-admin-questions.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_questions_list_filters` | action | `includes/admin/class-ppq-admin-questions.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_settings_data` | filter | `includes/admin/class-ppq-admin-settings.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_settings_header_mascot` | filter | `includes/admin/class-ppq-admin-settings.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_settings_tabs` | filter | `includes/admin/class-ppq-admin-settings.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_settings_template_presets` | filter | `includes/api/class-ppq-rest-controller.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_settings_updated` | action | `includes/api/class-ppq-rest-controller.php` |
| 5 | ADMIN_UX | `pressprimer_quiz_template_settings_keys` | filter | `includes/models/class-ppq-quiz.php` |
| 4 | EMAIL | `pressprimer_quiz_email_footer` | filter | `includes/services/class-ppq-email-service.php` |
| 4 | EMAIL | `pressprimer_quiz_email_header` | filter | `includes/services/class-ppq-email-service.php` |
| 4 | EMAIL | `pressprimer_quiz_guest_email_captured` | action | `includes/models/class-ppq-attempt.php` |
| 3 | AI | `pressprimer_quiz_ai_models_anthropic` | filter | `includes/services/ai/class-ppq-ai-provider-anthropic.php` |
| 3 | AI | `pressprimer_quiz_ai_models_openai` | filter | `includes/services/ai/class-ppq-ai-provider-openai.php` |
| 3 | AI | `pressprimer_quiz_ai_providers` | filter | `includes/services/class-ppq-ai-service.php` |
| 3 | LMS_BRIDGE | `pressprimer_quiz_learnpress_lesson_completed` | action | `includes/integrations/class-ppq-learnpress.php` |
| 3 | LMS_BRIDGE | `pressprimer_quiz_lifterlms_lesson_completed` | action | `includes/integrations/class-ppq-lifterlms.php` |
| 3 | LMS_BRIDGE | `pressprimer_quiz_tutorlms_lesson_completed` | action | `includes/integrations/class-ppq-tutorlms.php` |
| 2 | BRANDING | `pressprimer_quiz_allow_custom_theme_css` | filter | `includes/frontend/class-ppq-theme-loader.php` |
| 2 | BRANDING | `pressprimer_quiz_available_themes` | filter | `includes/frontend/class-ppq-theme-loader.php` |
| 2 | BRANDING | `pressprimer_quiz_global_appearance_css` | filter | `includes/frontend/class-ppq-theme-loader.php` |
| 2 | BRANDING | `pressprimer_quiz_plugin_name` | filter | `includes/admin/class-ppq-admin.php` |
| 2 | BRANDING | `pressprimer_quiz_quiz_custom_css` | filter | `includes/frontend/class-ppq-theme-loader.php` |
| 2 | BRANDING | `pressprimer_quiz_quiz_theme` | filter | `includes/frontend/class-ppq-theme-loader.php` |
| 2 | BRANDING | `pressprimer_quiz_reports_header_mascot` | filter | `includes/admin/class-ppq-admin.php` |
| 2 | BRANDING | `pressprimer_quiz_theme_enqueued` | action | `includes/frontend/class-ppq-theme-loader.php` |
| 2 | OTHER | `pressprimer_quiz_allow_backward` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 2 | OTHER | `pressprimer_quiz_attempt_deleted` | action | `includes/models/class-ppq-attempt.php` |
| 2 | OTHER | `pressprimer_quiz_bank_created` | action | `includes/models/class-ppq-bank.php` |
| 2 | OTHER | `pressprimer_quiz_bank_deleted` | action | `includes/models/class-ppq-bank.php` |
| 2 | OTHER | `pressprimer_quiz_bank_detail_after_header` | action | `includes/admin/class-ppq-admin-banks.php` |
| 2 | OTHER | `pressprimer_quiz_bank_questions_actions` | action | `includes/admin/class-ppq-admin-banks.php` |
| 2 | OTHER | `pressprimer_quiz_bank_questions_per_page` | filter | `includes/admin/class-ppq-admin-banks.php` |
| 2 | OTHER | `pressprimer_quiz_bank_row_actions` | filter | `includes/admin/class-ppq-banks-list-table.php` |
| 2 | OTHER | `pressprimer_quiz_bank_updated` | action | `includes/models/class-ppq-bank.php` |
| 2 | OTHER | `pressprimer_quiz_before_progress_reset` | action | `includes/services/class-ppq-progress-reset-service.php` |
| 2 | OTHER | `pressprimer_quiz_guest_consent_args` | filter | `includes/frontend/class-ppq-ajax-handler.php` |
| 2 | OTHER | `pressprimer_quiz_guest_data_erased` | action | `includes/utilities/class-ppq-privacy.php` |
| 2 | OTHER | `pressprimer_quiz_localize_strings` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 2 | OTHER | `pressprimer_quiz_overview_stats` | filter | `includes/services/class-ppq-statistics-service.php` |
| 2 | OTHER | `pressprimer_quiz_page_mode` | filter | `includes/frontend/class-ppq-quiz-renderer.php` |
| 2 | OTHER | `pressprimer_quiz_plugin_icon` | filter | `includes/admin/class-ppq-admin.php` |
| 2 | OTHER | `pressprimer_quiz_progress_reset` | action | `includes/services/class-ppq-progress-reset-service.php` |
| 2 | OTHER | `pressprimer_quiz_question_created` | action | `includes/models/class-ppq-question.php` |
| 2 | OTHER | `pressprimer_quiz_question_deleted` | action | `includes/models/class-ppq-question.php` |
| 2 | OTHER | `pressprimer_quiz_question_editor_enqueue_scripts` | action | `includes/admin/class-ppq-admin-questions.php` |
| 2 | OTHER | `pressprimer_quiz_question_editor_tabs` | filter | `includes/admin/class-ppq-admin-questions.php` |
| 2 | OTHER | `pressprimer_quiz_question_restored` | action | `includes/models/class-ppq-question.php` |
| 2 | OTHER | `pressprimer_quiz_question_updated` | action | `includes/models/class-ppq-question.php` |
| 2 | OTHER | `pressprimer_quiz_quiz_editor_data` | filter | `includes/admin/class-ppq-admin-quizzes.php` |
| 2 | OTHER | `pressprimer_quiz_quiz_meta_items` | action | `includes/frontend/class-ppq-quiz-renderer.php` |
| 2 | OTHER | `pressprimer_quiz_register_addons` | action | `includes/class-ppq-addon-manager.php` |
| 2 | OTHER | `pressprimer_quiz_reset_preview` | filter | `includes/services/class-ppq-progress-reset-service.php` |
| 2 | OTHER | `pressprimer_quiz_sanitize_settings` | filter | `includes/api/class-ppq-rest-controller.php` |
| 2 | OTHER | `pressprimer_quiz_search_question_author_ids` | filter | `includes/admin/class-ppq-admin.php` |
| 2 | OTHER | `pressprimer_quiz_show_category_breakdown` | filter | `includes/frontend/class-ppq-results-renderer.php` |
| 1 | INTERNAL | `pressprimer_quiz_addon_registered` | action | `includes/class-ppq-addon-manager.php` |
| 1 | INTERNAL | `pressprimer_quiz_expected_schema` | filter | `includes/database/class-ppq-schema.php` |
| 1 | INTERNAL | `pressprimer_quiz_generation_candidates` | filter | `includes/models/class-ppq-quiz.php` |
| 1 | INTERNAL | `pressprimer_quiz_image_upload_allowed_mimes` | filter | `includes/api/class-ppq-rest-controller.php` |
| 1 | INTERNAL | `pressprimer_quiz_image_upload_max_size` | filter | `includes/api/class-ppq-rest-controller.php` |
| 1 | INTERNAL | `pressprimer_quiz_math_auto_selectors` | filter | `pressprimer-quiz.php` |
| 1 | INTERNAL | `pressprimer_quiz_math_delimiters` | filter | `pressprimer-quiz.php` |
| 1 | INTERNAL | `pressprimer_quiz_quiz_density` | filter | `includes/models/class-ppq-quiz.php` |
| 1 | INTERNAL | `pressprimer_quiz_randomize_questions` | filter | `includes/models/class-ppq-quiz.php` |
| 1 | INTERNAL | `pressprimer_quiz_reports_addon_reports` | filter | `includes/admin/class-ppq-admin.php` |
| 1 | INTERNAL | `pressprimer_quiz_status_tables` | filter | `includes/database/class-ppq-migrator.php` |
| 1 | INTERNAL | `pressprimer_quiz_template_applied_defaults` | action | `includes/models/class-ppq-quiz.php` |

## Bucket counts

- **OTHER**: 30
- **ADMIN_UX**: 23
- **EMBED_UX**: 22
- **INTERNAL**: 12
- **BRANDING**: 8
- **OUTCOME**: 5
- **AUTHORING_SYNC**: 5
- **IDENTITY_CAPS**: 5
- **ACCESS**: 4
- **SCORING**: 3
- **EMAIL**: 3
- **AI**: 3
- **LMS_BRIDGE**: 3
