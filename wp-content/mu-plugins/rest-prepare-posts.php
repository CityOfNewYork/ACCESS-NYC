<?php

/**
 * Plugin Name: REST Prepare Posts
 * Description: Filter to modify the contents of posts called by the WP REST API. Currenlty modifies "Programs" only.
 * Author: NYC Opportunity
 */

use nyco\WpRestPreparePosts\RestPreparePosts\RestPreparePosts as RestPreparePosts;

if (file_exists(WPMU_PLUGIN_DIR . '/wp-rest-prepare-posts/index.php')) {
  require_once WPMU_PLUGIN_DIR . '/wp-rest-prepare-posts/index.php';

  $RestPreparePosts = new RestPreparePosts();
  $types = ['programs'];

  // Add custom fields to each post type in our list
  add_action('rest_api_init', function () use ($types, $RestPreparePosts) {
    foreach ($types as $type) {
      $RestPreparePosts->type = $type;
      $fields = $RestPreparePosts->getAcf();

      add_filter("rest_prepare_$type", function ($post) use ($fields, $RestPreparePosts) {
        // 1. Get the custom field values.
        foreach ($fields as $field)
          $post->data['acf'][$field['name']] = get_field($field['name']);

        // 2. Add public taxonomy details to the post.
        $post->data['terms'] = $RestPreparePosts->getTerms($post->data->id);

        return $post;
      });
    }
  });
}