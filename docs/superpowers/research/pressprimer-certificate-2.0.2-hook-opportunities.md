# PressPrimer Certificate 2.0.2 — every hook annotated for Burbank

Every hook from the inventory, tagged with a Burbank opportunity bucket.

| Pri | Bucket | Hook | Kind | File |
| --- | --- | --- | --- | --- |
| 10 | ISSUE_GATE | `ppcert_before_issue` | action | `includes/services/class-ppcert-issuance-service.php` |
| 10 | ISSUE_GATE | `ppcert_certificate_issued` | action | `includes/services/class-ppcert-issuance-service.php` |
| 10 | ISSUE_GATE | `ppcert_issue_duplicate_ref` | filter | `includes/services/class-ppcert-issuance-service.php` |
| 10 | ISSUE_GATE | `ppcert_issue_validation` | filter | `includes/services/class-ppcert-issuance-service.php` |
| 10 | MERGE_FIELDS | `ppcert_merge_data` | filter | `includes/services/class-ppcert-merge-field-registry.php` |
| 10 | MERGE_FIELDS | `ppcert_register_merge_fields` | filter | `includes/services/class-ppcert-merge-field-registry.php` |
| 9 | ADAPTER | `ppcert_adapter_classes` | filter | `includes/class-ppcert-plugin.php` |
| 9 | ADAPTER | `ppcert_register_trigger_types` | filter | `includes/services/class-ppcert-trigger-registry.php` |
| 9 | LIFECYCLE | `ppcert_certificate_deleted` | action | `includes/models/class-ppcert-certificate.php` |
| 9 | LIFECYCLE | `ppcert_certificate_reinstated` | action | `includes/models/class-ppcert-certificate.php` |
| 9 | LIFECYCLE | `ppcert_certificate_revoked` | action | `includes/models/class-ppcert-certificate.php` |
| 9 | LIFECYCLE | `ppcert_certificate_verified` | action | `includes/api/class-ppcert-rest-verification-controller.php` |
| 8 | TEMPLATE_SYNC | `ppcert_template_created` | action | `includes/models/class-ppcert-template.php` |
| 8 | TEMPLATE_SYNC | `ppcert_template_duplicated` | action | `includes/models/class-ppcert-template.php` |
| 8 | TEMPLATE_SYNC | `ppcert_template_issuer_assignment_allowed` | filter | `includes/api/class-ppcert-rest-templates-controller.php` |
| 8 | TEMPLATE_SYNC | `ppcert_template_issuer_changed` | action | `includes/api/class-ppcert-rest-templates-controller.php` |
| 8 | TEMPLATE_SYNC | `ppcert_template_trashed` | action | `includes/models/class-ppcert-template.php` |
| 8 | TEMPLATE_SYNC | `ppcert_template_updated` | action | `includes/api/class-ppcert-rest-templates-controller.php` |
| 7 | VERIFY_UX | `ppcert_certificate_link_action` | filter | `includes/frontend/class-ppcert-certificate-link.php` |
| 7 | VERIFY_UX | `ppcert_certificate_link_embedded_sources` | filter | `includes/frontend/class-ppcert-certificate-link.php` |
| 7 | VERIFY_UX | `ppcert_my_certificates_row_actions` | filter | `includes/frontend/class-ppcert-my-certificates.php` |
| 7 | VERIFY_UX | `ppcert_verification_actions` | filter | `includes/api/class-ppcert-rest-verification-controller.php` |
| 7 | VERIFY_UX | `ppcert_verification_display` | filter | `includes/api/class-ppcert-rest-verification-controller.php` |
| 7 | VERIFY_UX | `ppcert_verification_page_data` | filter | `includes/frontend/class-ppcert-verification-page.php` |
| 7 | VERIFY_UX | `ppcert_verification_result` | filter | `includes/api/class-ppcert-rest-verification-controller.php` |
| 7 | VERIFY_UX | `ppcert_view_page_actions` | filter | `includes/frontend/class-ppcert-view-page.php` |
| 7 | VERIFY_UX | `ppcert_view_page_head` | action | `includes/frontend/class-ppcert-view-page.php` |
| 6 | ADMIN_UX | `ppcert_admin_branding` | filter | `includes/admin/class-ppcert-admin.php` |
| 6 | ADMIN_UX | `ppcert_admin_menu_registered` | action | `includes/admin/class-ppcert-admin.php` |
| 6 | ADMIN_UX | `ppcert_certificate_list_row_actions` | filter | `includes/admin/class-ppcert-certificates-list-table.php` |
| 6 | ADMIN_UX | `ppcert_dashboard_logo` | filter | `includes/admin/class-ppcert-admin-dashboard.php` |
| 6 | ADMIN_UX | `ppcert_dashboard_welcome_text` | filter | `includes/admin/class-ppcert-admin-dashboard.php` |
| 6 | ADMIN_UX | `ppcert_designer_element_types` | filter | `includes/services/class-ppcert-element-types.php` |
| 6 | ADMIN_UX | `ppcert_designer_enqueued` | action | `includes/admin/class-ppcert-admin.php` |
| 6 | ADMIN_UX | `ppcert_designer_fonts` | filter | `includes/services/class-ppcert-layout-validator.php` |
| 6 | ADMIN_UX | `ppcert_onboarding_completed` | action | `includes/admin/class-ppcert-onboarding.php` |
| 6 | ADMIN_UX | `ppcert_onboarding_reset` | action | `includes/admin/class-ppcert-onboarding.php` |
| 6 | ADMIN_UX | `ppcert_onboarding_skipped` | action | `includes/admin/class-ppcert-onboarding.php` |
| 6 | ADMIN_UX | `ppcert_onboarding_started` | action | `includes/admin/class-ppcert-onboarding.php` |
| 6 | ADMIN_UX | `ppcert_settings_saved` | action | `includes/api/class-ppcert-rest-settings-controller.php` |
| 6 | ADMIN_UX | `ppcert_settings_tabs` | filter | `includes/admin/class-ppcert-admin-settings.php` |
| 6 | ADMIN_UX | `ppcert_template_list_column` | action | `includes/admin/class-ppcert-templates-list-table.php` |
| 6 | ADMIN_UX | `ppcert_template_list_columns` | filter | `includes/admin/class-ppcert-templates-list-table.php` |
| 6 | ADMIN_UX | `ppcert_template_list_filters` | action | `includes/admin/class-ppcert-templates-list-table.php` |
| 6 | ADMIN_UX | `ppcert_template_list_query_args` | filter | `includes/admin/class-ppcert-certificates-list-table.php` |
| 6 | ADMIN_UX | `ppcert_template_list_row_actions` | filter | `includes/admin/class-ppcert-templates-list-table.php` |
| 6 | ADMIN_UX | `ppcert_template_settings_validation` | filter | `includes/api/class-ppcert-rest-templates-controller.php` |
| 5 | EMAIL | `ppcert_email_content` | filter | `includes/services/class-ppcert-email-service.php` |
| 5 | EMAIL | `ppcert_email_enabled` | filter | `includes/services/class-ppcert-email-service.php` |
| 5 | EMAIL | `ppcert_email_footer` | filter | `includes/services/class-ppcert-email-service.php` |
| 5 | EMAIL | `ppcert_email_from` | filter | `includes/services/class-ppcert-email-service.php` |
| 5 | EMAIL | `ppcert_email_intake_url` | filter | `includes/services/class-ppcert-email-optin-service.php` |
| 5 | EMAIL | `ppcert_email_optin_submitted` | action | `includes/api/class-ppcert-rest-email-optin-controller.php` |
| 4 | ACCESS | `ppcert_certificate_scope` | filter | `includes/models/class-ppcert-certificate.php` |
| 4 | ACCESS | `ppcert_user_can_edit_template` | filter | `includes/admin/class-ppcert-admin.php` |
| 4 | ACCESS | `ppcert_user_can_issue_from_template` | filter | `includes/api/class-ppcert-rest-certificates-controller.php` |
| 2 | BRANDING | `ppcert_mascot_url` | filter | `includes/admin/class-ppcert-admin-settings.php` |
| 2 | BRANDING | `ppcert_plugin_name` | filter | `includes/admin/class-ppcert-admin-dashboard.php` |
| 2 | OTHER | `ppcert_issue_templates` | filter | `includes/admin/class-ppcert-admin-certificates.php` |
| 2 | OTHER | `ppcert_loaded` | action | `includes/class-ppcert-plugin.php` |
| 2 | OTHER | `ppcert_pdf_generated` | action | `includes/services/class-ppcert-pdf-renderer.php` |
| 2 | OTHER | `ppcert_pdf_metadata` | filter | `includes/services/class-ppcert-pdf-renderer.php` |
| 2 | OTHER | `ppcert_preview_render_failed` | action | `includes/services/class-ppcert-preview-service.php` |
| 2 | OTHER | `ppcert_privacy_policy_content` | filter | `includes/utilities/class-ppcert-privacy.php` |
| 2 | OTHER | `ppcert_rest_template_summaries` | filter | `includes/api/class-ppcert-rest-templates-controller.php` |
| 2 | OTHER | `ppcert_sanitize_settings` | filter | `includes/api/class-ppcert-rest-settings-controller.php` |
| 2 | OTHER | `ppcert_template_sanitize_settings` | filter | `includes/models/class-ppcert-template.php` |
| 1 | UPSELL | `ppcert_upgrade_tiers` | filter | `includes/class-ppcert-addon-manager.php` |
| 1 | UPSELL | `ppcert_upsell_touchpoints` | filter | `includes/class-ppcert-addon-manager.php` |

## Bucket counts

- **ADMIN_UX**: 20
- **VERIFY_UX**: 9
- **OTHER**: 9
- **TEMPLATE_SYNC**: 6
- **EMAIL**: 6
- **ISSUE_GATE**: 4
- **LIFECYCLE**: 4
- **ACCESS**: 3
- **MERGE_FIELDS**: 2
- **ADAPTER**: 2
- **BRANDING**: 2
- **UPSELL**: 2
