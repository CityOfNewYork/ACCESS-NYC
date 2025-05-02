<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetTranslationBatchDefaultName;

use WPML\Core\Component\Translation\Application\Query\TranslationBatchesQueryInterface;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

class GetTranslationBatchDefaultName implements EndpointInterface {

  /** @var TranslationBatchesQueryInterface */
  private $translationBatchesQuery;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct (
    TranslationBatchesQueryInterface $translationBatchesQuery,
    LanguagesQueryInterface $languagesQuery
  ) {
    $this->translationBatchesQuery = $translationBatchesQuery;
    $this->languagesQuery          = $languagesQuery;
  }


  public function handle ( $requestData = null ): array {
    $translationBatchDefaultName = 'WPML|'
                                   . $this->languagesQuery->getDefaultCode()
                                   . '|';

    $translationBatchesByName = $this->translationBatchesQuery->getByNameStartsWith( $translationBatchDefaultName );

    $translationBatchDefaultName .= count( $translationBatchesByName ) + 1;

    return [
      'value' => $translationBatchDefaultName,
    ];
  }


}
