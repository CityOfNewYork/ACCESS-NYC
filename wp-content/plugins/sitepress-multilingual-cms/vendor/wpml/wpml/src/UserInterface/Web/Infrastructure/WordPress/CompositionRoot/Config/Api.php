<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;
use WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config\ApiInterface;

class Api implements ApiInterface {


  /**
   * @param callable $handle
   * @param callable $authorisation
   *
   * @return void
   */
  public function registerRoute(
    Endpoint $endpoint,
    $handle,
    $authorisation
  ) {

    if ( $endpoint->isAjax() ) {
      $this->registerAjaxEndpoint( $endpoint, $handle, $authorisation );
      return;
    }

    $this->registerRestEndpoint(
      $endpoint->namespaceWithVersion(),
      $endpoint->path(),
      $endpoint->method(),
      $handle,
      $authorisation
    );
  }


  /**
   * @param callable $handle
   * @param callable $authorisation
   *
   * @return void
   */
  private function registerAjaxEndpoint(
    Endpoint $endpoint,
    $handle,
    $authorisation
  ) {
    $routeWithoutSlashes = \str_replace( '/', '_', $endpoint->route() );

    add_action(
      'wp_ajax_wpml_api_' . $routeWithoutSlashes,
      function() use ( $handle ) {

        // We'll get the JSON data from the request body
        $json = file_get_contents( 'php://input' );

        // Parse the JSON data so we can use it
        $params = $json ? json_decode( $json, true ) : [];
        $params = is_array( $params ) ? $params : [];

        // Add the $_GET params
        $params = array_merge( $params, $_GET );

        /** @var \WP_REST_Response $jsonResponse */
        $jsonResponse = $handle( $params );

        http_response_code( $jsonResponse->status );
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
        echo \json_encode( $jsonResponse->data );

        wp_die();
      }
    );
  }


  /**
   * @return void
   */
  private function registerRestEndpoint(
    string $name,
    string $path,
    string $method,
    callable $handle,
    callable $authorisation
  ) {
    register_rest_route(
      $name,
      $path,
      [
        'methods' => $method,
        'callback' =>
        /** @return mixed */
        function( \WP_REST_Request $request ) use ( $handle ) {
          return $handle( $request->get_params() );
        },
        'permission_callback' => $authorisation,
      ]
    );
  }


  public function getFullUrl( Endpoint $endpoint ): string {
    $route = $endpoint->route();
    if ( $endpoint->isAjax() ) {
      $routeWithoutSlashes = \str_replace( '/', '_', $route );
      return admin_url( 'admin-ajax.php' ) . '?action=wpml_api_' . $routeWithoutSlashes;
    }
    $restUrl = get_rest_url( null, $route );
    if ( get_option( 'permalink_structure' ) === '' ) {
      $restUrl = add_query_arg( 'rest_route', '/' . ltrim( $route, '/' ), home_url( '/' ) );
    }
    return $restUrl;
  }


  public function nonce( $name = null ): string {
    $name = $name ?? 'wp_rest';

    return \wp_create_nonce( $name );
  }


  public function validateRequest( string $capability ): bool {
    return \current_user_can( $this->capabilityPlusAdmin( $capability ) );
  }


  public function capabilityPlusAdmin( string $capability ): string {
    if ( current_user_can( WPML_CAP_MANAGE_OPTIONS ) ) {
      // There is nothing on WPML which isn't allowed by Administrators.
      // This prevents people from being locked out of WPML functions when
      // they accidentally lose their WPML_CAP_MANAGE_TRANSLATIONS capability.
      // See wpmldev-4629.
      return WPML_CAP_MANAGE_OPTIONS;
    }

    return $capability;
  }


  /**
   * @param array<mixed> $data
   */
  public function responseJsonSuccess( $data ) {
      return \rest_ensure_response( new \WP_REST_Response( $data, 200 ) );
  }


  /**
   * @param string $data
   */
  public function responseJsonError( $data ) {
      return \rest_ensure_response( new \WP_REST_Response( $data, 500 ) );
  }


  public function isRestRequest(): bool {
    return defined( 'REST_REQUEST' );
  }


}
