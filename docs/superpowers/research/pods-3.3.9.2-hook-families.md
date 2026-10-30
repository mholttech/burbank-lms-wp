# Pods 3.3.9.2 — every hook by family (Burbank notes)

Total hooks: 443. Grouped by name prefix; families most relevant to PressPrimer integration are marked.

## `pods_form_ui` (76)

_Field UI customization including pick related objects and DFV_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_form_ui_comment_text` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_code` | action | `classes/fields/code.php` |
| `pods_form_ui_field_code_{$field_type}` | action | `classes/fields/code.php` |
| `pods_form_ui_field_currency_currencies` | filter | `classes/fields/currency.php` |
| `pods_form_ui_field_date_format_default` | filter | `classes/fields/date.php` |
| `pods_form_ui_field_date_format_options` | filter | `classes/fields/date.php` |
| `pods_form_ui_field_datetime_custom_formatter` | filter | `classes/fields/date.php` |
| `pods_form_ui_field_datetime_formatter` | filter | `classes/fields/datetime.php` |
| `pods_form_ui_field_db` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_file_mime_types_for_media_type` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_hidden` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_html5` | filter | `classes/fields/currency.php` |
| `pods_form_ui_field_link_new_window` | filter | `classes/fields/link.php` |
| `pods_form_ui_field_number_currency_default` | filter | `classes/fields/currency.php` |
| `pods_form_ui_field_number_currency_format_default` | filter | `classes/fields/currency.php` |
| `pods_form_ui_field_number_currency_options` | filter | `classes/fields/currency.php` |
| `pods_form_ui_field_number_currency_placement_default` | filter | `classes/fields/currency.php` |
| `pods_form_ui_field_number_format_default` | filter | `classes/fields/number.php` |
| `pods_form_ui_field_paragraph_display_value` | filter | `classes/fields/paragraph.php` |
| `pods_form_ui_field_paragraph_display_value_pre_process` | filter | `classes/fields/paragraph.php` |
| `pods_form_ui_field_pick_ajax` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_autocomplete_limit` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_autocomplete_lookup` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_bidirectional_objects` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_custom_values` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_ca_provinces` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_capabilities` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_countries` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_image_sizes` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_pick_data_post_stati` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_post_stati_with_any` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_post_types` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_roles` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_taxonomies` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_data_us_states` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_format_multi_options` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_format_single_options` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_related_objects` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_related_objects_custom` | action | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_related_objects_new_data_loaded` | action | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_related_objects_other` | action | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_related_objects_predefined` | action | `classes/fields/pick.php` |
| `pods_form_ui_field_pick_simple_objects` | filter | `classes/fields/pick.php` |
| `pods_form_ui_field_row` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_time_format_24_default` | filter | `classes/fields/datetime.php` |
| `pods_form_ui_field_time_format_24_options` | filter | `classes/fields/datetime.php` |
| `pods_form_ui_field_time_format_default` | filter | `classes/fields/datetime.php` |
| `pods_form_ui_field_time_format_options` | filter | `classes/fields/datetime.php` |
| `pods_form_ui_field_time_format_type_default` | filter | `classes/fields/datetime.php` |
| `pods_form_ui_field_website_clickable` | filter | `classes/fields/website.php` |
| `pods_form_ui_field_website_new_window` | filter | `classes/fields/website.php` |
| `pods_form_ui_field_website_nofollow` | filter | `classes/fields/website.php` |
| `pods_form_ui_field_website_port` | filter | `classes/fields/website.php` |
| `pods_form_ui_field_wysiwyg` | action | `classes/fields/wysiwyg.php` |
| `pods_form_ui_field_wysiwyg_display_value` | filter | `classes/fields/wysiwyg.php` |
| `pods_form_ui_field_wysiwyg_display_value_pre_process` | filter | `classes/fields/wysiwyg.php` |
| `pods_form_ui_field_wysiwyg_editors` | filter | `classes/fields/wysiwyg.php` |
| `pods_form_ui_field_wysiwyg_{$editor_type}` | action | `classes/fields/wysiwyg.php` |
| `pods_form_ui_field_{$type}` | action,filter | `classes/PodsForm.php` |
| `pods_form_ui_field_{$type}_attributes` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_{$type}_data` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_{$type}_extensions_default` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_merge_attributes` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_{$type}_options` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_{$type}_override` | filter | `classes/PodsForm.php` |
| `pods_form_ui_field_{$type}_template_default` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_type_default` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_type_options` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_type_templates` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_ui_image_size` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_upload_dir_custom` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_uploader_options` | filter | `classes/fields/file.php` |
| `pods_form_ui_field_{$type}_value` | filter | `classes/PodsForm.php` |
| `pods_form_ui_label_help` | filter | `classes/PodsForm.php` |
| `pods_form_ui_label_text` | filter | `classes/PodsForm.php` |
| `pods_form_ui_{$type}` | filter | `classes/PodsForm.php` |

