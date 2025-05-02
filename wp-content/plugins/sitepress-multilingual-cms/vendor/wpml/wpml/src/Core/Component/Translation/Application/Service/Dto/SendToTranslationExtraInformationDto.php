<?php

namespace WPML\Core\Component\Translation\Application\Service\Dto;

use WPML\PHP\ConstructableFromArrayInterface;

/**
 * @implements ConstructableFromArrayInterface<SendToTranslationExtraInformationDto>
 *
 * @phpstan-type TranslationServiceExtraFieldsArray array<int, array{
 *   fieldType: string,
 *   fieldName: string,
 *   fieldValue: string
 * }>
 *
 * @phpstan-type SendToTranslationExtraInformationArray array{
 * howToHandleExistingTranslations?: string,
 * deadline?: string,
 * translationServiceExtraFields?: TranslationServiceExtraFieldsArray
 * }
 *
 */
class SendToTranslationExtraInformationDto implements ConstructableFromArrayInterface {

  /** @var string|null */
  private $deadline = null;

  /** @var string */
  private $howToHandleExistingTranslations;

  /** @var TranslationServiceExtraFieldsArray | null */
  private $translationServiceExtraFields;


  /**
   * @param string|null $deadline
   * @param string $howToHandleExistingTranslations
   *
   * @phpstan-param  TranslationServiceExtraFieldsArray $translationServiceExtraFields | null
   */
  public function __construct(
    string $howToHandleExistingTranslations,
    string $deadline = null,
    $translationServiceExtraFields = null
  ) {
    $this->deadline                        = $deadline;
    $this->howToHandleExistingTranslations = $howToHandleExistingTranslations;
    $this->translationServiceExtraFields   = $translationServiceExtraFields;
  }


  /**
   * @return string|null
   */
  public function getDeadline() {
    return $this->deadline;
  }


  public function getHowToHandleExistingTranslations(): string {
    return $this->howToHandleExistingTranslations;
  }


  /**
   * @return array|null
   *
   * @phpstan-return TranslationServiceExtraFieldsArray|null
   */
  public function getTranslationServiceExtraFields() {
    return $this->translationServiceExtraFields;
  }


  /**
   * @phpstan-param SendToTranslationExtraInformationArray $array
   *
   * @return SendToTranslationExtraInformationDto
   * @psalm-suppress MoreSpecificImplementedParamType
   */
  public static function fromArray( $array ): SendToTranslationExtraInformationDto {
    $deadline = ! isset( $array[ 'deadline' ] )
      ? null
      : $array[ 'deadline' ];

    $howToHandleExistingTranslations = ! isset( $array[ 'howToHandleExistingTranslations' ] )
      ? ''
      : $array[ 'howToHandleExistingTranslations' ];

    $translationServiceExtraFields = ! isset( $array[ 'translationServiceExtraFields' ] )
      ? null
      : $array[ 'translationServiceExtraFields' ];

    return new self( $howToHandleExistingTranslations, $deadline, $translationServiceExtraFields );
  }


}
