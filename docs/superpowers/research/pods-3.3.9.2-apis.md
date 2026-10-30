# Pods 3.3.9.2 — PHP API entry points

Generated from `classes/` and `includes/`.

## Global functions (`pods_*`)

Total: **271**

| Function | File |
| --- | --- |
| `pods_absint($maybeint, $strict = true, $allow_negative = false)` | `includes/data.php` |
| `pods_access($privs, $method = 'OR')` | `includes/general.php` |
| `pods_access_bleep_data($data, array $additional_bleep_properties = [])` | `includes/access.php` |
| `pods_access_bleep_items(array $items, array $additional_bleep_properties = [])` | `includes/access.php` |
| `pods_access_bleep_placeholder()` | `includes/access.php` |
| `pods_access_bleep_text($value)` | `includes/access.php` |
| `pods_access_bypass_post_with_password(array $args)` | `includes/access.php` |
| `pods_access_bypass_private_post(array $args)` | `includes/access.php` |
| `pods_access_callback_allowed($callback, array $params = [])` | `includes/access.php` |
| `pods_access_clear_disallowed_display_callbacks()` | `includes/access.php` |
| `pods_access_create_form_nonce($pod, $id, $submitted_fields, $uri_hash = null)` | `includes/access.php` |
| `pods_access_form_field_hash($submitted_fields)` | `includes/access.php` |
| `pods_access_form_field_names($context, $group_key = '')` | `includes/access.php` |
| `pods_access_form_nonce_action($pod, $id, $submitted_fields, $uri_hash = null, $uid = null)` | `includes/access.php` |
| `pods_access_form_nonce_present_in_request($context = 'form', $group_key = '', $source = 'post')` | `includes/access.php` |
| `pods_access_form_normalize_fields($submitted_fields)` | `includes/access.php` |
| `pods_access_form_uid()` | `includes/access.php` |
| `pods_access_form_uri_hash($path = null)` | `includes/access.php` |
| `pods_access_get_capabilities_preview(string $pod_type, string $pod_name)` | `includes/access.php` |
| `pods_access_get_dynamic_features_allow_options()` | `includes/access.php` |
| `pods_access_get_form_nonce_fields($pod, $id, $submitted_fields, $nonce_field_names = null, $uri_hash = null)` | `includes/access.php` |
| `pods_access_get_restricted_dynamic_features_options()` | `includes/access.php` |
| `pods_access_map_capabilities(array $args, ?int $user_id = null, bool $strict = false)` | `includes/access.php` |
| `pods_access_output_form_nonce_fields($pod, $id, $submitted_fields, $nonce_field_names = null, $uri_hash = null)` | `includes/access.php` |
| `pods_access_pod_options(string $pod_type, string $pod_name, ?Pod $pod = null)` | `includes/access.php` |
| `pods_access_record_disallowed_display_callback(string $callback)` | `includes/access.php` |
| `pods_access_settings_config()` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_comments(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_double_hyphens(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_mismatch_parenthesis(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_post_status(bool $allowed, string $sql, string $context, array $info, ?object $params = null)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_subqueries(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_unsafe_functions(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_unsafe_keywords(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_disallow_unsafe_tables(bool $allowed, string $sql)` | `includes/access.php` |
| `pods_access_sql_fragment_is_allowed(string $sql, string $context, array $args = [], ?object $params = null)` | `includes/access.php` |
| `pods_access_verify_form_nonce($nonce, $pod, $id, $submitted_fields, $uri_hash = null)` | `includes/access.php` |
| `pods_access_verify_form_nonce_from_request($nonce_field_names = null, $source = 'post')` | `includes/access.php` |
| `pods_admin()` | `includes/classes.php` |
| `pods_allow_deprecated($strict = true)` | `includes/general.php` |
| `pods_api($pod = null, $format = null)` | `includes/classes.php` |
| `pods_api_cache()` | `includes/general.php` |
| `pods_array($container)` | `includes/classes.php` |
| `pods_array_filter_null(array $data)` | `includes/data.php` |
| `pods_array_filter_null_and_empty_string(array $data)` | `includes/data.php` |
| `pods_attachment_import($url, $post_parent = null, $featured = false, $strict = false)` | `includes/media.php` |
| `pods_audio($url, $args = false)` | `includes/media.php` |
| `pods_bool_to_int($value)` | `includes/data.php` |
| `pods_by_title($title, $output = OBJECT, $type = 'page', $status = null, $return = 'post')` | `includes/general.php` |
| `pods_cache_clear($key = true, $group = '')` | `includes/general.php` |
| `pods_cache_get($key, $group = '', $callback = null)` | `includes/general.php` |
| `pods_cache_set($key, $value, $group = '', $expires = 0)` | `includes/general.php` |
| `pods_can_use_dynamic_feature(string $type)` | `includes/access.php` |
| `pods_can_use_dynamic_feature_sql_clauses(?string $clause_type = null)` | `includes/access.php` |
| `pods_can_use_dynamic_feature_unrestricted(array $args, string $type, ?string $mode = null)` | `includes/access.php` |
| `pods_can_use_dynamic_features(?Pod $pod = null)` | `includes/access.php` |
| `pods_can_use_sessions($only_env_check = false)` | `includes/general.php` |
| `pods_cast($value, $cast_from = null)` | `includes/data.php` |
| `pods_clean_linebreaks($content)` | `includes/data.php` |
| `pods_clean_memory($sleep_time = 0)` | `includes/data.php` |
| `pods_clean_name($orig, $lower = true, $trim_underscores = false)` | `includes/data.php` |
| `pods_clone_object($object)` | `includes/data.php` |
| `pods_clone_objects($objects)` | `includes/data.php` |
| `pods_compatibility_check()` | `includes/general.php` |
| `pods_components()` | `includes/classes.php` |
| `pods_config_for_field($field, $pod = null)` | `includes/general.php` |
| `pods_config_for_pod($pod)` | `includes/general.php` |
| `pods_config_get_all_fields($pod)` | `includes/general.php` |
| `pods_config_get_field_from_all_fields($field, $pod, $arg = null)` | `includes/general.php` |
| `pods_config_get_field_from_value_field($value_field)` | `includes/general.php` |
| `pods_config_get_fields_from_value_fields(array $value_fields)` | `includes/general.php` |
| `pods_config_merge_data($config_to_merge_into, $config_to_merge_from)` | `includes/general.php` |
| `pods_config_merge_fields($configs_to_merge_into, $configs_to_merge_from)` | `includes/general.php` |
| `pods_container($slug_or_class = null)` | `includes/general.php` |
| `pods_container_callback($slug_or_class, $method)` | `includes/general.php` |
| `pods_container_register_service_provider($provider_class)` | `includes/general.php` |
| `pods_create_slug($value, $strict = true)` | `includes/data.php` |
| `pods_current_host()` | `includes/general.php` |
| `pods_current_path()` | `includes/general.php` |
| `pods_current_url()` | `includes/general.php` |
| `pods_current_user_can_access_object(array $args, string $access_type = 'edit', ?string $context = null)` | `includes/access.php` |
| `pods_data($pod = null, $id = null, $strict = true, $unique = true)` | `includes/classes.php` |
| `pods_data_field($obj, $field_name)` | `includes/general.php` |
| `pods_debug($debug = '_null', $die = false, $prefix = '_null')` | `includes/general.php` |
| `pods_debug_log($debug)` | `includes/general.php` |
| `pods_debug_log_data($debug, string $context, string $function, int $line)` | `includes/general.php` |
| `pods_deprecated($function, $version, $replacement = null)` | `includes/general.php` |
| `pods_developer()` | `includes/general.php` |
| `pods_do_hook($scope, $name, $args = null, $obj = null)` | `includes/general.php` |
| `pods_do_shortcode($content, $shortcodes = [], $ignored_shortcodes = [])` | `includes/general.php` |
| `pods_doing_json()` | `includes/general.php` |
| `pods_doing_shortcode($bool = null)` | `includes/general.php` |
| `pods_enforce_safe_class(?string $value)` | `includes/data.php` |
| `pods_enforce_safe_id(?string $value)` | `includes/data.php` |
| `pods_enforce_safe_url(string $url, ?string $fallback_url = null)` | `includes/data.php` |
| `pods_enforce_safe_value_via_regex(?string $value, string $disallowed_pattern)` | `includes/data.php` |
| `pods_error($error, $obj = null)` | `includes/general.php` |
| `pods_error_exception($error)` | `includes/general.php` |
| `pods_eval_show_errors()` | `includes/general.php` |
| `pods_evaluate_tag($tag, $args = array()` | `includes/data.php` |
| `pods_evaluate_tag_sanitized($tag, $args = array()` | `includes/data.php` |
| `pods_evaluate_tags($tags, $args = array()` | `includes/data.php` |
| `pods_evaluate_tags_sql($tags, $args = array()` | `includes/data.php` |
| `pods_field($pod, $id = null, $name = null, $single = false)` | `includes/general.php` |
| `pods_field_display($pod, $id = null, $name = null, $single = false)` | `includes/general.php` |
| `pods_field_raw($pod, $id = null, $name = null, $single = false)` | `includes/general.php` |
| `pods_field_update($pod, $id = null, $name = null, $value = null)` | `includes/general.php` |
| `pods_form()` | `includes/classes.php` |
| `pods_form_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false)` | `includes/forms.php` |
| `pods_form_enqueue_style($handle, $src = '', $deps = array()` | `includes/forms.php` |
| `pods_form_field_make_hidden($field)` | `includes/forms.php` |
| `pods_form_field_make_readonly($field)` | `includes/forms.php` |
| `pods_form_get_submitted_field_value($field, $method = 'post')` | `includes/forms.php` |
| `pods_form_get_submitted_field_values($name, array $options = [])` | `includes/forms.php` |
| `pods_form_get_submitted_fields($name, array $options = [])` | `includes/forms.php` |
| `pods_form_get_visible_objects($pod, array $options = [])` | `includes/forms.php` |
| `pods_form_render_fields($name, $object_id, array $options = [])` | `includes/forms.php` |
| `pods_form_save_submitted_fields($name, $object_id, $is_new_item = false, array $options = [])` | `includes/forms.php` |
| `pods_form_validate_submitted_fields($name, $object_id = null, array $options = [])` | `includes/forms.php` |
| `pods_function_or_file($function_or_file, $function_name = null, $file_dir = null, $file_name = null)` | `includes/general.php` |
| `pods_get_access_admin_notice(array $args, bool $force_message = false, ?string $message = null)` | `includes/access.php` |
| `pods_get_access_user_notice(array $args, bool $force_message = false, ?string $message = null)` | `includes/access.php` |
| `pods_get_admin_capabilities(array $other_capabilities = [])` | `includes/general.php` |
| `pods_get_debug_timing()` | `includes/general.php` |
| `pods_get_disallowed_display_callbacks()` | `includes/access.php` |
| `pods_get_instance($type = null, $id = null, $strict = null)` | `includes/classes.php` |
| `pods_get_item_object($item_id, $object_type)` | `includes/data.php` |
| `pods_get_setting($setting_name, $default = null)` | `includes/general.php` |
| `pods_get_settings()` | `includes/general.php` |
| `pods_get_timing()` | `includes/general.php` |
| `pods_group_add($pod, $label, $fields, $context = 'normal', $priority = 'default', $type = null)` | `includes/general.php` |
| `pods_has_permissions($object)` | `includes/general.php` |
| `pods_help($text, $url = null, $container = null, $return = false)` | `includes/general.php` |
| `pods_helper($helper_name, $value = null, $name = null)` | `includes/general.php` |
| `pods_hierarchical_list($list, $args = array()` | `includes/data.php` |
| `pods_hierarchical_list_recurse($parent, $list, &$args)` | `includes/data.php` |
| `pods_hierarchical_select($list, $args = array()` | `includes/data.php` |
| `pods_hierarchical_select_recurse($items, $args, $depth = 0)` | `includes/data.php` |
| `pods_host_from_url($url)` | `includes/data.php` |
| `pods_i18n()` | `includes/classes.php` |
| `pods_image($image, $size = 'thumbnail', $default = 0, $attributes = '', $force = false)` | `includes/media.php` |
| `pods_image_for_post($field_name, $size = 'full', $default = 0)` | `includes/media.php` |
| `pods_image_id_from_field($image)` | `includes/media.php` |
| `pods_image_resize($attachment_id, $size)` | `includes/media.php` |
| `pods_image_url($image, $size = 'thumbnail', $default = 0, $force = false)` | `includes/media.php` |
| `pods_image_url_for_post($field_name, $size = 'full', $default = 0)` | `includes/media.php` |
| `pods_info_from_args(array $args)` | `includes/access.php` |
| `pods_init()` | `includes/classes.php` |
| `pods_is_admin($capabilities = null)` | `includes/general.php` |
| `pods_is_debug_display()` | `includes/general.php` |
| `pods_is_debug_logging_enabled()` | `includes/general.php` |
| `pods_is_demo()` | `includes/general.php` |
| `pods_is_falsey($value)` | `includes/data.php` |
| `pods_is_image_size($size)` | `includes/media.php` |
| `pods_is_modal_window()` | `includes/general.php` |
| `pods_is_plugin_active($plugin)` | `includes/general.php` |
| `pods_is_truthy($value)` | `includes/data.php` |
| `pods_is_type_public(array $args, string $context = 'shortcode')` | `includes/access.php` |
| `pods_is_types_only($check_constant_only = false, $content_type = null)` | `includes/general.php` |
| `pods_is_user_admin(int $user_id, $capabilities = null)` | `includes/general.php` |
| `pods_js_camelcase_name($orig)` | `includes/data.php` |
| `pods_js_name($orig, $lower = true)` | `includes/data.php` |
| `pods_kses_exclude_p($content)` | `includes/data.php` |
| `pods_kses_exclude_tags($content, $context = 'post', $disallowed_html = [], $disallowed_protocols = [])` | `includes/data.php` |
| `pods_light()` | `includes/general.php` |
| `pods_list_filter($list, $args = array()` | `includes/data.php` |
| `pods_maybe_clean_memory($sleep_time = 0, $after = 100)` | `includes/data.php` |
| `pods_maybe_image_resize($attachment_id, $size)` | `includes/media.php` |
| `pods_maybe_safely_unserialize($data)` | `includes/access.php` |
| `pods_mb_strlen($string)` | `includes/data.php` |
| `pods_mb_substr($string, $start, $length = null, $encoding = null)` | `includes/data.php` |
| `pods_message($message, $type = null, $return = false, $is_dismissible = true)` | `includes/general.php` |
| `pods_meta()` | `includes/classes.php` |
| `pods_meta_hook_list($object_type = 'post', $object = null)` | `includes/general.php` |
| `pods_migrate($type = null, $delimiter = null, $data = null)` | `includes/classes.php` |
| `pods_no_conflict_check($object_type = 'post')` | `includes/general.php` |
| `pods_no_conflict_off($object_type = 'post', $object = null, $force = false)` | `includes/general.php` |
| `pods_no_conflict_on($object_type = 'post', $object = null)` | `includes/general.php` |
| `pods_objects_keyed_by_name($objects)` | `includes/data.php` |
| `pods_option_cache_clear($key = true, $group = '')` | `includes/general.php` |
| `pods_option_cache_get($key, $group = '', $callback = null)` | `includes/general.php` |
| `pods_option_cache_set($key, $value, $expires = 0, $group = '')` | `includes/general.php` |
| `pods_output_kses_exclude_p($content)` | `includes/data.php` |
| `pods_parse_image_size($size)` | `includes/media.php` |
| `pods_permission($object)` | `includes/general.php` |
| `pods_podsrel_enabled($field = null, $context = null)` | `includes/general.php` |
| `pods_query($sql, $error = 'Database Error', $results_error = null, $no_results_error = null)` | `includes/general.php` |
| `pods_query_arg($array = null, $allowed = null, $excluded = null, $url = null)` | `includes/data.php` |
| `pods_query_prepare($sql, $prepare, $error = 'Database Error', $results_error = null, $no_results_error = null)` | `includes/general.php` |
| `pods_redirect($location = null, $status = 302, $die = true)` | `includes/general.php` |
| `pods_register_block_collection(array $collection)` | `includes/general.php` |
| `pods_register_block_type(array $block, array $fields = [])` | `includes/general.php` |
| `pods_register_config_file($file, $config_type = 'json')` | `includes/general.php` |
| `pods_register_config_item_type($item_type)` | `includes/general.php` |
| `pods_register_config_path($path)` | `includes/general.php` |
| `pods_register_config_type($type)` | `includes/general.php` |
| `pods_register_field($pod, $name, $field = null)` | `includes/general.php` |
| `pods_register_field_type($type, $file = null)` | `includes/general.php` |
| `pods_register_group(array $group, $pod, array $fields = [])` | `includes/general.php` |
| `pods_register_group_field(array $field, $group, $pod)` | `includes/general.php` |
| `pods_register_object(array $object, $type)` | `includes/general.php` |
| `pods_register_related_object($name, $label, $options = null)` | `includes/general.php` |
| `pods_register_type($type, $name, $object = null)` | `includes/general.php` |
| `pods_relationship_meta_storage_enabled($field = null, $pod = null)` | `includes/general.php` |
| `pods_relationship_meta_storage_enabled_for_simple_relationships($field = null, $pod = null)` | `includes/general.php` |
| `pods_relationship_table_storage_enabled_for_object_relationships($field = null, $pod = null)` | `includes/general.php` |
| `pods_relationship_table_storage_enabled_for_simple_relationships($field = null, $pod = null)` | `includes/general.php` |
| `pods_render_is_in_block()` | `includes/general.php` |
| `pods_replace_gt_et_placeholders($value)` | `includes/data.php` |
| `pods_replace_keys_to_values($value, $replacements)` | `includes/data.php` |
| `pods_require_component($component)` | `includes/general.php` |
| `pods_reserved_keywords($context = null)` | `includes/general.php` |
| `pods_sanitize($input, $params = array()` | `includes/data.php` |
| `pods_sanitize_like($input)` | `includes/data.php` |
| `pods_serial_comma($value, $field = null, $fields = null, $and = null, $field_index = null)` | `includes/data.php` |
| `pods_session_auto_start($check_constant_only = false)` | `includes/general.php` |
| `pods_session_id()` | `includes/general.php` |
| `pods_session_start()` | `includes/general.php` |
| `pods_set_render_is_in_block(bool $render_is_in_block)` | `includes/general.php` |
| `pods_shortcode($tags, $content = null)` | `includes/general.php` |
| `pods_shortcode_allow_evaluate_tags()` | `includes/general.php` |
| `pods_shortcode_detect_context(array $attributes)` | `includes/general.php` |
| `pods_shortcode_form($tags, $content = null)` | `includes/general.php` |
| `pods_shortcode_query_var_affix(array $attributes)` | `includes/general.php` |
| `pods_shortcode_run($tags, $content = null, $blog_is_switched = false, $check_display_access_rights = true)` | `includes/general.php` |
| `pods_shortcode_run_safely($tags, ?string $content = null, bool $check_display_access_rights = true)` | `includes/general.php` |
| `pods_site_transient_clear($key = true)` | `includes/general.php` |
| `pods_site_transient_get($key, $callback = null)` | `includes/general.php` |
| `pods_site_transient_set($key, $value, $expires = 0)` | `includes/general.php` |
| `pods_slash($input, $params = array()` | `includes/data.php` |
| `pods_static_cache_clear($key = true, $group = '')` | `includes/general.php` |
| `pods_static_cache_get($key, $group = '', $callback = null)` | `includes/general.php` |
| `pods_static_cache_set($key, $value, $group = '')` | `includes/general.php` |
| `pods_str_replace($find, $replace, $string, $occurrences = - 1)` | `includes/data.php` |
| `pods_strict($include_debug = true)` | `includes/general.php` |
| `pods_svg_icon($icon_path, $default = 'dashicons-database', $mode = 'base64')` | `includes/general.php` |
| `pods_tableless()` | `includes/general.php` |
| `pods_template_part($template, $data = null, $return = false)` | `includes/general.php` |
| `pods_transient_clear($key = true)` | `includes/general.php` |
| `pods_transient_get($key, $callback = null)` | `includes/general.php` |
| `pods_transient_set($key, $value, $expires = 0)` | `includes/general.php` |
| `pods_traverse($traverse, $value)` | `includes/data.php` |
| `pods_trim($input, $charlist = " \t\n\r\0\x0B", $lr = null)` | `includes/data.php` |
| `pods_ui($obj, $deprecated = false)` | `includes/classes.php` |
| `pods_unique_slug($slug, $column_name, $pod, $pod_id = 0, $id = 0, $obj = null, $strict = true)` | `includes/data.php` |
| `pods_unsanitize($input, $params = array()` | `includes/data.php` |
| `pods_unslash($input)` | `includes/data.php` |
| `pods_update_setting($setting_name, $setting_value)` | `includes/general.php` |
| `pods_update_settings($setting_values)` | `includes/general.php` |
| `pods_upgrade($version = '')` | `includes/classes.php` |
| `pods_user_can_access_object(array $args, ?int $user_id, string $access_type = 'edit', ?string $context = null)` | `includes/access.php` |
| `pods_v($var = null, $type = 'get', $default = null, $strict = false, $params = array()` | `includes/data.php` |
| `pods_v_bool($var = null, $type = 'get', bool $default = false)` | `includes/data.php` |
| `pods_v_sanitized($var = null, $type = 'get', $default = null, $strict = false, $params = array()` | `includes/data.php` |
| `pods_v_set($value, $var, $type = 'get')` | `includes/data.php` |
| `pods_validate_safe_path($path, $paths_to_check = null)` | `includes/data.php` |
| `pods_var($var = 'last', $type = 'get', $default = null, $allowed = null, $strict = false, $casting = false, $context = 'display')` | `includes/data.php` |
| `pods_var_raw($var = 'last', $type = 'get', $default = null, $allowed = null, $strict = false, $casting = false)` | `includes/data.php` |
| `pods_var_set($value, $var = 'last', $type = 'url')` | `includes/data.php` |
| `pods_var_update($array = null, $allowed = null, $excluded = null, $url = null)` | `includes/data.php` |
| `pods_var_user($anon = false, $user = true, $capability = null)` | `includes/data.php` |
| `pods_version_check($what, $minimum_version, $comparison = '<=', $maximum_version = null)` | `includes/general.php` |
| `pods_version_notice_mysql()` | `includes/general.php` |
| `pods_version_notice_php()` | `includes/general.php` |
| `pods_version_notice_wp()` | `includes/general.php` |
| `pods_video($url, $args = false)` | `includes/media.php` |
| `pods_view($view, $data = null, $expires = false, $cache_mode = 'cache', $return = false, $limited = false)` | `includes/classes.php` |
| `pods_view_clear($key = true, $cache_mode = 'cache', $group = '')` | `includes/general.php` |
| `pods_view_get($key, $cache_mode = 'cache', $group = '', $callback = null)` | `includes/general.php` |
| `pods_view_set($key, $value, $expires = 0, $cache_mode = 'cache', $group = '')` | `includes/general.php` |
| `pods_wrap_html($html, $attributes = [])` | `includes/general.php` |

## Field type classes

- `avatar` (`classes/fields/avatar.php`)
- `boolean` (`classes/fields/boolean.php`)
- `code` (`classes/fields/code.php`)
- `color` (`classes/fields/color.php`)
- `comment` (`classes/fields/comment.php`)
- `currency` (`classes/fields/currency.php`)
- `date` (`classes/fields/date.php`)
- `datetime` (`classes/fields/datetime.php`)
- `email` (`classes/fields/email.php`)
- `file` (`classes/fields/file.php`)
- `heading` (`classes/fields/heading.php`)
- `html` (`classes/fields/html.php`)
- `link` (`classes/fields/link.php`)
- `number` (`classes/fields/number.php`)
- `oembed` (`classes/fields/oembed.php`)
- `paragraph` (`classes/fields/paragraph.php`)
- `password` (`classes/fields/password.php`)
- `phone` (`classes/fields/phone.php`)
- `pick` (`classes/fields/pick.php`)
- `slug` (`classes/fields/slug.php`)
- `taxonomy` (`classes/fields/taxonomy.php`)
- `text` (`classes/fields/text.php`)
- `time` (`classes/fields/time.php`)
- `website` (`classes/fields/website.php`)
- `wysiwyg` (`classes/fields/wysiwyg.php`)

## Key class public methods

### `PodsAPI` (`classes/PodsAPI.php`)

- `public static function init($pod = null, $format = null)`
- `public function __construct($pod = null, $format = null)`
- `public function save_wp_object($object_type, $data, $meta = [], $strict = false, $sanitized = false, $fields = [])`
- `public function delete_wp_object($object_type, $id, $force_delete = true)`
- `public function save_meta($meta_type, $id, $meta = null, $strict = false, $fields = [])`
- `public function save_post($post_data, $post_meta = null, $strict = false, $sanitized = false, $fields = [])`
- `public function save_post_meta($id, $post_meta = null, $strict = false, $fields = [])`
- `public function save_user($user_data, $user_meta = null, $strict = false, $sanitized = false, $fields = [])`
- `public function save_user_meta($id, $user_meta = null, $strict = false, $fields = [])`
- `public function save_comment($comment_data, $comment_meta = null, $strict = false, $sanitized = false, $fields = [])`
- `public function save_comment_meta($id, $comment_meta = null, $strict = false, $fields = [])`
- `public function save_term($term_data, $term_meta, $strict = false, $sanitized = false, $fields = [])`
- `public function save_term_meta($id, $term_meta = null, $strict = false, $fields = [])`
- `public function save_setting($setting, $option_data, $sanitized = false)`
- `public function rename_wp_object_type($object_type, $old_name, $new_name)`
- `public function get_wp_object_fields($object = 'post_type', $pod = null, $refresh = false)`
- `public function add_pod($params)`
- `public function save_pod($params, $sanitized = false, $db = true)`
- `public function save_pod_table_schema($pod, array $fields, array $old_info, $overwrite_table_schema = true)`
- `public function save_pod_handle_rename($pod, $old_name)`
- `public function save_pod_handle_sync_built_in($pod)`
- `public function save_pod_group_field_order($groups, $object, $db = true)`
- `public function add_field($params, $table_operation = true, $sanitized = false, $db = true)`
- `public function save_field($params, $table_operation = true, $sanitized = false, $db = true)`
- `public function add_group($params, $sanitized = false, $db = true)`
- `public function save_group($params, $sanitized = false, $db = true)`
- `public function save_slug_fix($slug, $post_ID, $post_status, $post_type, $post_parent = 0, $original_slug = null)`
- `public function save_object($params, $sanitized = false)`
- `public function save_template($params, $sanitized = false)`
- `public function save_page($params, $sanitized = false)`
- `public function save_helper($params, $sanitized = false)`
- `public function save_pod_item($params)`
- `public function prepare_tableless_data_for_save($pod, $field, $values, $pieces)`
- `public function save_pod_items($params, $data)`
- `public static function handle_changed_fields($pod, $id, $mode = 'set')`
- `public function get_changed_fields($pieces)`
- `public function save_relationships($id, $related_ids, $pod, $field)`
- `public function duplicate_pod($params, $strict = false)`
- `public function duplicate_group($params, $strict = false)`
- `public function duplicate_field($params, $strict = false)`
- `public function duplicate_pod_item($params)`
- `public function export_pod_item($params, $pod = null)`
- `public function reorder_pod_item($params)`
- `public function reset_pod($params, $pod = false)`
- `public function delete_pod($params, $strict = false, $delete_all = false)`
- `public function delete_field($params, $table_operation = true)`
- `public function delete_group($params, $strict = false, $delete_all = false)`
- `public function delete_object($params)`
- `public function delete_template($params)`
- `public function delete_page($params)`
- `public function delete_helper($params)`
- `public function delete_pod_item($params, $wp = true)`
- `public function delete_object_from_relationships($id, $object, $name = null)`
- `public function delete_relationships($related_id, $id, $related_pod, $related_field, $force = true)`
- `public function pod_exists($params, $type = null)`
- `public function get_pod_type_count($type)`
- `public function load_pod($params, $strict = false)`
- `public function load_pods($params = [])`
- `public function field_exists($params, $allow_id = true)`
- `public function load_field($params, $strict = false)`
- `public function traverse_fields(array $params)`
- `public function load_fields($params = [])`
- `public function group_exists($params, $allow_id = true)`
- `public function load_group($params, $strict = false)`
- `public function load_groups($params = [])`
- `public function load_object($params, $strict = false)`
- `public function load_objects($params)`
- `public function load_template($params)`
- `public function load_templates($params = null)`
- `public function load_page($params)`
- `public function load_pages($params = null)`
- `public function load_helper($params)`
- `public function load_helpers($params = null)`
- `public function load_pod_item($params)`
- `public function load_sister_fields($params, $pod = null)`
- `public static function detect_pod_field_from_sql_data_type($sql_field)`
- `public function get_field_types()`
- `public function handle_field_validation(&$value, $field, $object_fields, $fields, $pod, $params = [])`
- `public function lookup_related_items($field_id, $pod_id, $ids, $field = null, $pod = null)`
- `public function lookup_related_items_from($field_id, $pod_id, $id, $field = null, $pod = null)`
- `public function get_table_info_load($object_type, $object, $name = null, $pod = null)`
- `public function get_table_info($object_type, $object, $name = null, $pod = null, $field = null)`
- `public function export_package($params)`
- `public function replace_package($data = false)`
- `public function import_package($data = false, $replace = false)`
- `public function validate_package($data = false, $output = false)`
- `public function import($import_data, $numeric_mode = false, $format = null)`
- `public function export($pod = null, $params = null)`
- `public function csv_to_php($data, $delimiter = ',')`
- `public function cache_flush_pods($pod = null,
		$flush_rewrites = true,
		$flush_groups_and_fields = true,
		$static_only = false)`
- `public function cache_flush_groups($flush_fields = true, $static_only = false)`
- `public function cache_flush_fields($static_only = false)`
- `public function process_form($form_params, $obj = null, $fields = null, $thank_you = null)`
- `public function __get($name)`
- `public function get_default_object_storage_type()`
- `public function get_pods_object_from_wp_post($post)`
- `public function _load_object(array $params, $strict = false)`
- `public function _load_objects(array $params)`
- `public function get_pod_types()`
- `public function get_storage_types()`

### `Pods` (`classes/Pods.php`)

- `public function __construct($pod = null, $id = null, $strict = false)`
- `public function is_defined()`
- `public function is_valid()`
- `public function valid()`
- `public function is_iterator()`
- `public function stop_iterator()`
- `public function rewind()`
- `public function current()`
- `public function key()`
- `public function next()`
- `public function exists()`
- `public function has_rows()`
- `public function data()`
- `public function input($field, $input_name = null, $value = '__null')`
- `public function fields($field_name = null, $option = null)`
- `public function row()`
- `public function display($name, $single = null)`
- `public function raw($name, $single = null)`
- `public function field($name, $single = null, $raw = false)`
- `public function has($field, $value, $id = null)`
- `public function is($field, $value, $id = null)`
- `public function id()`
- `public function prev_id($id = null, $params_override = null)`
- `public function next_id($id = null, $params_override = null)`
- `public function first_id($params_override = null)`
- `public function last_id($params_override = null)`
- `public function index()`
- `public function find($params = null, $limit = 15, $where = null, $sql = null)`
- `public function fetch($id = null, $explicit_set = true)`
- `public function reset($row = null)`
- `public function total()`
- `public function total_found($params = null)`
- `public function total_all_rows()`
- `public function total_pages($limit = null, $offset = null, $total = null)`
- `public function zebra()`
- `public function nth($nth = null)`
- `public function position()`
- `public function add($data = null, $value = null)`
- `public function add_to($field, $value, $id = null)`
- `public function remove_from($field, $value = null, $id = null)`
- `public function save($data = null, $value = null, $id = null, $params = null)`
- `public function delete($id = null)`
- `public function reset_pod()`
- `public function duplicate($id = null)`
- `public function import($import_data, $numeric_mode = false, $format = null)`
- `public function export($fields = null, $id = null, $format = null)`
- `public function export_data($params = null)`
- `public function output_pagination($params = null)`
- `public function pagination($params = null)`
- `public function output_filters($params = null)`
- `public function filters($params = null)`
- `public function helper($helper, $value = null, $name = null)`
- `public function template_singular($template_name, $code = null, $deprecated = false)`
- `public function template($template_name, $code = null, $deprecated = false, $check_access = false)`
- `public function output_form($params = null, $label = null, $thank_you = null)`
- `public function form($params = null, $label = null, $thank_you = null)`
- `public function output_view($view_fields = null)`
- `public function view($view_fields = null)`
- `public function do_magic_tags($code)`
- `public function ui($options = null, $amend = false)`
- `public function __get($name)`
- `public function __set($name, $value)`
- `public function __isset($name)`
- `public function __unset($name)`
- `public function __call($name, $arguments)`
- `public function __toString()`

### `PodsForm` (`classes/PodsForm.php`)

- `public static function init()`
- `public static function output_label($name, $label, $help = '', $options = null)`
- `public static function label($name, $label, $help = '', $options = null)`
- `public static function output_comment($name, $message = null, $options = null)`
- `public static function comment($name, $message = null, $options = null)`
- `public static function output_field($name, $value, $type = 'text', $options = null, $pod = null, $id = null)`
- `public static function field($name, $value, $type = 'text', $options = null, $pod = null, $id = null)`
- `public static function output_submit_button($text = null, $type = 'primary large', $name = 'submit', $wrap = true, $other_attributes = null)`
- `public static function submit_button($text = null, $type = 'primary large', $name = 'submit', $wrap = true, $other_attributes = null)`
- `public static function row($name, $value, $type = 'text', $options = null, $pod = null, $id = null)`
- `public static function attributes($attributes, $name = null, $type = null, $options = null)`
- `public static function data($data, $name = null, $type = null, $options = null)`
- `public static function merge_attributes($attributes, $name = null, $type = null, $options = null, $classes = '')`
- `public static function options($type, $options)`
- `public static function options_setup($type = null, $options = null)`
- `public static function ui_options($type)`
- `public static function fields_setup($fields = null, $core_defaults = null, $single = false)`
- `public static function field_setup($field = null, $core_defaults = null, $type = null)`
- `public static function dependencies($options, $prefix = 'pods-form-ui-')`
- `public static function value($type, $value = null, $name = null, $options = null, $pod = null, $id = null, $traverse = null)`
- `public static function display($type, $value = null, $name = null, $options = null, $pod = null, $id = null, $traverse = null)`
- `public static function regex($type, $options)`
- `public static function prepare($type, $options)`
- `public static function validate($type, $value, $name = null, $options = null, $fields = null, $pod = null, $id = null, $params = null)`
- `public static function pre_save($type, $value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null)`
- `public static function save($type, $value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null)`
- `public static function delete($type, $id = null, $name = null, $options = null, $pod = null)`
- `public static function permission($type, $name = null, $options = null, $fields = null, $pod = null, $id = null, $params = null)`
- `public static function default_value($value, $type = 'text', $name = null, $options = null, $pod = null, $id = null)`
- `public static function clean($input, $noarray = false, $db_field = false)`
- `public function admin_init()`
- `public static function field_loader($field_type, $file = '')`
- `public static function field_method()`
- `public static function register_field_type($type, $file = null)`
- `public static function pod_types_list()`
- `public static function field_types_list()`
- `public static function field_types()`
- `public static function tableless_field_types()`
- `public static function file_field_types()`
- `public static function repeatable_field_types()`
- `public static function number_field_types()`
- `public static function date_field_types()`
- `public static function text_field_types()`
- `public static function block_field_types()`
- `public static function layout_field_types()`
- `public static function non_input_field_types()`
- `public static function separator_excluded_field_types()`
- `public static function revisionable_field_types()`
- `public static function simple_tableless_objects()`
- `public static function render_postbox_header($title)`

### `PodsMeta` (`classes/PodsMeta.php`)

- `public static function init()`
- `public function __construct()`
- `public function core()`
- `public static function enqueue()`
- `public function cache_pods($refresh = true)`
- `public function register($type, $pod)`
- `public function register_field($pod, $field)`
- `public function integrations()`
- `public function cpac_meta_keys($meta_fields, $storage_model)`
- `public function cpac_post_types($post_types)`
- `public function cpac_meta_value($meta, $id, $obj)`
- `public function group_add($pod, $label, $fields, $context = 'normal', $priority = 'default')`
- `public function setup_hooks($pod)`
- `public function object_get($type, $name)`
- `public function groups_get($type, $name, $default_fields = null, $full_objects = false, $id = null)`
- `public function groups_cleanup(array &$groups, array &$reference_groups)`
- `public function meta_post_add($post_type, $post = null)`
- `public function meta_post_enter_title_here($placeholder, $post)`
- `public function meta_post_revisions_to_keep($num, $post)`
- `public function add_class_submittable()`
- `public function maybe_set_up_pod($pod_name, $id = null, $pod_type = null)`
- `public function meta_post($post, $metabox)`
- `public function save_post_detect_new($new_status, $old_status, $post)`
- `public function save_post($post_id, $post, $update = null)`
- `public function save_post_track_changed_fields($data, $postarr)`
- `public function meta_media($form_fields, $post)`
- `public function save_media($post, $attachment)`
- `public function save_media_ajax()`
- `public function meta_taxonomy($tag, $taxonomy = null)`
- `public function save_taxonomy($term_id, $term_taxonomy_id, $taxonomy)`
- `public function save_taxonomy_track_changed_fields($term_id, $taxonomy)`
- `public function meta_user($user_id)`
- `public function save_user($user_id, $old_user_data = null)`
- `public function save_user_track_changed_fields($user_login)`
- `public function meta_comment_new_logged_in()`
- `public function meta_comment_new($submit_field)`
- `public function meta_comment_add($comment_type, $comment = null)`
- `public function meta_comment($comment, $metabox)`
- `public function validate_comment($approved, $commentdata)`
- `public function save_comment($comment_id)`
- `public function save_comment_track_changed_fields($data, $comment, $commentarr)`
- `public function get_keys_not_covered($type)`
- `public function is_type_covered($type, $object_name = null)`
- `public function is_key_covered($type, $key, $object_name = null)`
- `public function get_post_meta()`
- `public function get_user_meta()`
- `public function get_comment_meta()`
- `public function get_term_meta()`
- `public function get_option()`
- `public function add_post_meta()`
- `public function add_user_meta()`
- `public function add_comment_meta()`
- `public function add_term_meta()`
- `public function add_option()`
- `public function update_post_meta()`
- `public function update_user_meta()`
- `public function update_comment_meta()`
- `public function update_term_meta()`
- `public function update_option()`
- `public function update_post_meta_by_id()`
- `public function update_user_meta_by_id()`
- `public function update_comment_meta_by_id()`
- `public function update_term_meta_by_id()`
- `public function delete_post_meta()`
- `public function delete_user_meta()`
- `public function delete_comment_meta()`
- `public function delete_term_meta()`
- `public function delete_option()`
- `public function delete_post_meta_by_id()`
- `public function delete_user_meta_by_id()`
- `public function delete_comment_meta_by_id()`
- `public function delete_term_meta_by_id()`
- `public function get_object($object_type, $object_id, $aux = '')`
- `public function get_meta($object_type, $_null = null, $object_id = 0, $meta_key = '', $single = false)`
- `public function add_meta($object_type, $_null = null, $object_id = 0, $meta_key = '', $meta_value = '', $unique = false)`
- `public function update_meta($object_type, $_null = null, $object_id = 0, $meta_key = '', $meta_value = '', $prev_value = '')`
- `public function update_meta_by_id($object_type, $_null = null, $meta_id = 0, $meta_key = '', $meta_value = '')`
- `public function delete_meta($object_type, $_null = null, $object_id = 0, $meta_key = '', $meta_value = '', $delete_all = false)`
- `public function delete_meta_by_id($object_type, $_null = null, $meta_id = 0)`
- `public function delete_post($id)`
- `public function delete_taxonomy($id)`
- `public static function split_shared_term($term_id, $new_term_id, $term_taxonomy_id, $taxonomy)`
- `public function delete_user($id)`
- `public function delete_comment($id)`
- `public function delete_media($id)`
- `public function delete_object($type, $id, $name = null)`
- `public function cover_object_fields_in_meta()`

### `for` (`classes/PodsField.php`)

- `public function __construct()`
- `public function setup()`
- `public function admin_init()`
- `public function options()`
- `public function ui_options()`
- `public function schema($options = null)`
- `public function prepare($options = null)`
- `public function is_empty($value)`
- `public function values_are_empty($values, $strict = true)`
- `public function value($value = null, $name = null, $options = null, $pod = null, $id = null)`
- `public function display($value = null, $name = null, $options = null, $pod = null, $id = null)`
- `public function format($value = null, $name = null, $options = null, $pod = null, $id = null)`
- `public function input($name, $value = null, $options = null, $pod = null, $id = null)`
- `public function render_input_script($args)`
- `public function build_dfv_field_data($args)`
- `public function build_dfv_field_options($options, $args)`
- `public function build_dfv_field_attributes($attributes, $args)`
- `public function build_dfv_field_config($args)`
- `public function build_dfv_field_item_data($args)`
- `public function data($name, $value = null, $options = null, $pod = null, $id = null, $in_form = true)`
- `public function regex($value = null, $name = null, $options = null, $pod = null, $id = null)`
- `public function validate($value, $name = null, $options = null, $fields = null, $pod = null, $id = null, $params = null)`
- `public function pre_save($value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null)`
- `public function save($value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null)`
- `public function post_save($value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null)`
- `public function pre_delete($id = null, $name = null, $options = null, $pod = null)`
- `public function delete($id = null, $name = null, $options = null, $pod = null)`
- `public function post_delete($id = null, $name = null, $options = null, $pod = null)`
- `public function ui($id, $value, $name = null, $options = null, $fields = null, $pod = null)`
- `public function is_required($options)`
- `public function strip_html($value, $options = null)`
- `public function maybe_sanitize_output($value, $options = null)`
- `public function strip_shortcodes($value, $options = null)`
- `public function trim_whitespace($value, $options = null)`
- `public function normalize_value_for_input($value, $field, $separator = ' ')`
- `public static function __set_state($properties)`

### `PodsInit` (`classes/PodsInit.php`)

- `public static function init()`
- `public function __construct()`
- `public static function autoload_class($class)`
- `public function plugins_loaded()`
- `public function stats_tracking($plugin_file, $plugin_slug)`
- `public function after_setup_theme()`
- `public function load_components()`
- `public function load_meta()`
- `public function load_i18n()`
- `public function core()`
- `public function register_assets()`
- `public function add_classes_to_modal_body($classes)`
- `public function register_pods()`
- `public function admin_init()`
- `public function refresh_existing_content_types_cache($force = false)`
- `public function setup_content_types($force = false)`
- `public function quick_edit_enabled_for_post_type(bool $enable, string $post_type)`
- `public function quick_edit_enabled_for_taxonomy(bool $enable, string $taxonomy)`
- `public function flush_rewrite_rules($force = false)`
- `public function setup_updated_messages($messages)`
- `public static function object_label_fix(array $args, string $type, ?string $name = null)`
- `public function activate_install()`
- `public function activate()`
- `public function deactivate()`
- `public function needs_upgrade($current = null, $last = null)`
- `public function new_blog($_blog_id)`
- `public function setup($_blog_id = null)`
- `public function reset($_blog_id = null)`
- `public function run()`
- `public function delete_attachment($_ID)`
- `public function register_widgets()`
- `public function admin_bar_links()`
- `public function filter_wp_privacy_additional_user_profile_data($additional_user_profile_data, $user, $reserved_names)`

### `PodsRESTFields` (`classes/PodsRESTFields.php`)

- `public function __construct($pod)`
- `public function get_pod()`
- `public function set_pod($pod)`
- `public static function is_rest_authenticated()`
- `public function add_fields()`
- `public function register($field)`
- `public static function field_allowed_to_extend($field, $pod, $mode)`

### `PodsRESTHandlers` (`classes/PodsRESTHandlers.php`)

- `public static function get_pods_object($pod_name, $id)`
- `public static function get_handler($object, $field_name, $request, $object_type)`
- `public static function save_handler($object, $request, $creating)`

### `PodsUI` (`classes/PodsUI.php`)

- `public function __construct($options)`
- `public function setup($options)`
- `public function setup_fields($fields = null, $which = 'fields')`
- `public function message($msg, $error = false)`
- `public function error($msg)`
- `public function go()`
- `public function save_nonce_is_valid()`
- `public function add()`
- `public function edit($duplicate = false)`
- `public function form($create = false, $duplicate = false)`
- `public function view()`
- `public function reorder()`
- `public function save($insert = false)`
- `public function delete($id = null)`
- `public function delete_bulk()`
- `public function export_bulk()`
- `public function export_fields_form()`
- `public function export($export_type = null)`
- `public function get_field($field)`
- `public function get_params($params = null, $action = null)`
- `public function get_data($params = null)`
- `public function sort_data()`
- `public function get_row(&$counter = 0, $method = null)`
- `public function manage($reorder = false)`
- `public function filters()`
- `public function filters_popup()`
- `public function table($reorder = false)`
- `public function get_actions($row)`
- `public function screen_meta()`
- `public function pagination($header = false)`
- `public function limit($options = false)`
- `public function output_template($code, $row = false)`
- `public function do_template($code, $row = false)`
- `public function do_magic_tags($tag)`
- `public function hidden_vars($exclude = false, $array = false)`
- `public function exclusion()`
- `public function restricted($action = 'edit', $row = null)`
- `public function get_field_data($field, $which = 'manage')`
- `public function is_field_searchable($field)`
- `public function is_field_sortable($field)`
- `public function callback()`
- `public function callback_action()`
- `public function callback_bulk()`
- `public function my_filter_function($args, $obj)`

### `PodsData` (`classes/PodsData.php`)

- `public static function init($pod = null, $id = null, $strict = true)`
- `public function __construct($pod = null, $id = 0, $strict = false)`
- `public function table($table, $object = '')`
- `public function insert($table, $data, $format = null)`
- `public static function insert_on_duplicate($table, $data, $formats = array()`
- `public function update($table, $data, $where, $format = null, $where_format = null)`
- `public function delete($table, $where, $where_format = null)`
- `public function select($params)`
- `public function calculate_totals()`
- `public function build($params)`
- `public function total()`
- `public function total_found()`
- `public function zebra()`
- `public function nth($nth)`
- `public function position()`
- `public static function table_create($table, $fields, $if_not_exists = false)`
- `public static function table_alter($table, $changes)`
- `public static function table_truncate($table)`
- `public static function table_drop($table)`
- `public function reorder($table, $weight_field, $id_field, $ids)`
- `public function fetch($row = null, $explicit_set = true)`
- `public function reset($row = null)`
- `public static function query($sql, $error = 'Database Error', $results_error = null, $no_results_error = null)`
- `public static function get_tables($wp_core = true, $pods_tables = true)`
- `public static function get_table_columns($table)`
- `public static function get_column_data($column_name, $table)`
- `public static function prepare($sql, $data)`
- `public static function query_fields($fields, $pod = null, &$params = null)`
- `public static function query_field($field, $q, $pod = null, &$params = null)`
- `public function traverse_build($fields = null, $params = null)`
- `public function traverse_recurse($traverse_recurse)`
- `public function traverse($fields = null, $all_fields = null, $params = null)`
- `public static function get_pods_prefix()`
- `public function get_sql($sql = '')`
- `public static function get_db_field($params)`
- `public function __get($name)`
- `public function __set($name, $value)`
- `public function __isset($name)`
- `public function __unset($name)`

### `PodsField_Pick` (`classes/fields/pick.php`)

- `public function setup()`
- `public function admin_init()`
- `public function options()`
- `public function prepare($options = null)`
- `public function register_related_object($name, $label, $options = null)`
- `public function setup_related_objects($force = false)`
- `public function related_objects($force = false)`
- `public function simple_objects()`
- `public function bidirectional_objects()`
- `public function schema($options = null)`
- `public function display($value = null, $name = null, $options = null, $pod = null, $id = null)`
- `public function input($name, $value = null, $options = null, $pod = null, $id = null)`
- `public function build_dfv_field_options($options, $args)`
- `public function build_dfv_autocomplete_ajax_data($options, $args, $ajax = false)`
- `public function build_dfv_field_config($args)`
- `public function build_dfv_field_item_data($args)`
- `public function build_dfv_field_item_data_recurse($data, $args)`
- `public function build_dfv_field_item_data_recurse_item($item_id, $item_title, $args)`
- `public function validate($value, $name = null, $options = null, $fields = null, $pod = null, $id = null, $params = null)`
- `public function save($value, $id = null, $name = null, $options = null, $fields = null, $pod = null, $params = null)`
- `public function delete($id = null, $name = null, $options = null, $pod = null)`
- `public function ui($id, $value, $name = null, $options = null, $fields = null, $pod = null)`
- `public function get_raw_data($field)`
- `public function data($name, $value = null, $options = null, $pod = null, $id = null, $in_form = true)`
- `public function simple_value($name, $value = null, $options = null, $pod = null, $id = null, $raw = false)`
- `public function value_to_label($name, $value = null, $options = null, $pod = null, $id = null)`
- `public function get_field_data($field, $deprecated = null, $object_params = [])`
- `public function get_object_data($object_params = null)`
- `public function admin_ajax_relationship()`
- `public function data_post_stati($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_post_stati_with_any($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_roles($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_capabilities($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_image_sizes($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_post_types($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_taxonomies($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_countries($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_us_states($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_ca_provinces($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_days_of_week($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function data_months_of_year($name = null, $value = null, $options = null, $pod = null, $id = null)`
- `public function admin_modal_input()`
- `public function admin_modal_bail($item_id, $item_title, $field_args)`
- `public function admin_modal_bail_JSON($item_id, $item_title, $field_args)`
- `public function admin_modal_bail_post_redirect($location, $post_id)`
- `public function admin_modal_bail_term_action()`
- `public function admin_modal_bail_term_action_add_new()`
- `public function admin_modal_bail_term($term_id, $tt_id, $taxonomy)`
- `public function admin_modal_bail_user_action()`
- `public function admin_modal_bail_user_redirect($location)`
- `public function admin_modal_bail_pod($id, $params, $obj)`
- `public function build_dfv_field_data($args)`

## Relationship extension points

- `PodsField_Pick::register_related_object( $name, $label, $options )`
- Action `pods_form_ui_field_pick_related_objects_custom`
- Filters `pods_field_pick_data`, `pods_field_pick_data_ajax`, `pods_field_pick_object_data`, `pods_field_dfv_data`, `pods_form_ui_field_pick_custom_values`
