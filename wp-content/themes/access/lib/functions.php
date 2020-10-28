<?php

/**
 * Theme Functions
 *
 * Only functions to be made available to view templates should be added to
 * functions. Site configuration should be modified/added as Must Use Plugins.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions
 */

/**
 * Dependencies
 */

use NYCO\WpAssets as WpAssets;

/**
 * Return a localized reading friendly string of the environment.
 *
 * @param   String  $env  The environment string to return if unknown.
 *
 * @return  String        The localized reading friendly string.
 */
function environment_string($env = 'Unkown') {
  switch (WP_ENV) {
    case 'accessnyc':
      $env = __('Production');
      break;
    case 'accessnycstage':
      $env = __('Staging');
      break;
    case 'accessnycdemo':
      $env = __('Demo');
      break;
    case 'accessnyctest':
      $env = __('Testing');
      break;
    case 'development':
      $env = __('Development');
      break;
  }

  return $env;
}

/**
 * Preloading fonts content with rel="preload"
 * Using case statements to manage different language fonts
 *
 * @source https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
 *
 * @param  String  $language  The code of the language ex: 'kr'
 */
function preload_fonts($lang) {
  $fonts = [
    'noto-serif/NotoSerif.woff2',
    'noto-sans/NotoSans-Italic.woff2',
    'noto-sans/NotoSans-Bold.woff2',
    'noto-sans/NotoSans-BoldItalic.woff2',
  ];

  switch ($lang) {
    case 'ko':
      $fonts = array_merge($fonts, [
        'noto-cjk-kr/NotoSansCJKkr-Regular.otf',
        'noto-cjk-kr/NotoSansCJKkr-Regular.otf'
      ]);

      break;

    case 'zh-hant':
      $fonts = array_merge($fonts, [
        'noto-cjk-tc/NotoSansCJKtc-Regular.otf',
        'noto-cjk-tc/NotoSansCJKtc-Bold.otf'
      ]);

      break;

    case 'ar':
      $fonts = array_merge($fonts, [
        'noto-ar/NotoNaskhArabic-Regular.ttf',
        'noto-ar/NotoNaskhArabic-Bold.ttf'
      ]);

      break;

    case 'ur':
      $fonts = array_merge($fonts, [
        'noto-ur/NotoNastaliqUrdu-Regular.ttf'
      ]);

      break;
  }

  add_action('wp_head', function() use ($fonts) {
    $preload_links = array_map(function($font_path) {
      $dir = '/' . str_replace(ABSPATH, '', get_template_directory())
        . '/assets/fonts/';

      return '<link rel="preload" href=' . $dir . $font_path . ' as="font" crossorigin>';
    }, $fonts);

    echo implode("\n", $preload_links);
  }, 2);
}

/**
 * Enqueue a hashed script based on it's name.
 * Enqueue the minified version based on debug mode.
 *
 * @param  String   $name  The name of the script source.
 * @param  Boolean  $cors  Add the crossorigin="anonymous" attribute.
 */
function enqueue_script($name, $cors = false) {
  if (!isset($GLOBALS['wp_assets'])) {
    $GLOBALS['wp_assets'] = new WpAssets();
  }

  $GLOBALS['wp_assets']->scripts = 'js/';

  $script = $GLOBALS['wp_assets']->addScript($name);

  if ($cors) {
    $GLOBALS['wp_assets']->addCrossoriginAttr($name);
  }
}

/**
 * Enqueue a hashed style based on it's name and language prefix.
 *
 * @param  String  $name  The name of the stylesheet source
 */
function enqueue_language_style($name) {
  if (!isset($GLOBALS['wp_assets'])) {
    $GLOBALS['wp_assets'] = new WpAssets();
  }

  $languages = array('ar', 'ko', 'ur', 'zh-hant');

  $lang = (!in_array(constant('ICL_LANGUAGE_CODE'), $languages))
    ? 'default' : constant('ICL_LANGUAGE_CODE');

  $style = $GLOBALS['wp_assets']->addStyle($name . '-' . $lang);
}

/**
 * Enqueue a client-side integration.
 *
 * @param   String   $name  Key of the integration in the mu-plugins/integrations.json
 *
 * @return  Boolean
 */
function enqueue_inline($name) {
  if (!isset($GLOBALS['wp_assets'])) {
    $GLOBALS['wp_assets'] = new WpAssets();
  }

  if (!isset($GLOBALS['wp_integrations'])) {
    $GLOBALS['wp_integrations'] = $GLOBALS['wp_assets']->loadIntegrations();
  }

  if ($GLOBALS['wp_integrations']) {
    $index = array_search($name, array_column($GLOBALS['wp_integrations'], 'handle'));

    $GLOBALS['wp_assets']->addInline($GLOBALS['wp_integrations'][$index]);

    return true;
  } else {
    return false;
  }
}

/**
 * Validate params through regex
 *
 * @param   String  $namespace  The namespace of the parameter
 * @param   String  $subject    The string to validate
 *
 * @return  String              Returns blank string if false, parameter if valid
 */
function validate_params($namespace, $subject) {
  $patterns = array(
    'programs' => '/^[A-Z0-9,]*$/',
    'categories' => '/^[a-z,-]*$/',
    'date' => '/^[0-9]*$/',
    'guid' => '/^[a-zA-Z0-9]{13,13}$/',
    'step' => '/^[a-z,-]*$/'
  );

  preg_match($patterns[$namespace], $subject, $matches);

  return (isset($matches[0])) ? $matches[0] : ''; // fail silently
}

/**
 * Expose urldecode() method to templates. Decodes URL-encoded string
 * @link https://www.php.net/manual/en/function.urldecode.php
 *
 * @param   String  $text  The string to be decoded
 *
 * @return  String         Decoded string
 */
function url_decode($text) {
    return urldecode($text);
}

/**
 * Creates a shareable url along with valid hash
 *
 * @param   Array  $params  Requires programs, categories, date, guid, share_link
 *
 * @return  Array           0; the url 1; the hash
 */
function share_data($params) {
  $query = array();
  $data = array();

  // Gets the URL Parameters for the search value,
  if (isset($params['programs'])) {
    $query['programs'] = validate_params(
      'programs', urldecode(htmlspecialchars($params['programs']))
    );
  }

  if (isset($params['categories'])) {
    $query['categories'] = validate_params(
      'categories', urldecode(htmlspecialchars($params['categories']))
    );
  }

  if (isset($params['date'])) {
    $query['date'] = validate_params(
      'date', urldecode(htmlspecialchars($params['date']))
    );
  }

  if (isset($params['guid'])) {
    $query['guid'] = validate_params(
      'guid', urldecode(htmlspecialchars($params['guid']))
    );
  }

  // Build query
  $http_query = http_build_query($query);
  $http_query = (isset($http_query)) ? '?'.$http_query : '';
  $url = home_url().$params['path'].$http_query;
  $hash = \SMNYC\hash($url);

  return array('url' => $url, 'hash' => $hash, 'query' => $query);
}

/**
 * Filter null/false values, convert encoding, and encode php to JSON
 *
 * @param   Array  $schema  PHP Array interpretation of schema
 *
 * @return  String          JSON encoded schema
 */
function encode_schema($schema) {
  $schema = array_values(array_filter($schema));
  $schema = mb_convert_encoding($schema, 'UTF-8', 'auto');
  $schema = json_encode($schema);

  return $schema;
}

/**
 * Determine if browser is IE
 *
 * @return  Boolean if the browser is IE
 */
function is_ie() {
  $IE = 'Trident';
  $IS_IE = strpos($_SERVER['HTTP_USER_AGENT'], $IE);
  return $IS_IE;
}