## `pods_api_save` (20) **← high relevance**

_Save pipeline; complement existing field_validation / post_save_pod_item_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_save_field_definition` | filter | `classes/PodsAPI.php` |
| `pods_api_save_field_old_definition` | filter | `classes/PodsAPI.php` |
| `pods_api_save_field_table_altered` | action | `classes/PodsAPI.php` |
| `pods_api_save_field_table_definition_mode` | filter | `classes/PodsAPI.php` |
| `pods_api_save_field_table_pre_alter` | action | `classes/PodsAPI.php` |
| `pods_api_save_pod_config_data` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_handle_rename_after` | action | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_conditional_logic_field_is_visible` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_exclude_conditionally_hidden_fields` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_processed_data_to_save` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_save_to_table` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_strict_meta_save` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_taggable_data` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_item_track_changed_fields_{$pod_name}` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_meta_data` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_post_data` | filter | `classes/PodsAPI.php` |
| `pods_api_save_pod_table_schema_after` | action | `classes/PodsAPI.php` |
| `pods_api_save_relationships` | action | `classes/PodsAPI.php` |
| `pods_api_save_wp_object_for_custom_object_type` | filter | `classes/PodsAPI.php` |
| `pods_api_save_wp_object_use_meta_fallback` | filter | `classes/PodsAPI.php` |

## `pods_meta_meta` (19)

_Meta box rendering around course fields_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_meta_comment` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_comment_post` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_comment_post_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_comment_pre_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_media` | filter | `classes/PodsMeta.php` |
| `pods_meta_meta_post` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_post_post` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_post_post_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_post_pre_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_post_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_post_{$field_name}_post` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_taxonomy` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_taxonomy_post` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_taxonomy_post_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_taxonomy_pre_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_user` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_user_post` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_user_post_row_{$field_name}` | action | `classes/PodsMeta.php` |
| `pods_meta_meta_user_pre_row_{$field_name}` | action | `classes/PodsMeta.php` |

## `pods_meta_save` (16) **← high relevance**

_Post meta save lifecycle for bfc_course_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_save_comment` | action | `classes/PodsMeta.php` |
| `pods_meta_save_media` | action | `classes/PodsMeta.php` |
| `pods_meta_save_post` | action | `classes/PodsMeta.php` |
| `pods_meta_save_post_blacklist_status` | filter | `classes/PodsMeta.php` |
| `pods_meta_save_post_blacklist_types` | filter | `classes/PodsMeta.php` |
| `pods_meta_save_post_{$post->post_type}` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_comment` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_media` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_post` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_post_{$post->post_type}` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_taxonomy` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_taxonomy_{$taxonomy}` | action | `classes/PodsMeta.php` |
| `pods_meta_save_pre_user` | action | `classes/PodsMeta.php` |
| `pods_meta_save_taxonomy` | action | `classes/PodsMeta.php` |
| `pods_meta_save_taxonomy_{$taxonomy}` | action | `classes/PodsMeta.php` |
| `pods_meta_save_user` | action | `classes/PodsMeta.php` |

## `pods_meta_create` (10)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_create_post` | action | `classes/PodsMeta.php` |
| `pods_meta_create_post_{$post->post_type}` | action | `classes/PodsMeta.php` |
| `pods_meta_create_pre_post` | action | `classes/PodsMeta.php` |
| `pods_meta_create_pre_post_{$post->post_type}` | action | `classes/PodsMeta.php` |
| `pods_meta_create_pre_taxonomy` | action | `classes/PodsMeta.php` |
| `pods_meta_create_pre_taxonomy_{$taxonomy}` | action | `classes/PodsMeta.php` |
| `pods_meta_create_pre_user` | action | `classes/PodsMeta.php` |
| `pods_meta_create_taxonomy` | action | `classes/PodsMeta.php` |
| `pods_meta_create_taxonomy_{$taxonomy}` | action | `classes/PodsMeta.php` |
| `pods_meta_create_user` | action | `classes/PodsMeta.php` |

## `pods_field_pick` (9) **← high relevance**

