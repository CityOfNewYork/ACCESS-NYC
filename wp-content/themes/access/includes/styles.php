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
  require_once(
    get_template_directory() .
    '/vendor/nyco/wp-assets/dist/style.php'
  );

  $languages = array('ar', 'ko', 'ur', 'zh-hant');
  error_reporting(0);
  $lang = (!in_array(ICL_LANGUAGE_CODE, $languages))
    ? 'default' : ICL_LANGUAGE_CODE;
  error_reporting(WP_DEBUG);

  $style = Nyco\Enqueue\style("$name-$lang");
}

/**
 * Disable the WP Security Questions stylesheet
 * @return null
 */
function unstyle_wp_security_questions() {
  wp_deregister_style('wsq-frontend.css');
} add_action('wp_print_styles', 'unstyle_wp_security_questions', 100);
