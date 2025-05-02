<?php

namespace WPML\Core\Component\Translation\Application\Service\TranslationBatchService;

use WPML\Core\Component\Translation\Application\Query\TranslationBatchesQueryInterface;

class BatchNamePreparer {

  /** @var TranslationBatchesQueryInterface */
  private $translationBatchesQuery;


  public function __construct ( TranslationBatchesQueryInterface $translationBatchesQuery ) {
    $this->translationBatchesQuery = $translationBatchesQuery;
  }


  public function prepare ( string $batchName ): string {
    $batchNameExistingRecords = $this->translationBatchesQuery->getByNameStartsWith(
      $batchName
    );

    return count( $batchNameExistingRecords )
      ? $batchName . '-' . ( count( $batchNameExistingRecords ) + 1 )
      : $batchName;
  }


}
