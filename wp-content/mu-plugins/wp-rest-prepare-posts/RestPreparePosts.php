<?php

namespace nyco\WpRestPreparePosts\RestPreparePosts;

/**
 * Functions for preparing posts for the WP Rest API
 */
class RestPreparePosts
{

  /** Required. The post type of the rest client */
  var $type = '';

  /** The ACF rest option */
  var $acf_rest_option = 'show_in_rest';

  /**
   * The options for get_taxonomies()
   * @url https://codex.wordpress.org/Function_Reference/get_taxonomies
   */
  var $tax_options = array(
    'public' => true,
    '_builtin' => false
  );

  /**
   * Class Constuctor
   * Add the "Show in Rest" toggle to the ACF settings UI
   * @url https://github.com/airesvsg/acf-to-rest-api#field-settings
   */
  function __construct() {
    add_filter('acf/rest_api/field_settings/show_in_rest', '__return_true');
  }

  /**
   * Finds the AC Fields for a particular post type and returns them.
   * It uses the option "Show In Rest" exposed by the ACF Rest plugin
   * to determine if a field should be returned. Does not return the
   * values, those must be retrieved per post.
   *
   * @return  Array The collection of AC Fields set to "Show In Rest"
   */
  public function getAcf() {
    $groups = acf_get_field_groups();

    foreach ($groups as $group)
      foreach ($group['location'] as $location)
        foreach ($location as $loc)
          if ($loc['operator'] === '==' && $loc['value'] === $this->type)
            $post_type_groups[] = $group;

    foreach ($post_type_groups as $group)
      foreach (acf_get_fields($group['key']) as $field)
        if ($field[$this->acf_rest_option]) $fields[] = $field;

    return $fields;
  }

  /**
   * This will get public taxonomies of a particular post. For custom taxonomies
   * the "show_in_rest" configuration must be set to true on registration.
   *
   * @param   Number  $id  The Post ID.
   *
   * @return  Array        The post's public, show in rest, terms.
   */
  public function getTerms($id) {
    $terms = array();
    foreach (get_taxonomies($this->tax_options, 'objects') as $taxonomy) {
      $terms = array_merge($terms, get_the_terms($id, $taxonomy->name));
    }

    return $terms;
  }

}