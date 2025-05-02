<?php

namespace WPML\Core\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\Dto\TranslationBatchDto;

interface TranslationBatchesQueryInterface {


  public function getTotalCount (): int;


  /**
   * @param string $searchName
   *
   * @return TranslationBatchDto[]
   */
  public function getByNameStartsWith ( string $searchName ): array;


  /**
   * @return array<string, bool>
   */
  public function getNeedsReviewJobsBatchType(): array;


}
