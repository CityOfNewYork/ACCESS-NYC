<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\Endpoint;

class StringItemEndpointData {


  /**
   * @return array{ url: string }
   */
  public function getEndpointData(): array {
    return [
      'url'   => $this->getRestUrl( '/wpml/st/v1/strings' ),
    ];
  }


  /**
   * @return array{ url: string }
   */
  public function getStringPackagesEndpointData(): array {
    return [
      'url'   => $this->getRestUrl( '/wpml/st/v1/string-packages' ),
    ];
  }


  public function isStPluginActive(): bool {
    return class_exists( 'WPML_String_Translation' );
  }


  private function getRestUrl( string $path ): string {
    $restUrl = get_rest_url( null, $path );
    if ( get_option( 'permalink_structure' ) === '' ) {
        $restUrl = add_query_arg( 'rest_route', '/' . ltrim( $path, '/' ), home_url( '/' ) );
    }
    return $restUrl;
  }


}
