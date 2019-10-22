<?php

namespace REST;

class Auth {
  /**
   * Verify same origin - this should be enough to protect the endpoint from
   * outside access.
   * @param   WP_REST_Request  $request  All arguments passed in from the request
   * @return  boolean                    Wether authentication passes
   */
  public static function sameOrigin($request) {
    $referer = $request->get_header('referer');
    $substr = substr($referer, 0, strlen(get_site_url()));
    $sameorigin = (get_site_url() === $substr);

    return $sameorigin;
  }

  /**
   * Verify the previous nonce before providing another
   * @param   WP_REST_Request  $request  All arguments passed in from the request
   * @return  boolean                    Wether authentication passes
   */
  public static function bsdSmnycToken($request) {
    $params = $request->get_params();
    $nonce = $request->get_header('x-bsd-smnyc-token');
    // The namespace and content for the bsd token is defined in the
    // Send Me NYC plugin
    $verify = wp_verify_nonce($nonce, 'bsd_smnyc_token_' . $params['url']);

    return $verify;
  }

  /**
   * Verifies same origin, valid nonce, or logged in permissions.
   * @param   WP_REST_Request  $request  All arguments passed in from the request
   * @return  mixed                      Boolean if auth fails or error
   *                                     describing inauth
   */
  public static function smnycToken($request) {
    $auth_sameorigin = self::sameOrigin($request);
    $auth_token = self::bsdSmnycToken($request);

    if ($auth_token || $auth_sameorigin) {
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
}
