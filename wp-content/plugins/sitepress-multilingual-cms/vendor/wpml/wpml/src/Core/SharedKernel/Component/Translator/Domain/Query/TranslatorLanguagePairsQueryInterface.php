<?php

namespace WPML\Core\SharedKernel\Component\Translator\Domain\Query;

use WPML\Core\SharedKernel\Component\Translator\Domain\LanguagePair;

interface TranslatorLanguagePairsQueryInterface {


  /**
   * @param int $translatorId
   *
   * @return LanguagePair[]
   */
  public function getForSingleTranslator( int $translatorId ): array;


  /**
   * @param int[] $translatorsIds
   *
   * @return array<int, LanguagePair[]>
   */
  public function getForManyTranslators( array $translatorsIds ): array;


}
