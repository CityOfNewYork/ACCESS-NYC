<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\ValidateSelectedTranslationMethods;

use WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto;
use WPML\Core\Component\Translation\Application\Service\Validator\AssignedTranslationServiceValidatorService;
use WPML\Core\Component\Translation\Application\Service\Validator\AssignedTranslatorsValidatorService;
use WPML\Core\Component\Translation\Application\Service\Validator\Dto\ValidationResultDto;
use WPML\Core\Component\Translation\Application\Service\Validator\TranslationEditorValidatorService;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\TranslationProxy\Domain\Query\FetchRemoteTranslationServiceException;
use WPML\PHP\Exception\Exception;
use WPML\PHP\Exception\InvalidArgumentException;

/**
 * @phpstan-import-type SendToTranslationDtoArray from \WPML\Core\Component\Translation\Application\Service\Dto\SendToTranslationDto
 */
class ValidateSelectedTranslationMethodsController implements EndpointInterface {

  /** @var AssignedTranslatorsValidatorService */
  private $assignedTranslatorsValidatorService;

  /** @var AssignedTranslationServiceValidatorService */
  private $assignedTranslationServiceValidatorService;

  /** @var TranslationEditorValidatorService */
  private $translationEditorValidatorService;


  public function __construct(
    AssignedTranslatorsValidatorService $assignedTranslatorsValidatorService,
    AssignedTranslationServiceValidatorService $assignedTranslationServiceValidatorService,
    TranslationEditorValidatorService $translationEditorValidatorService
  ) {
    $this->assignedTranslatorsValidatorService        = $assignedTranslatorsValidatorService;
    $this->assignedTranslationServiceValidatorService = $assignedTranslationServiceValidatorService;
    $this->translationEditorValidatorService          = $translationEditorValidatorService;
  }


  /**
   * @phpstan-param  SendToTranslationDtoArray $requestData
   *
   * @phpstan-return  array<array{
   *   type: string,
   *   valid: bool
   * }>
   *
   * @throws Exception Some system related error.
   * @psalm-suppress MoreSpecificImplementedParamType
   */
  public function handle( $requestData = null ): array {
    $requestData       = $requestData ?: [];
    $validationResults = [];

    try {
      $sendToTranslationDto = SendToTranslationDto::fromArray( $requestData );

      // Validate the assigned local translators (For the LocalTranslator methods)
      $validationResults[] = $this->assignedTranslatorsValidatorService->validate(
        $sendToTranslationDto
      )->toArray();

      // Validate the assigned translation service (For the TranslationService methods)
      $validationResults[] = $this->assignedTranslationServiceValidatorService->validate(
        $sendToTranslationDto
      )->toArray();

      // Validate the current active translation editor (For the Automatic methods)
      $validationResults[] = $this->translationEditorValidatorService->validate(
        $sendToTranslationDto
      )->toArray();

    } catch ( InvalidArgumentException $e ) {
      // return error if InvalidArgumentException is caught
      $validationResults[] = ( new ValidationResultDto( 'invalid-argument', false ) )->toArray();
    } catch ( FetchRemoteTranslationServiceException $e ) {
      // return error if FetchRemoteTranslationServiceException is caught
      $validationResults[] = ( new ValidationResultDto(
        $this->assignedTranslationServiceValidatorService->getType(),
        false
      ) )->toArray();
    }

    return $validationResults;
  }


}