_CRITICAL for quiz/template pickers via register_related_object / pick data filters_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_pick_always_show_default_select_text` | filter | `classes/fields/pick.php` |
| `pods_field_pick_data` | filter | `classes/fields/pick.php` |
| `pods_field_pick_data_ajax` | filter | `classes/fields/pick.php` |
| `pods_field_pick_data_ajax_items` | filter | `classes/fields/pick.php` |
| `pods_field_pick_object_data` | filter | `classes/fields/pick.php` |
| `pods_field_pick_object_data_params` | filter | `classes/fields/pick.php` |
| `pods_field_pick_save_sync_taxonomy_term_ids` | filter | `classes/fields/pick.php` |
| `pods_field_pick_setup_related_objects_pods` | filter | `classes/fields/pick.php` |
| `pods_field_pick_value_to_label` | filter | `classes/fields/pick.php` |

## `pods_meta_delete` (9)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_delete_comment_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_comment_meta_by_id` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_option` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_post_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_post_meta_by_id` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_term_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_term_meta_by_id` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_user_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_delete_user_meta_by_id` | filter | `classes/PodsMeta.php` |

## `pods_meta_update` (9)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_update_comment_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_comment_meta_by_id` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_option` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_post_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_post_meta_by_id` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_term_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_term_meta_by_id` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_user_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_update_user_meta_by_id` | filter | `classes/PodsMeta.php` |

## `pods_admin_ui` (8)

_Admin UI overrides for bfc_course_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_ui` | filter | `classes/Pods.php` |
| `pods_admin_ui_custom` | action | `classes/Pods.php` |
| `pods_admin_ui_custom_{$pod_name}` | action | `classes/Pods.php` |
| `pods_admin_ui_custom_{$pod_pod_name}` | action | `classes/PodsAdmin.php` |
| `pods_admin_ui_fields` | filter | `classes/Pods.php` |
| `pods_admin_ui_fields_{$pod_name}` | filter | `classes/Pods.php` |
| `pods_admin_ui_{$pod_name}` | filter | `classes/Pods.php` |
| `pods_admin_ui_{$pod_pod_name}` | filter | `classes/PodsAdmin.php` |

## `pods_field_{$type}` (5)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_{$type}_options` | filter | `classes/PodsForm.php` |
| `pods_field_{$type}_prepare` | filter | `classes/PodsForm.php` |
| `pods_field_{$type}_regex` | filter | `classes/PodsForm.php` |
| `pods_field_{$type}_ui_options` | filter | `classes/PodsForm.php` |
| `pods_field_{$type}_validate` | filter | `classes/PodsForm.php` |

## `pods_meta_add` (5)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_add_comment_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_add_option` | filter | `classes/PodsMeta.php` |
| `pods_meta_add_post_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_add_term_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_add_user_meta` | filter | `classes/PodsMeta.php` |

## `pods_meta_get` (5)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_get_comment_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_get_option` | filter | `classes/PodsMeta.php` |
| `pods_meta_get_post_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_get_term_meta` | filter | `classes/PodsMeta.php` |
| `pods_meta_get_user_meta` | filter | `classes/PodsMeta.php` |

## `pods_access_sql` (4) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_sql_fragment_disallow_unsafe_functions` | filter | `includes/access.php` |
| `pods_access_sql_fragment_disallow_unsafe_keywords` | filter | `includes/access.php` |
| `pods_access_sql_fragment_disallow_unsafe_tables` | filter | `includes/access.php` |
| `pods_access_sql_fragment_is_allowed` | filter | `includes/access.php` |

## `pods_admin_menu` (4)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_menu` | filter | `classes/PodsAdmin.php` |
| `pods_admin_menu_label` | filter | `classes/PodsAdmin.php` |
| `pods_admin_menu_page_title` | filter | `classes/PodsAdmin.php` |
| `pods_admin_menu_secondary_content` | filter | `classes/PodsAdmin.php` |

## `pods_data_traverse` (4)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_traverse` | filter | `classes/PodsData.php` |
| `pods_data_traverse_recurse_handle_join` | filter | `classes/PodsData.php` |
| `pods_data_traverse_recurse_ignore_aliases` | filter | `classes/PodsData.php` |
| `pods_data_traverse_the_join` | filter | `classes/PodsData.php` |

## `pods_pods_field` (4)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_field` | filter | `classes/Pods.php` |
| `pods_pods_field_get_metadata_object_id` | filter | `classes/Pods.php` |
| `pods_pods_field_related_output_type` | filter | `classes/Pods.php` |
| `pods_pods_field_{$field_type}` | filter | `classes/Pods.php` |

## `pods_rest_api` (4) **← high relevance**

