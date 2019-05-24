<?php

/**
 * Styles
 */

/**
 * Enqueue a hashed style based on it's name and language prefix.
 * @param  [string] $name the name of the stylesheet source
 * @return null
 */
function enqueue_language_style($name) {
  require_once ABSPATH . '/vendor/nyco/wp-assets/dist/style.php';

  $languages = array('ar', 'ko', 'ur', 'zh-hant');
  error_reporting(0);
  $lang = (!in_array(ICL_LANGUAGE_CODE, $languages))
  ? 'default' : ICL_LANGUAGE_CODE;
  error_reporting(WP_DEBUG);

  $style = Nyco\Enqueue\style("assets/styles/$name-$lang");
}
