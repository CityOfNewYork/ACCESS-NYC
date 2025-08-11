<?php

/**
 * Plugin Name: Content Workflow Format Meta Fields
 * Description: Format meta fields before pushing to Content Workflow/GatherContent
 * Author: NYC Opportunity
 */

add_filter('cwby_config_pre_meta_field_value_updated', function($unused_param, $meta_value, $meta_key, $push_object) {
  error_log("$meta_value: " . print_r($meta_value, true));
  error_log("$meta_key: " . print_r($meta_key, true));
  error_log("$push_object: " . print_r($push_object, true));
  error_log("$meta_value type: " . print_r(gettype($meta_value), true));
}, 10, 4);