_REST shape for relationships if quiz became a related object_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_rest_api_depth_for_relationship_response` | filter | `classes/PodsRESTHandlers.php` |
| `pods_rest_api_fields_for_relationship_response` | filter | `classes/PodsRESTHandlers.php` |
| `pods_rest_api_output_type_for_relationship_response` | filter | `classes/PodsRESTHandlers.php` |
| `pods_rest_api_pod_name` | filter | `classes/PodsRESTHandlers.php` |

## `pods_serial_comma` (4)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_serial_comma` | filter | `includes/data.php` |
| `pods_serial_comma_and` | filter | `includes/data.php` |
| `pods_serial_comma_separator` | filter | `includes/data.php` |
| `pods_serial_comma_value` | filter | `includes/data.php` |

## `pods_ui_manage` (4)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_ui_manage_after_container` | action | `classes/PodsUI.php` |
| `pods_ui_manage_before_container` | action | `classes/PodsUI.php` |
| `pods_ui_manage_before_filters` | action | `classes/PodsUI.php` |
| `pods_ui_manage_custom_container_classes` | filter | `classes/PodsUI.php` |

## `pods_access_can` (3) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_can_use_dynamic_feature` | filter | `includes/access.php` |
| `pods_access_can_use_dynamic_features` | filter | `includes/access.php` |
| `pods_access_can_use_dynamic_features_unrestricted` | filter | `includes/access.php` |

## `pods_admin_ajax` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_ajax_actions` | filter | `classes/PodsAdmin.php` |
| `pods_admin_ajax_methods` | filter | `classes/PodsAdmin.php` |
| `pods_admin_ajax_success_{$method->name}` | action | `classes/PodsAdmin.php` |

## `pods_admin_setup` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_setup_edit_pod_config` | filter | `classes/PodsAdmin.php` |
| `pods_admin_setup_extend_pods_internal` | filter | `classes/PodsAdmin.php` |
| `pods_admin_setup_global_config` | filter | `classes/PodsAdmin.php` |

## `pods_data_get` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_get_db_field` | filter | `classes/PodsData.php` |
| `pods_data_get_db_field_use_pod_meta_storage_for_field` | filter | `classes/PodsData.php` |
| `pods_data_get_sql` | filter | `classes/PodsData.php` |

## `pods_form_field` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_form_field_default_value` | filter | `classes/PodsForm.php` |
| `pods_form_field_include` | filter | `classes/PodsForm.php` |
| `pods_form_field_permission` | filter | `classes/PodsForm.php` |

## `pods_internal_register` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_internal_register_post_type_field` | filter | `classes/PodsInit.php` |
| `pods_internal_register_post_type_group` | filter | `classes/PodsInit.php` |
| `pods_internal_register_post_type_pod` | filter | `classes/PodsInit.php` |

## `pods_meta_group` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_group_add` | filter | `classes/PodsMeta.php` |
| `pods_meta_group_add_{$pod_type}` | filter | `classes/PodsMeta.php` |
| `pods_meta_group_add_{$pod_type}_{$object_name}` | filter | `classes/PodsMeta.php` |

## `pods_ui_dfv` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_ui_dfv_pick_modals_allow_nested` | filter | `classes/fields/pick.php` |
| `pods_ui_dfv_pick_modals_iframe` | filter | `classes/fields/pick.php` |
| `pods_ui_dfv_pick_modals_show_on_front` | filter | `classes/fields/pick.php` |

## `pods_user_can` (3)

_Object-level access checks_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_user_can_access_object` | filter | `includes/access.php` |
| `pods_user_can_access_object_get_capabilities` | filter | `includes/access.php` |
| `pods_user_can_access_object_pre_check` | filter | `includes/access.php` |

## `pods_view_cache` (3)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_cache_alt_get` | filter | `classes/PodsView.php` |
| `pods_view_cache_alt_get_value` | filter | `classes/PodsView.php` |
| `pods_view_cache_alt_set` | filter | `classes/PodsView.php` |

## `pods_access_bypass` (2) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_bypass_post_with_password` | filter | `includes/access.php` |
| `pods_access_bypass_private_post` | filter | `includes/access.php` |

## `pods_advanced_content` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_advanced_content_type_pod_data` | filter | `classes/PodsAdmin.php` |
| `pods_advanced_content_type_pod_data_{$pod_name}` | filter | `classes/PodsAdmin.php` |

## `pods_api_delete` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_delete_object_from_relationships` | action | `classes/PodsAPI.php` |
| `pods_api_delete_relationships` | action | `classes/PodsAPI.php` |

