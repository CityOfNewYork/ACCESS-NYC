<?php

/**
 * Plugin Name:  NYCO WP Rest Prepare Posts
 * Description:  Functions for helping prepare posts for the WP REST API
 * Author:       NYC Opportunity
 * Requirements: The functions work with configurations from the ACF to REST
 *               API plugin, as well as default WordPress configurations such
 *               as "show_in_rest" for registering taxonomies.
 */

namespace RestPreparePosts;

/**
 * Functions for preparing posts for the WP Rest API
 */
class RestPreparePosts {
  /** Required. The post type of the rest client */
  public $type = '';

  /** The ACF rest option */
  public $acf_rest_option = 'show_in_rest';

  /**
   * The options for get_taxonomies()
   * @url https://codex.wordpress.org/Function_Reference/get_taxonomies
   */
  public $tax_options = array(
    'public' => true,
    '_builtin' => false
  );

  /**
   * Class Constuctor
   */
  public function __construct() {
    /**
     * Add the "Show in Rest" toggle to the ACF settings UI
     * @url https://github.com/airesvsg/acf-to-rest-api#field-settings
     */
    add_action('acf/render_field_settings', function ($field) {
      acf_render_field_setting($field, array(
        'label'         => __('Show in REST API?'),
        'instructions'  => '',
        'type'          => 'true_false',
        'name'          => 'show_in_rest',
        'ui'            => 1,
        'class'         => 'field-show_in_rest',
        'default_value' => 0,
      ), true);
    });
  }

  /**
   * Finds the AC Fields for a particular post type and returns them.
   * It uses the option "Show In Rest" exposed by the ACF Rest plugin
   * to determine if a field should be returned. Does not return the
   * values, those must be retrieved per post.
   *
   * @return  Array The collection of AC Fields set to "Show In Rest"
   */
  public function getAcfShownInRest() {
    $groups = acf_get_field_groups();

    foreach ($groups as $group) {
      foreach ($group['location'] as $location) {
        foreach ($location as $loc) {
          if ($loc['operator'] === '==' && $loc['value'] === $this->type) {
            $post_type_groups[] = $group;
          }
        }
      }
    }

    foreach ($post_type_groups as $group) {
      foreach (acf_get_fields($group['key']) as $field) {
        if (isset($field[$this->acf_rest_option])) {
          $fields[] = $field;
        }
      }
    }

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

  /**
   * Get the Timber Controller and construct a Timber post. Return context
   * that extends the Timber View of the post.
   *
   * @param   Number  $id  ID of the post
   *
   * @return  Object       If REST method exists in post ctrl, return items.
   */
  public function getTimberContext($id) {
    $slug = str_replace('_', '-', $this->type);
    $class = str_replace(' ', '', ucwords(str_replace('-', ' ', $slug)));

    $path = get_stylesheet_directory() . '/controllers/' . $slug . '.php';

    if (file_exists($path)) {
      require_once $path;
    } else {
      return null;
    }

    $cntrlClass = "Controller\\" . $class;

    $cntrlPost = new $cntrlClass($id);

    return (method_exists($cntrlPost, 'showInRest')) ?
      $cntrlPost->showInRest() : null;
  }

  /**
   * Show the ACF "Show in REST API" field setting
   *
   * @return  Null
   */
  public static function renderRestFieldSetting() {
    add_action('acf/render_field_settings', function ($field) {
      acf_render_field_setting($field, array(
        'label' => __('Show in REST API?'),
        'instructions' => '',
        'type' => 'true_false',
        'name' => 'show_in_rest',
        'ui' => 1,
        'class' => 'field-show_in_rest',
        'default_value' => 0,
      ), true);
    });
  }
}
