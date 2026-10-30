# PressPrimer Certificate — hooks

Pinned version: **2.0.2**

Total unique hooks: **69**

- Actions only: 25
- Filters only: 44
- Used as both: 0

## Actions

| Hook | Kind | Occurrences | Example file |
| --- | --- | --- | --- |
| `ppcert_admin_menu_registered` | action | 1 | `includes/admin/class-ppcert-admin.php` |
| `ppcert_before_issue` | action | 1 | `includes/services/class-ppcert-issuance-service.php` |
| `ppcert_certificate_deleted` | action | 1 | `includes/models/class-ppcert-certificate.php` |
| `ppcert_certificate_issued` | action | 1 | `includes/services/class-ppcert-issuance-service.php` |
| `ppcert_certificate_reinstated` | action | 1 | `includes/models/class-ppcert-certificate.php` |
| `ppcert_certificate_revoked` | action | 1 | `includes/models/class-ppcert-certificate.php` |
| `ppcert_certificate_verified` | action | 1 | `includes/api/class-ppcert-rest-verification-controller.php` |
| `ppcert_designer_enqueued` | action | 1 | `includes/admin/class-ppcert-admin.php` |
| `ppcert_email_optin_submitted` | action | 1 | `includes/api/class-ppcert-rest-email-optin-controller.php` |
| `ppcert_loaded` | action | 1 | `includes/class-ppcert-plugin.php` |
| `ppcert_onboarding_completed` | action | 1 | `includes/admin/class-ppcert-onboarding.php` |
| `ppcert_onboarding_reset` | action | 1 | `includes/admin/class-ppcert-onboarding.php` |
| `ppcert_onboarding_skipped` | action | 1 | `includes/admin/class-ppcert-onboarding.php` |
| `ppcert_onboarding_started` | action | 1 | `includes/admin/class-ppcert-onboarding.php` |
| `ppcert_pdf_generated` | action | 1 | `includes/services/class-ppcert-pdf-renderer.php` |
| `ppcert_preview_render_failed` | action | 1 | `includes/services/class-ppcert-preview-service.php` |
| `ppcert_settings_saved` | action | 1 | `includes/api/class-ppcert-rest-settings-controller.php` |
| `ppcert_template_created` | action | 1 | `includes/models/class-ppcert-template.php` |
| `ppcert_template_duplicated` | action | 1 | `includes/models/class-ppcert-template.php` |
| `ppcert_template_issuer_changed` | action | 1 | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert_template_list_column` | action | 1 | `includes/admin/class-ppcert-templates-list-table.php` |
| `ppcert_template_list_filters` | action | 1 | `includes/admin/class-ppcert-templates-list-table.php` |
| `ppcert_template_trashed` | action | 1 | `includes/models/class-ppcert-template.php` |
| `ppcert_template_updated` | action | 1 | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert_view_page_head` | action | 1 | `includes/frontend/class-ppcert-view-page.php` |

## Filters

