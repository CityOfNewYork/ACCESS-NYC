<?php

/**
 * Get a translated post id by it's title. By default, it retrieves the english
 * translation of a program post type by it's title using the wpml_object_id filter.
 * This is meant to help ensure that the correct post is returned for the
 * Locations > Programs ACF Relationship Field. Uses the WPML Object ID
 * filter @url https://wpml.org/wpml-hook/wpml_object_id/
 *
 * @param   String  $title  The title of the post.
 * @param   String  $type   The type of the post. Defaults to 'programs'
 * @param   String  $lang   The two character language code to retrieve it in.
 *
 * @return  Number
 */
function get_translated_id_by_title($title = false, $type = 'programs', $lang = 'en') {
  if ($title) {
    $post = get_page_by_title($title, OBJECT, $type);

    /** @link https://wpml.org/wpml-hook/wpml_object_id */
    $id = apply_filters('wpml_object_id', $post->ID, $type, true, $lang);

    return $id;
  }
}

?>