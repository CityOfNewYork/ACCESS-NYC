<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetTranslationStatus;

use WPML\Core\Component\Translation\Application\Query\Dto\TranslationStatusDto;
use WPML\Core\Component\Translation\Application\Query\TranslationStatusQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;


class GetTranslationStatusController implements EndpointInterface {

  /** @var TranslationStatusQueryInterface */
  private $translationStatusQuery;


  public function __construct( TranslationStatusQueryInterface $translationStatusQuery ) {
    $this->translationStatusQuery = $translationStatusQuery;
  }


  /**
   * @psalm-suppress MoreSpecificImplementedParamType
   * @psalm-suppress PossiblyNullReference
   *
   * @param int[] $requestData jobIds
   *
   * @return array<array{itemId: int, type: string, targetLanguage: string, status: int, reviewStatus: string|null}>
   */
  public function handle( $requestData = null ): array {
    $jobIds       = array_map( 'intval', $requestData ?: [] );
    $translations = $this->translationStatusQuery->getByJobIds( $jobIds, true );

    return array_map(
      function ( TranslationStatusDto $translation ) {
        return $translation->toArray();
      },
      $translations
    );
  }


}
