<?php

namespace WPML\Legacy\Component\Translation\Sender\ErrorMapper;

use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

class UnsupportedLanguagesInTranslationService implements StrategyInterface {

  /** @var LanguagesQueryInterface */
  private $languageQuery;


  public function __construct( LanguagesQueryInterface $languageQuery ) {
    $this->languageQuery = $languageQuery;
  }


  /**
   * The expected error message should have following structure
   * "(3) This service does not support the following iso-codes: hy,he".
   * For each row in $errors, we must check if the error message matches the expected structure.
   * If it does, we must extract the unsupported language codes.
   * At the end, we need to return one message for all of them.
   *
   * @param array{type?: string, text?: string}[] $errors
   *
   * @return string|null
   */
  public function map( array $errors ) {
    $unsupportedLanguages = [];
    $pattern              = '/This service does not support the following iso-codes: (.*)/';
    foreach ( $errors as $error ) {
      if ( preg_match( $pattern, $error['text'] ?? '', $matches ) ) {
        $unsupportedLanguages = array_merge( $unsupportedLanguages, explode( ',', $matches[1] ) );
      }
    }

    if ( $unsupportedLanguages ) {
      $unsupportedLanguages = $this->getLanguageNames( $unsupportedLanguages );

      return sprintf(
        __( "The selected translation service doesn't support the following languages: %s", 'wpml' ),
        implode( ', ', $unsupportedLanguages )
      );
    }

    return null;
  }


  /**
   * @param string[] $languageCodes
   *
   * @return string[]
   */
  private function getLanguageNames( array $languageCodes ): array {
    $languages = $this->getActiveLanguagesGroupedByCode();

    return array_map(
      function ( string $languageCode ) use ( $languages ) {
        return $languages[ $languageCode ] ?? $languageCode;
      },
      $languageCodes
    );
  }


  /**
   * @return array<string, string>
   */
  private function getActiveLanguagesGroupedByCode(): array {
    $languages = $this->languageQuery->getActive();

    /** @var array<string, string> $result */
    $result = array_reduce(
      $languages,
      function ( $carry, LanguageDto $language ) {
        $carry[ $language->getCode() ] = $language->getDisplayName();

        return $carry;
      },
      []
    );

    return $result;
  }


}
