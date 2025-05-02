<?php

namespace WPML\UserInterface\Web\Infrastructure\CompositionRoot\Config;

use WPML\UserInterface\Web\Core\SharedKernel\Config\Endpoint\Endpoint;

interface ApiInterface {


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
  );


  public function getFullUrl( Endpoint $endpoint ): string;


  /**
   * @param ?string $name
   * @return string
   */
  public function nonce( $name = null ): string;


  public function validateRequest( string $capability ): bool;


  public function capabilityPlusAdmin( string $capability ): string;


  /**
   * @param array<mixed> $data
   * @return mixed
   */
  public function responseJsonSuccess( $data );


  /**
   * @param string $data
   * @return mixed
   */
  public function responseJsonError( $data );


  public function isRestRequest(): bool;


}
