<?php

/**
 * Transient Class
 */

namespace NYCO;

class Transients {
  const TRANSIENTS_OPTION = 'open_data_transients_saved';
  const TOKEN_OPTION = 'open_data_app_token';
  const EXPIRATION = WEEK_IN_SECONDS; // WordPress Time Constant
  const NAME_REGEX = '/^[A-Za-z_]*$/';

  /**
   * Gets a set transient if it exists, if it doesn't, it will set the transient
   * from the stored list of transients.
   * @param  [string] $name The name of the transient to retrieve
   * @return [string]       A JSON string representing the body of the request
   */
  public function get($name) {
    $transient = get_transient($name);

    if (empty($transient)) {
      $transient = self::set($name);
    }

    return $transient;
  }

  /**
   * Set the transient based on the url saved in the options database.
   * @param  [string] $name The name of the transient to retrieve
   * @return [string]       A JSON string representing the body of the request
   */
  public function set($name) {
    $option_transients = get_option(self::TRANSIENTS_OPTION, null);

    $option_token = self::TOKEN_OPTION;
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

    set_transient($name, json_decode($body, true), self::EXPIRATION);

    return get_transient($name);
  }

  /**
   * Save the transient to our saved list to be referenced by the set and get methods
   * @param  [string] $name The name of the transient
   * @param  [string] $url  The url of the transient
   * @return [object]       The saved transient
   */
  public function save($name, $url) {
    // Validate params
    $name = filter_var(
      $name, FILTER_VALIDATE_REGEXP,
      ['options' => ['regexp' => self::NAME_REGEX]]
    );

    $url = filter_var($url, FILTER_VALIDATE_URL);

    if (!$name || !$url) {
      echo 'Valid name (ex; "my_name" or "MY_NAME")
        and url (ex; "https://opendata.com/endpoint") are required.';
      exit;
    }

    $option = get_option(self::TRANSIENTS_OPTION, null);

    $transients = (null === $option || $option === '')
      ? [] : json_decode($option, true);

    $transient_new = array(
      'name' => $name,
      'url' => $url
    );

    $update = false;

    foreach ($transients as $i => $transient) {
      if ($transient['name'] === $transient_new['name']) {
        $transients[$i]['url'] = $transient_new['url'];
        $update = true;
      }
    }

    if (!$update) {
      array_push($transients, $transient_new);
    }

    // Save our list of transients
    update_option(self::TRANSIENTS_OPTION, json_encode($transients));

    return $transient_new;
  }
}
