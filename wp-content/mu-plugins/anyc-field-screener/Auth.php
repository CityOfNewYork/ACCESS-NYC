<?php

namespace FieldScreener;

class Auth {
  /**
   * The key for creating and verifying the WordPress nonce
   *
   * @var String
   */
  const NONCE_KEY = 'field_screener_nonce';

  /**
   * Time, in seconds, for the application's nonce session life
   *
   * @var
   */
  const NONCE_LIFE = HOUR_IN_SECONDS;

  /**
   * Verify same origin - this should be enough to protect the endpoint from
   * outside access.
   *
   * @param   WP_REST_Request  $request  All arguments passed in from the request
   *
   * @return  Boolean                    Wether authentication passes
   */
  public static function sameOrigin($request) {
    $referer = $request->get_header('referer');

    $substr = substr($referer, 0, strlen(get_site_url()));

    $sameorigin = (get_site_url() === $substr);

    return $sameorigin;
  }

  /**
   * Verify the previous nonce before providing another
   *
   * @param   WP_REST_Request  $request  All arguments passed in from the request
   *
   * @return  Boolean                    Wether authentication passes
   */
  public static function bsdSmnycToken($request) {
    $params = $request->get_params();

    $nonce = $request->get_header('x-bsd-smnyc-token');

    /**
     * Verify the token for the Send Me NYC plugin. The NONCE key prefix matches
     * the prefix used by the plugin.
     */

    $verify = wp_verify_nonce($nonce, 'bsd_smnyc_token_' . $params['url']);

    return $verify;
  }

  /**
   * Verifies same origin, valid nonce, or logged in permissions.
   *
   * @param   WP_REST_Request  $request  All arguments passed in from the request
   *
   * @return  Mixed                      Boolean if auth fails or error
   *                                     describing inauth
   */
  public static function smnycToken($request) {
    $authSameorigin = self::sameOrigin($request);

    $authToken = self::bsdSmnycToken($request);

    if ($authToken || $authSameorigin) {
      return true;
    }

    if ('development' === WP_ENV) {
      return new \WP_Error(
        'forbidden',
        'Forbidden',
        array(
          'status' => 403,
          'sameorigin' => $auth_sameorigin,
          'token' => $auth_token
        )
      );
    } else {
      return false;
    }
  }

  /**
   * Set NONCE life to match the application client session timeout length
   *
   * @return  Number  Hours expressed by seconds, defined by WordPress time constants
   */
  public static function nonceLife() {
    return self::NONCE_LIFE;
  }
}
