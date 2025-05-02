<?php

namespace WPML\Core\Component\Translation\Application\Repository;

use WPML\Core\Component\Translation\Domain\Translation;
use WPML\Core\Component\Translation\Domain\TranslationType;

interface TranslationRepositoryInterface {


  /**
   * @param TranslationType $itemType
   * @param string          $elementType
   * @param int             $elementId
   *
   * @return Translation
   * @throws TranslationNotFoundException
   */
  public function get( TranslationType $itemType, string $elementType, int $elementId ): Translation;


  /**
   * @param TranslationType $itemType
   * @param string          $elementType
   * @param int             $elementId
   * @param string          $languageCode
   * @param string|null     $sourceLanguageCode
   * @param int|null        $trid
   *
   * @return void
   */
  public function saveElementLanguage(
    TranslationType $itemType,
    string $elementType,
    int $elementId,
    string $languageCode,
    string $sourceLanguageCode = null,
    int $trid = null
  );


}