## `pods_api_export` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_export_pod_item_level_export_as_json` | filter | `classes/PodsAPI.php` |
| `pods_api_export_pod_item_level_export_ids_at_final_depth` | filter | `classes/PodsAPI.php` |

## `pods_api_get` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_get_table_info` | filter | `classes/PodsAPI.php` |
| `pods_api_get_table_info_default_post_status` | filter | `classes/PodsAPI.php` |

## `pods_api_lookup` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_lookup_related_items_bidirectional_fallback` | filter | `classes/PodsAPI.php` |
| `pods_api_lookup_related_items_related_ids_for_id` | filter | `classes/PodsAPI.php` |

## `pods_data_fetch` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_fetch` | filter | `classes/PodsData.php` |
| `pods_data_fetch_full` | filter | `classes/PodsData.php` |

## `pods_data_query` (2)

_Query customization_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_query` | filter | `classes/PodsData.php` |
| `pods_data_query_result` | filter | `classes/PodsData.php` |

## `pods_error_exception` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_error_exception` | filter | `includes/general.php` |
| `pods_error_exception_fallback_enabled` | filter | `includes/general.php` |

## `pods_form_render` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_form_render_fields_group_post` | action | `includes/forms.php` |
| `pods_form_render_fields_group_pre` | action | `includes/forms.php` |

## `pods_get_current` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_get_current_language` | filter | `classes/PodsI18n.php` |
| `pods_get_current_language_context` | filter | `classes/PodsI18n.php` |

## `pods_meta_groups` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_groups` | action | `classes/PodsMeta.php` |
| `pods_meta_groups_get` | filter | `classes/PodsMeta.php` |

## `pods_meta_handler` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_handler` | filter | `includes/general.php` |
| `pods_meta_handler_get` | filter | `includes/general.php` |

## `pods_pods_total` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_total` | action | `classes/Pods.php` |
| `pods_pods_total_found` | action | `classes/Pods.php` |

## `pods_register_post` (2)

_Post type registration filters (we use Pods API instead)_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_register_post_type` | filter | `classes/PodsInit.php` |
| `pods_register_post_type_{$post_type}` | filter | `classes/PodsInit.php` |

## `pods_register_taxonomy` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_register_taxonomy` | filter | `classes/PodsInit.php` |
| `pods_register_taxonomy_{$taxonomy}` | filter | `classes/PodsInit.php` |

## `pods_relationship_meta` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_relationship_meta_storage_enabled` | filter | `includes/general.php` |
| `pods_relationship_meta_storage_enabled_for_simple_relationships` | filter | `includes/general.php` |

## `pods_relationship_table` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_relationship_table_storage_enabled_for_object_relationships` | filter | `includes/general.php` |
| `pods_relationship_table_storage_enabled_for_simple_relationships` | filter | `includes/general.php` |

## `pods_stats_tracking` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_stats_tracking_after_init` | action | `classes/PodsInit.php` |
| `pods_stats_tracking_object` | action | `classes/PodsInit.php` |

## `pods_templates_post` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_templates_post_template` | filter | `classes/Pods.php` |
| `pods_templates_post_template_{$template_name}` | filter | `classes/Pods.php` |

## `pods_templates_pre` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_templates_pre_template` | filter | `classes/Pods.php` |
| `pods_templates_pre_template_{$template_name}` | filter | `classes/Pods.php` |

## `pods_term_splitting` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_term_splitting_update_related_term` | action | `classes/PodsTermSplitting.php` |
| `pods_term_splitting_update_taxonomy` | action | `classes/PodsTermSplitting.php` |

## `pods_ui_get` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_ui_get_find_params` | filter | `classes/PodsUI.php` |
| `pods_ui_get_params` | filter | `classes/PodsUI.php` |

## `pods_unique_slug` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_unique_slug` | filter | `includes/data.php` |
| `pods_unique_slug_is_bad_flat_slug` | filter | `includes/data.php` |

## `pods_upload_dir` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_upload_dir_custom` | filter | `classes/fields/file.php` |
| `pods_upload_dir_custom_context_pod` | filter | `classes/fields/file.php` |

## `pods_view_output` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_output` | filter | `classes/PodsView.php` |
| `pods_view_output_{$cache_key}` | filter | `classes/PodsView.php` |

## `pods_wp_supported` (2)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_wp_supported_post_types` | filter | `classes/PodsInit.php` |
| `pods_wp_supported_taxonomies` | filter | `classes/PodsInit.php` |

## `pods_access` (1) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access` | filter | `includes/general.php` |

