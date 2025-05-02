<?php

namespace WPML\Core\Component\Translation\Application\Query;

use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationType;

interface TranslationQueryInterface {


  /**
   * @param int[] $jobIds
   *
   * @return Translation[]
   */
  public function getManyByJobIds( array $jobIds ): array;


  /**
   * @param int $jobId
   *
   * @return Translation|null
   */
  public function getOneByJobId( int $jobId );


  /**
   * @param int[] $translatedElementIds
   *
   * @return Translation[]
   */
  public function getManyByTranslatedElementIds( array $translatedElementIds ): array;


  /**
   * @param TranslationType $translationType
   * @param int[] $elementIds
   *
   * @return Translation[]
   */
  public function getManyByElementIds(
    TranslationType $translationType,
    array $elementIds
  ): array;


}
