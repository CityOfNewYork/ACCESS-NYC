<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config;

use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\DicInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;

class EndpointLoader {

  /** @var Endpoint $endpoint */
  private $endpoint;

  /** @var DicInterface $dic */
  private $dic;

  /** @var ApiInterface $api */
  private $api;

  /** @var bool $outputBufferActive */
  private $outputBufferActive = false;


  public function __construct(
    Endpoint $endpoint,
    DicInterface $dic,
    ApiInterface $api
  ) {
    $this->endpoint = $endpoint;
    $this->dic = $dic;
    $this->api = $api;
    $this->register();
  }


  /** @return void */
  public function register() {
    $this->api->registerRoute(
      $this->endpoint,
      [ $this, 'handle' ],
      [ $this, 'authorisation' ]
    );
  }


  /**
   * @param array<string,mixed> $params
   * @return mixed
   */
  public function handle( $params ) {
    $handlerString = $this->endpoint->handler();

    if ( ! $handlerString ) {
      $this->api->responseJsonError( 'Endpoint handler missing.' );
      return;
    }

    /** @var EndpointInterface $handler */
    $handler = $this->dic->make( $handlerString );

    try {
      // Start: Prevent any 3rd party output (called by filters, actions).
      // Because any output would destroy the json object in the response.
      // The most common output are notices, warnings, errors, etc. when
      // WP_DEBUG_DISPLAY is set to true.
      // This way we remove the php logs from the response, but they are still
      // in the error log. The user wouldn't see them on the screen anyway.
      $this->outputBufferActive = ob_start();

        // Get our response data.
        $result = $handler->handle( $params );

      // End: Flush all output created on the $handler->handle( $params ) call.
      $this->outputBufferActive && ob_end_clean();
      $this->outputBufferActive = false;

      return $this->api->responseJsonSuccess( $result );
    } catch ( \Throwable $e ) {
      // In case of an error we still need to flush the output buffer.
      $this->outputBufferActive && ob_end_clean();
      $this->outputBufferActive = false;

      return $this->api->responseJsonError( $e->getMessage() );
    }
  }


  /**
   * @return bool
   */
  public function authorisation() {
    return $this->api->validateRequest( $this->endpoint->capability() );
  }


}