## `pods_access_allow` (1) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_allow_class_callbacks` | filter | `includes/access.php` |

## `pods_access_bleep` (1) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_bleep_properties` | filter | `includes/access.php` |

## `pods_access_callbacks` (1) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_callbacks_custom_prefix` | filter | `includes/access.php` |

## `pods_access_map` (1) **← high relevance**

_Capability/access wrappers_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_access_map_capabilities` | filter | `includes/access.php` |

## `pods_acf_backwards` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_acf_backwards_compatibility` | filter | `includes/compatibility/acf.php` |

## `pods_admin_callouts` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_callouts` | filter | `classes/PodsAdmin.php` |

## `pods_admin_capabilities` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_capabilities` | filter | `includes/general.php` |

## `pods_admin_components` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_components_menu` | filter | `classes/PodsComponents.php` |

## `pods_admin_dfv` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_dfv_config` | filter | `classes/PodsInit.php` |

## `pods_admin_media` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_media_button` | filter | `classes/PodsAdmin.php` |

## `pods_admin_settings` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_admin_settings_init` | action | `classes/PodsAdmin.php` |

## `pods_after_enqueue` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_after_enqueue_scripts` | action | `classes/PodsInit.php` |

## `pods_api_cache` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_cache` | filter | `includes/general.php` |

## `pods_api_duplicate` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_duplicate_pod_item_ignore_fields` | filter | `classes/PodsAPI.php` |

## `pods_api_field` (1)

_Field type registration / validation extensions_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_field_types` | filter | `classes/PodsForm.php` |

## `pods_api_handle` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_handle_field_validation_check_required` | filter | `classes/PodsAPI.php` |

## `pods_api_migrate` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_migrate_run` | filter | `classes/PodsAdmin.php` |

## `pods_api_object` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_object_storage_type` | filter | `classes/PodsAPI.php` |

## `pods_api_pod` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_pod_types` | filter | `classes/PodsAPI.php` |

## `pods_api_process` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_process_form_restrict_content` | filter | `classes/PodsAPI.php` |

## `pods_api_processed` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_processed_form` | action | `classes/PodsAPI.php` |

## `pods_api_storage` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_storage_types` | filter | `classes/PodsAPI.php` |

## `pods_api_{$method` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_api_{$method_name}` | filter | `classes/PodsAdmin.php` |

## `pods_before_enqueue` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_before_enqueue_scripts` | action | `classes/PodsInit.php` |

## `pods_cache_flushed` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_cache_flushed` | action | `classes/PodsAPI.php` |

## `pods_component_ajax` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_component_ajax_{$component}_{$method}` | filter | `classes/PodsComponents.php` |

## `pods_component_require` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_component_require_{$component_id}` | filter | `classes/PodsComponents.php` |

## `pods_components_dir` (1)

_Optional Pods components (not required)_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_components_dir` | filter | `classes/PodsComponents.php` |

## `pods_components_get` (1)

_Optional Pods components (not required)_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_components_get` | action | `classes/PodsComponents.php` |

## `pods_components_load` (1)

_Optional Pods components (not required)_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_components_load` | action | `classes/PodsComponents.php` |

## `pods_components_register` (1)

_Optional Pods components (not required)_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_components_register` | filter | `classes/PodsComponents.php` |

## `pods_create_slug` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_create_slug` | filter | `includes/data.php` |

## `pods_current_url` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_current_url` | filter | `includes/general.php` |

## `pods_data_auto` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_auto_calculate_total_found` | filter | `classes/PodsData.php` |

## `pods_data_build` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_build_pre_traverse_args` | filter | `classes/PodsData.php` |

## `pods_data_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_field_query` | filter | `classes/PodsData.php` |

## `pods_data_pre` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_pre_select_params` | filter | `classes/PodsData.php` |

## `pods_data_prepare` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_prepare` | filter | `classes/PodsData.php` |

## `pods_data_select` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_data_select` | filter | `classes/PodsData.php` |

## `pods_debug_log` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_debug_log_data` | action | `includes/general.php` |

## `pods_delete_attachment` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_delete_attachment` | action | `classes/PodsInit.php` |

## `pods_do_magic` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_do_magic_tags` | filter | `classes/Pods.php` |

## `pods_error_die` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_error_die` | filter | `includes/general.php` |

## `pods_error_mode` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_error_mode` | filter | `includes/general.php` |

## `pods_eval_show` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_eval_show_errors` | filter | `includes/general.php` |

## `pods_evaluate_tag` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_evaluate_tag` | filter | `includes/data.php` |

