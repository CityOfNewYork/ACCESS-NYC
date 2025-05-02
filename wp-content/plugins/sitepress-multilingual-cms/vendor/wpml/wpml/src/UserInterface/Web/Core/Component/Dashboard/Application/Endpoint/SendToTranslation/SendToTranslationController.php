<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\SendToTranslation;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\TranslationService;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-import-type ResultDtoArray from \WPML\Core\Component\Translation\Application\Service\TranslationService\Dto\ResultDto
 * @phpstan-import-type SendToTranslationDtoArray from \WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto
 */
class SendToTranslationController implements EndpointInterface {

  /** @var TranslationService */
  private $translationService;


  public function __construct( TranslationService $translationService ) {
    $this->translationService = $translationService;
  }


  /**
   * @phpstan-param  SendToTranslationDtoArray $requestData
   *
   * @phpstan-return  array{success: bool, data: ResultDtoArray|string}
   *
   * @psalm-suppress MoreSpecificImplementedParamType
   */
  public function handle( $requestData = null ): array {
    $requestData = $requestData ?: [];

    try {
      $sendToTranslationDto = SendToTranslationDto::fromArray( $requestData );

      $result = $this->translationService->send( $sendToTranslationDto );

      return [
        'success' => true,
        'data'    => $result->toArray()
      ];
    } catch ( InvalidArgumentException $e ) {
      return [
        'success' => false,
        'data'    => __(
          'The request data for SendToTranslation is not valid.',
          'wpml'
        )
      ];
    } catch ( TranslationService\TranslationServiceException $e ) {
      return [
        'success' => false,
        'data'    => $e->getMessage()
      ];
    } catch ( Exception $e ) {
      return [
        'success' => false,
        'data'    => __(
          'An error occurred while sending the translation.',
          'wpml'
        )
      ];
    }
  }


}