| Hook | Kind | Occurrences | Example file |
| --- | --- | --- | --- |
| `ppcert_adapter_classes` | filter | 1 | `includes/class-ppcert-plugin.php` |
| `ppcert_admin_branding` | filter | 1 | `includes/admin/class-ppcert-admin.php` |
| `ppcert_certificate_link_action` | filter | 1 | `includes/frontend/class-ppcert-certificate-link.php` |
| `ppcert_certificate_link_embedded_sources` | filter | 1 | `includes/frontend/class-ppcert-certificate-link.php` |
| `ppcert_certificate_list_row_actions` | filter | 1 | `includes/admin/class-ppcert-certificates-list-table.php` |
| `ppcert_certificate_scope` | filter | 1 | `includes/models/class-ppcert-certificate.php` |
| `ppcert_dashboard_logo` | filter | 1 | `includes/admin/class-ppcert-admin-dashboard.php` |
| `ppcert_dashboard_welcome_text` | filter | 1 | `includes/admin/class-ppcert-admin-dashboard.php` |
| `ppcert_designer_element_types` | filter | 1 | `includes/services/class-ppcert-element-types.php` |
| `ppcert_designer_fonts` | filter | 1 | `includes/services/class-ppcert-layout-validator.php` |
| `ppcert_email_content` | filter | 2 | `includes/services/class-ppcert-email-service.php` |
| `ppcert_email_enabled` | filter | 1 | `includes/services/class-ppcert-email-service.php` |
| `ppcert_email_footer` | filter | 1 | `includes/services/class-ppcert-email-service.php` |
| `ppcert_email_from` | filter | 1 | `includes/services/class-ppcert-email-service.php` |
| `ppcert_email_intake_url` | filter | 1 | `includes/services/class-ppcert-email-optin-service.php` |
| `ppcert_issue_duplicate_ref` | filter | 1 | `includes/services/class-ppcert-issuance-service.php` |
| `ppcert_issue_templates` | filter | 1 | `includes/admin/class-ppcert-admin-certificates.php` |
| `ppcert_issue_validation` | filter | 1 | `includes/services/class-ppcert-issuance-service.php` |
| `ppcert_mascot_url` | filter | 1 | `includes/admin/class-ppcert-admin-settings.php` |
| `ppcert_merge_data` | filter | 1 | `includes/services/class-ppcert-merge-field-registry.php` |
| `ppcert_my_certificates_row_actions` | filter | 1 | `includes/frontend/class-ppcert-my-certificates.php` |
| `ppcert_pdf_metadata` | filter | 1 | `includes/services/class-ppcert-pdf-renderer.php` |
| `ppcert_plugin_name` | filter | 3 | `includes/admin/class-ppcert-admin-dashboard.php` |
| `ppcert_privacy_policy_content` | filter | 1 | `includes/utilities/class-ppcert-privacy.php` |
| `ppcert_register_merge_fields` | filter | 1 | `includes/services/class-ppcert-merge-field-registry.php` |
| `ppcert_register_trigger_types` | filter | 1 | `includes/services/class-ppcert-trigger-registry.php` |
| `ppcert_rest_template_summaries` | filter | 1 | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert_sanitize_settings` | filter | 1 | `includes/api/class-ppcert-rest-settings-controller.php` |
| `ppcert_settings_tabs` | filter | 1 | `includes/admin/class-ppcert-admin-settings.php` |
| `ppcert_template_issuer_assignment_allowed` | filter | 1 | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert_template_list_columns` | filter | 1 | `includes/admin/class-ppcert-templates-list-table.php` |
| `ppcert_template_list_query_args` | filter | 3 | `includes/admin/class-ppcert-certificates-list-table.php` |
| `ppcert_template_list_row_actions` | filter | 1 | `includes/admin/class-ppcert-templates-list-table.php` |
| `ppcert_template_sanitize_settings` | filter | 1 | `includes/models/class-ppcert-template.php` |
| `ppcert_template_settings_validation` | filter | 1 | `includes/api/class-ppcert-rest-templates-controller.php` |
| `ppcert_upgrade_tiers` | filter | 1 | `includes/class-ppcert-addon-manager.php` |
| `ppcert_upsell_touchpoints` | filter | 1 | `includes/class-ppcert-addon-manager.php` |
| `ppcert_user_can_edit_template` | filter | 3 | `includes/admin/class-ppcert-admin.php` |
| `ppcert_user_can_issue_from_template` | filter | 1 | `includes/api/class-ppcert-rest-certificates-controller.php` |
| `ppcert_verification_actions` | filter | 1 | `includes/api/class-ppcert-rest-verification-controller.php` |
| `ppcert_verification_display` | filter | 1 | `includes/api/class-ppcert-rest-verification-controller.php` |
| `ppcert_verification_page_data` | filter | 1 | `includes/frontend/class-ppcert-verification-page.php` |
| `ppcert_verification_result` | filter | 1 | `includes/api/class-ppcert-rest-verification-controller.php` |
| `ppcert_view_page_actions` | filter | 1 | `includes/frontend/class-ppcert-view-page.php` |