## `pods_evaluate_tags` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_evaluate_tags_sql` | filter | `includes/data.php` |

## `pods_field_dfv` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_dfv_data` | filter | `classes/PodsField.php` |

## `pods_field_maybe` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_maybe_sanitize_output` | filter | `classes/PodsField.php` |

## `pods_field_oembed` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_oembed_preview_capability` | filter | `classes/fields/oembed.php` |

## `pods_field_validate` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_validate_` | filter | `classes/PodsField.php` |

## `pods_field_wp` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_field_wp_object_use_meta_fallback` | filter | `classes/Pods.php` |

## `pods_file_directory` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_file_directory` | filter | `includes/general.php` |

## `pods_file_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_file_field_types` | filter | `classes/PodsForm.php` |

## `pods_filters_params` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_filters_params` | filter | `classes/Pods.php` |

## `pods_form_display` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_form_display_{$type}` | filter | `classes/PodsForm.php` |

## `pods_form_revisionable` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_form_revisionable_field_types` | filter | `classes/PodsForm.php` |

## `pods_function_or` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_function_or_file` | filter | `includes/general.php` |

## `pods_helper_allow` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_helper_allow_callbacks` | filter | `includes/access.php` |

## `pods_helper_allowed` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_helper_allowed_callbacks` | filter | `includes/access.php` |

## `pods_helper_include` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_helper_include_obj` | filter | `classes/Pods.php` |

## `pods_image_default` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_image_default` | filter | `includes/media.php` |

## `pods_image_url` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_image_url_default` | filter | `includes/media.php` |

## `pods_init` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_init` | action | `classes/PodsInit.php` |

## `pods_init_delete` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_init_delete_attachment` | action | `classes/PodsInit.php` |

## `pods_init_register` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_init_register_assets_load_pods_dfv_on_front` | filter | `classes/PodsInit.php` |

## `pods_is_admin` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_is_admin` | filter | `includes/general.php` |

## `pods_is_debug` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_is_debug_logging_enabled` | filter | `includes/general.php` |

## `pods_is_type` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_is_type_public` | filter | `includes/access.php` |

## `pods_is_types` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_is_types_only` | filter | `includes/general.php` |

## `pods_is_user` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_is_user_admin` | filter | `includes/general.php` |

## `pods_layout_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_layout_field_types` | filter | `classes/PodsForm.php` |

## `pods_localized_strings` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_localized_strings` | filter | `classes/PodsI18n.php` |

## `pods_meta_cover` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_cover_object_fields_in_meta` | filter | `classes/PodsMeta.php` |

## `pods_meta_default` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_default_box_title` | filter | `classes/PodsAPI.php` |

## `pods_meta_hook` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_hook_list` | filter | `includes/general.php` |

## `pods_meta_ignored` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_ignored_types` | filter | `classes/PodsMeta.php` |

## `pods_meta_init` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_init` | action | `classes/PodsMeta.php` |

## `pods_meta_key` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_key_is_covered` | filter | `classes/PodsMeta.php` |

## `pods_meta_keys` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_keys_not_covered` | filter | `classes/PodsMeta.php` |

## `pods_meta_post` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_post_fields` | filter | `classes/PodsMeta.php` |

## `pods_meta_reserved` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_reserved_post_types` | filter | `classes/PodsMeta.php` |

## `pods_meta_user` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_meta_user_show_groups_on_bbpress_profile` | filter | `classes/PodsMeta.php` |

## `pods_non_input` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_non_input_field_types` | filter | `classes/PodsForm.php` |

## `pods_pick_ignore` (1) **← high relevance**

_CRITICAL for quiz/template pickers via register_related_object / pick data filters_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pick_ignore_internal` | filter | `classes/fields/pick.php` |

## `pods_pick_select2` (1) **← high relevance**

_CRITICAL for quiz/template pickers via register_related_object / pick data filters_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pick_select2_overrides` | filter | `classes/fields/pick.php` |

## `pods_pod_form` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pod_form_success_message` | filter | `classes/Pods.php` |

## `pods_pods_data` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_data` | action | `classes/Pods.php` |

## `pods_pods_fetch` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_fetch` | action | `classes/Pods.php` |

## `pods_pods_fields` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_fields` | filter | `classes/Pods.php` |

## `pods_pods_filters` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_filters` | filter | `classes/Pods.php` |

## `pods_pods_find` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_find` | filter | `classes/Pods.php` |

## `pods_pods_reset` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_reset` | action | `classes/Pods.php` |

## `pods_pods_row` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pods_row` | action | `classes/Pods.php` |

