<?php

/**
 * Plugin Name: GatherContent Importer Custom Field Keys
 * Description: Mapped WordPress Field meta_keys
 * Author: NYC Opportunity
 */

add_filter('gathercontent_importer_custom_field_keys', function($meta_keys) {
  // empty array that will contain the unique meta keys for the mapped fields
  $new_meta_keys = array();

  // Creates a new array of meta_keys that are not prefixed with underscore
  foreach ($meta_keys as $key => $value) {
    if (substr($value, 0, 1) != '_') {
      $new_meta_keys[$key] = $value;
    }
  }

  // return the new array
  return $new_meta_keys;
});
