<?php

namespace WPML\Core\Port\Endpoint;

interface EndpointInterface {


  /**
   * @param array<string,mixed>|null $requestData
   *
   * @return array<mixed, mixed>
   */
  public function handle( $requestData = null ): array;


}
