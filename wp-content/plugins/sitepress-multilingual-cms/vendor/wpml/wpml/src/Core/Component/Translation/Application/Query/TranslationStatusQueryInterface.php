<?php

namespace WPML\Core\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Application\Query\Dto\TranslationStatusDto;

interface TranslationStatusQueryInterface {


  /**
   * @param int[] $jobIds
   * @param bool  $mapStringBatchesOnIndividualStrings Normally we return a TranslationStatusDto
   *    for each string batch. If this is set to true,
   *    we return a TranslationStatusDto for each string included in the batch.
   *
   * @return TranslationStatusDto[]
   */
  public function getByJobIds( array $jobIds, bool $mapStringBatchesOnIndividualStrings = false ): array;


}
