<?php

namespace FieldScreener;

class Util {
  /**
   * The translation ID for strings in the template
   *
   * @var String
   */
  const TRANSLATION_ID = 'field-screener';

  /**
   * Return a localized reading friendly string of the environment
   *
   * @param   String  $env  The environment string to return if unknown
   *
   * @return  String        The localized reading friendly string
   */
  public static function environmentString($env = 'Unknown') {
    switch (WP_ENV) {
      case 'accessnyc':
        $env = __('Production', self::TRANSLATION_ID);

        break;

      case 'accessnycs':
        $env = __('Staging', self::TRANSLATION_ID);

        break;

      case 'accessnycdemo':
        $env = __('Demo', self::TRANSLATION_ID);

        break;

      case 'accessnyctest':
        $env = __('Testing', self::TRANSLATION_ID);

        break;

      case 'development':
        $env = __('Development', self::TRANSLATION_ID);

        break;
    }

    return $env;
  }

  /**
   * Creates a shareable url along with valid hash
   *
   * @param   Array  $params  Requires programs, categories, date, guid, share_link
   *
   * @return  Array           0; the url 1; the hash
   */
  public static function shareData($params) {
    $query = array();
    $data = array();

    /**
     * Gets the URL Parameters for the search value
     */

    if (isset($params['programs'])) {
      $query['programs'] = Util::validateParams(
        'programs', urldecode(htmlspecialchars($params['programs']))
      );
    }

    if (isset($params['categories'])) {
      $query['categories'] = Util::validateParams(
        'categories', urldecode(htmlspecialchars($params['categories']))
      );
    }

    if (isset($params['date'])) {
      $query['date'] = Util::validateParams(
        'date', urldecode(htmlspecialchars($params['date']))
      );
    }

    if (isset($params['guid'])) {
      $query['guid'] = Util::validateParams(
        'guid', urldecode(htmlspecialchars($params['guid']))
      );
    }

    /**
     * Build query
     */

    $httpQuery = http_build_query($query);

    $httpQuery = (isset($httpQuery)) ? '?' . $httpQuery : '';

    $url = home_url() . $params['path'] . $httpQuery;

    /**
     * Create a token for the Send Me NYC plugin. The NONCE key prefix matches
     * the prefix used by the plugin.
     */

    $hash = wp_create_nonce('bsd_smnyc_token_' . $url);

    return array(
      'url' => $url,
      'hash' => $hash,
      'query' => $query
    );
  }

  /**
   * Validate params through regex
   *
   * @param   String  $namespace  The namespace of the parameter
   * @param   String  $subject    The string to validate
   *
   * @return  String              Returns blank string if false, parameter if valid
   */
  public static function validateParams($namespace, $subject) {
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
}
