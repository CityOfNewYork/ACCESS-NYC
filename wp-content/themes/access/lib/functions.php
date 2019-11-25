<?php

/**
 * Dependencies
 */

use NYCO\WpAssets as WpAssets;

/**
 * Return a localized reading friendly string of the enviroment.
 * @param  string $env The environment string to return if uknown.
 * @return string      The localized reading friendly string.
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
 * Enqueue a hashed script based on it's name.
 * Enqueue the minified version based on debug mode.
 * @param  [string]  $name The name of the script source.
 * @param  [boolean] $cors Add the crossorigin="anonymous" attribute.
 * @return null
 */
function enqueue_script($name, $cors = false) {
  $WpAssets = new WpAssets();
  $WpAssets->scripts = 'js/';

  $script = $WpAssets->addScript($name);

  if ($cors) {
    $WpAssets->addCrossoriginAttr($name);
  }
}

/**
 * Enqueue a hashed style based on it's name and language prefix.
 * @param  [string] $name the name of the stylesheet source
 * @return null
 */
function enqueue_language_style($name) {
  $WpAssets = new WpAssets();

  $languages = array('ar', 'ko', 'ur', 'zh-hant');
  $lang = (!in_array(ICL_LANGUAGE_CODE, $languages))
    ? 'default' : ICL_LANGUAGE_CODE;

  $style = $WpAssets->addStyle("$name-$lang");
}

/**
 * Enqueue a client-side integration.
 * @param  [string] $name Key of the integration in the mu-plugins/integrations.json
 * @return null
 */
function enqueue_inline($name) {
  $WpAssets = new WpAssets();
  $integrations = $WpAssets->loadIntegrations();

  if ($integrations) {
    $index = array_search($name, array_column($integrations, 'handle'));
    $WpAssets->addInline($integrations[$index]);
  }
}

/**
 * Validate params through regex
 * @param  string $namespace The namespace of the parameter
 * @param  string $subject   The string to validate
 * @return string            Returns blank string if false, parameter if valid
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
 * Creates a shareable url along with valid hash
 * @param  array $params Requires programs, categories, date, guid, share_link
 * @return array         0; the url 1; the hash
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
