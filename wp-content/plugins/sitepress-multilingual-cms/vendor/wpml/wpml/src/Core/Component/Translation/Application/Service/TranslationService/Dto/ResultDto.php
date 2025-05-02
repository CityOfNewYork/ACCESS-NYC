<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationService\Dto;

/**
 * @phpstan-import-type CreatedTranslationDtoArray from CreatedTranslationDto
 * @phpstan-import-type IgnoredElementDtoArray from IgnoredElementDto
 *
 * @phpstan-type ResultDtoArray array{
 *   createdTranslations: CreatedTranslationDtoArray[],
 *   ignoredElements: IgnoredElementDtoArray[]
 * }
 */
class ResultDto {

  /** @var CreatedTranslationDto[] */
  private $createdTranslations;

  /** @var IgnoredElementDto[] */
  private $ignoredElements;


  /**
   * @param CreatedTranslationDto[] $createdTranslations
   * @param IgnoredElementDto[] $ignoredElements
   */
  public function __construct( array $createdTranslations, array $ignoredElements ) {
    $this->createdTranslations = $createdTranslations;
    $this->ignoredElements     = $ignoredElements;
  }


  /**
   * @return CreatedTranslationDto[]
   */
  public function getCreatedTranslations(): array {
    return $this->createdTranslations;
  }


  /**
   * @return IgnoredElementDto[]
   */
  public function getIgnoredElements(): array {
    return $this->ignoredElements;
  }


  /**
   * @phpstan-return  ResultDtoArray
   */
  public function toArray(): array {
    return [
      'createdTranslations' => array_map(
        function ( CreatedTranslationDto $createdTranslationDto ) {
          return $createdTranslationDto->toArray();
        },
        $this->createdTranslations
      ),
      'ignoredElements'     => array_map(
        function ( IgnoredElementDto $ignoredElementDto ) {
          return $ignoredElementDto->toArray();
        },
        $this->ignoredElements
      ),
    ];
  }


}
