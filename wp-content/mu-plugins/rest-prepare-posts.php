<?php

/**
 * Plugin Name: REST Prepare Posts
 * Description: Filter to modify the contents of posts called by the WP REST API. Currenlty modifies "Programs" only.
 * Author: NYC Opportunity
 */

use RestPreparePosts\RestPreparePosts as RestPreparePosts;

require_once plugin_dir_path(__FILE__) . '/rest-prepare-posts/RestPreparePosts.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$types = ['programs', 'location', 'homepage_tout', 'alert'];

// Render the ACF "Show in REST" field
RestPreparePosts::renderRestFieldSetting();

// Add custom fields to each post type in our list
add_action('rest_api_init', function () use ($types) {
  foreach ($types as $type) {
    $RestPreparePosts = new RestPreparePosts();

    $RestPreparePosts->type = $type;

    $fields = $RestPreparePosts->getAcfShownInRest();

    add_filter('rest_prepare_' . $type, function ($post) use ($fields, $RestPreparePosts) {
      // 1. Get the custom field values.
      foreach ($fields as $field) {
        $post->data['acf'][$field['name']] = get_field($field['name']);
      }

      // 2. Add public taxonomy details to the post.
      $post->data['terms'] = $RestPreparePosts->getTerms($post->data['id']);

      // 3. Add public shared Timber context.
      $post->data['timber'] = $RestPreparePosts->getTimberContext($post->data['id']);

      return $post;
    });
  }
});