## `pods_podsrel_enabled` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_podsrel_enabled` | filter | `includes/general.php` |

## `pods_pre_do` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_pre_do_magic_tags` | filter | `classes/Pods.php` |

## `pods_query_sql` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_query_sql` | filter | `includes/general.php` |

## `pods_render_is` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_render_is_in_block` | filter | `includes/general.php` |

## `pods_repeatable_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_repeatable_field_types` | filter | `classes/PodsForm.php` |

## `pods_reserved_keywords` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_reserved_keywords` | filter | `includes/general.php` |

## `pods_roles_get` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_roles_get_capabilities` | filter | `classes/fields/pick.php` |

## `pods_script_register` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_script_register_handlebars` | filter | `classes/PodsInit.php` |

## `pods_separator_excluded` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_separator_excluded_field_types` | filter | `classes/PodsForm.php` |

## `pods_session_id` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_session_id` | filter | `includes/general.php` |

## `pods_setup_content` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_setup_content_types` | action | `classes/PodsInit.php` |

## `pods_shortcode` (1) **← high relevance**

_Front-end course embeds_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_shortcode` | filter | `includes/general.php` |

## `pods_shortcode_detect` (1) **← high relevance**

_Front-end course embeds_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_shortcode_detect_from_current_post` | filter | `includes/general.php` |

## `pods_shortcode_findrecords` (1) **← high relevance**

_Front-end course embeds_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_shortcode_findrecords_params` | filter | `includes/general.php` |

## `pods_shortcode_output` (1) **← high relevance**

_Front-end course embeds_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_shortcode_output` | filter | `includes/general.php` |

## `pods_shortcode_throw` (1) **← high relevance**

_Front-end course embeds_

| Hook | Kind | File |
| --- | --- | --- |
| `pods_shortcode_throw_errors` | filter | `includes/general.php` |

## `pods_strict_mode` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_strict_mode` | filter | `includes/general.php` |

## `pods_svg_icon` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_svg_icon` | filter | `includes/general.php` |

## `pods_tableless_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_tableless_field_types` | filter | `classes/PodsForm.php` |

## `pods_template_content` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_template_content` | filter | `classes/Pods.php` |

## `pods_template_default` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_template_default_templates` | filter | `classes/Pods.php` |

## `pods_templates_do` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_templates_do_template` | filter | `classes/Pods.php` |

## `pods_text_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_text_field_types` | filter | `classes/PodsForm.php` |

## `pods_ui_after` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_ui_after_heading` | action | `classes/PodsUI.php` |

## `pods_ui_field` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_ui_field_display_value` | filter | `classes/PodsUI.php` |

## `pods_ui_save` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_ui_save_nonce_is_valid` | filter | `classes/PodsUI.php` |

## `pods_update` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_update` | action | `classes/PodsInit.php` |

## `pods_update_post` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_update_post` | action | `classes/PodsInit.php` |

## `pods_update_run` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_update_run` | filter | `classes/PodsInit.php` |

## `pods_upload_attachment` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_upload_attachment` | filter | `classes/fields/file.php` |

## `pods_upload_handle` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_upload_handle` | filter | `classes/fields/file.php` |

## `pods_v_custom` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_v_custom` | filter | `includes/data.php` |

## `pods_v_disallowed` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_v_disallowed_types_for_source_{$params->source}` | filter | `includes/data.php` |

## `pods_var_post` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_var_post_id` | filter | `includes/data.php` |

## `pods_var_set` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_var_set_{$type}` | filter | `includes/data.php` |

## `pods_var_{$type}` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_var_{$type}` | filter | `includes/data.php` |

## `pods_view_alt` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_alt_view` | filter | `classes/PodsView.php` |

## `pods_view_clear` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_clear_{$cache_mode}` | action | `classes/PodsView.php` |

## `pods_view_get` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_get_{$cache_mode}` | filter | `classes/PodsView.php` |

## `pods_view_inc` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_inc` | filter | `classes/PodsView.php` |

## `pods_view_locate` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_locate_template` | filter | `classes/PodsView.php` |

## `pods_view_set` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_set_{$cache_mode}` | action | `classes/PodsView.php` |

## `pods_view_url` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_view_url_include` | filter | `classes/PodsView.php` |

## `pods_wp_post` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_wp_post_types` | filter | `classes/PodsInit.php` |

## `pods_wp_taxonomies` (1)

| Hook | Kind | File |
| --- | --- | --- |
| `pods_wp_taxonomies` | filter | `classes/PodsInit.php` |
