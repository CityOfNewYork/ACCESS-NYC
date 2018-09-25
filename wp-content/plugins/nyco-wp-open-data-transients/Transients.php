<?php

namespace nyco\WpOpenDataTransients\Transients;

/**
 * Constants
 */

const TRANSIENTS_OPTION = 'open_data_transients_saved';
const TOKEN_OPTION = 'open_data_app_token';
const EXPIRATION = WEEK_IN_SECONDS; // WordPress Time Constant

/**
 * Functions
 */

/**
 * Gets a set transient if it exists, if it doesn't, it will set the transient
 * from the stored list of transients.
 * @param  [string] $name The name of the transient to retrieve
 * @return [string]       A JSON string representing the body of the request
 */
function get($name) {
  $transient = get_transient($name);

  if (empty($transient)) {
    $transient = set($name);
  }

  return $transient;
}

/**
 * Set the transient based on the url saved in the options database.
 * @param  [string] $name The name of the transient to retrieve
 * @return [string]       A JSON string representing the body of the request
 */
function set($name) {
  $option_transients = get_option(TRANSIENTS_OPTION, null);

  $option_token = TOKEN_OPTION;
  $token = get_option($option_token);
  $token = (!empty($token)) ? $url : $_ENV[strtoupper($option_token)];

  $transients = (null === $option_transients || $option_transients === '')
    ? [] : json_decode($option_transients, true);

  $key = array_search($name, array_column($transients, 'name'));

  // if key exists
  $url = $transients[$key]['url'];

  $body = wp_remote_retrieve_body(wp_remote_get($url, array(
    'headers' => array(
      'X-App-Token' => $token
    )
  )));

  set_transient($name, json_decode($body, true), EXPIRATION);

  return get_transient($name);
  // else {
  // return false;
  // }
}

/**
 * [delete description]
 * @return [type] [description]
 */
// function delete() {
//
// }
