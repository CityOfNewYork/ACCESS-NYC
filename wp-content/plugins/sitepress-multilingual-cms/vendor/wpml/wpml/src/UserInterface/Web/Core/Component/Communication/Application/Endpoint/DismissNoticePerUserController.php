<?php

namespace WPML\UserInterface\Web\Core\Component\Communication\Application\Endpoint;

use WPML\Core\Component\Communication\Application\Service\DismissNoticeService;
use WPML\Core\Port\Endpoint\EndpointInterface;

class DismissNoticePerUserController implements EndpointInterface {

  /** @var DismissNoticeService */
  private $service;


  public function __construct( DismissNoticeService $service ) {
    $this->service = $service;
  }


  /**
   * @param array<string,mixed> $requestData
   *
   * @return array<string, mixed>
   */
  public function handle( $requestData = null ): array {
    if ( ! isset( $requestData['noticeId'] ) || ! isset( $requestData['userId'] ) ) {
      return [
        'success' => false,
        'message' => 'Notice ID and User ID are required',
      ];
    }

    $noticeId = is_string( $requestData['noticeId'] ) ? $requestData['noticeId'] : '';
    $noticeId = htmlspecialchars( strip_tags( $noticeId ) );

    $userId = is_numeric( $requestData['userId'] ) ? (int) $requestData['userId'] : 0;

    if ( empty( $noticeId ) || $userId <= 0 ) {
      return [
        'success' => false,
        'message' => 'Notice ID and User ID are required',
      ];
    }

    $this->service->dismissPerUser( $noticeId, $userId );

    return [
      'success' => true,
    ];
  }


}
