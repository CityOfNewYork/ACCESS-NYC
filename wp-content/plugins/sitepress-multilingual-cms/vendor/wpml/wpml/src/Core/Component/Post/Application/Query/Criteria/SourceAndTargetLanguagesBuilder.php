<?php

namespace WPML\Core\Component\Post\Application\Query\Criteria;

use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

final class SourceAndTargetLanguagesBuilder {

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct( LanguagesQueryInterface $languagesQuery ) {
    $this->languagesQuery = $languagesQuery;
  }


  /**
   * @param string|null   $sourceLanguageCode
   * @param string[]|null $targetLanguageCodes
   *
   * @return SourceAndTargetLanguages
   */
  public function build(
    string $sourceLanguageCode = null,
    array $targetLanguageCodes = null
  ): SourceAndTargetLanguages {
    $source = $sourceLanguageCode ?? $this->languagesQuery->getDefaultCode();

    if ( $targetLanguageCodes === null ) {
      $targets = array_map(
        function ( $languageDto ) {
          return $languageDto->getCode();
        },
        $this->languagesQuery->getSecondary( true, $source )
      );
    } else {
      $targets = array_values(
        array_filter(
          $targetLanguageCodes,
          function ( $code ) use ( $source ) {
            return $code !== $source;
          }
        )
      );

      if ( empty( $targets ) ) {
        $targets = array_map(
          function ( $languageDto ) {
            return $languageDto->getCode();
          },
          $this->languagesQuery->getSecondary( true, $source )
        );
      }
    }

    /** @phpstan-ignore-next-line */
    return new SourceAndTargetLanguages( $source, $targets );
  }


}
