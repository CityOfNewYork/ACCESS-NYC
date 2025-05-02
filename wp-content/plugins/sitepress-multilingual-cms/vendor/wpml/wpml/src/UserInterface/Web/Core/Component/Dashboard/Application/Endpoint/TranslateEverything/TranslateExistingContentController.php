<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything;

use WPML\Core\Component\Translation\Application\Service\TranslateExistingContentService;
use WPML\Core\Port\Endpoint\EndpointInterface;

class TranslateExistingContentController implements EndpointInterface {

  /** @var TranslateExistingContentService */
  private $service;


  public function __construct( TranslateExistingContentService $service ) {
    $this->service = $service;
  }


  /**
   * @param array<string,mixed>|null $requestData
   *
   * @return array<mixed, mixed>
   */
  public function handle( $requestData = null ): array {
    /** @var string[] $postTypes */
    $postTypes    = $requestData['postTypes'] ?? [];
    /** @var string[] $packageTypes */
    $packageTypes = $requestData['packageTypes'] ?? [];

    $sanitize     = function ( string $type ): string {
      return htmlspecialchars( strip_tags( $type ) );
    };
    $postTypes    = array_map( $sanitize, $postTypes );
    $packageTypes = array_map( $sanitize, $packageTypes );

    $this->service->handle( $postTypes, $packageTypes );

    return [
      'success' => true,
    ];
  }


}
