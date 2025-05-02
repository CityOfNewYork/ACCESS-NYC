<?php

namespace WPML\Legacy\Component\Language\Application\Query;

use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\TM\API\ATE\CachedLanguageMappings;

class AutomaticTranslationsSupportInfoDecoratorForLanguagesQuery implements LanguagesQueryInterface {

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct( LanguagesQueryInterface $languagesQuery ) {
    $this->languagesQuery = $languagesQuery;
  }


  public function getDefaultCode(): string {
    return $this->languagesQuery->getDefaultCode();
  }


  public function getCurrentLanguageCode(): string {
    return $this->languagesQuery->getCurrentLanguageCode();
  }


  public function getDefault(): LanguageDto {
    $default = $this->languagesQuery->getDefault();

    return $this->addInfoAboutAutomaticTranslationsSupport( [ $default ] )[0];
  }


  public function getActive() {
    $active = $this->languagesQuery->getActive();

    return $this->addInfoAboutAutomaticTranslationsSupport( $active );
  }


  public function getSecondary( bool $withRespectToCurrentLang = false, $currentLang = null ) {
    $secondary = $this->languagesQuery->getSecondary( $withRespectToCurrentLang, $currentLang );

    return $this->addInfoAboutAutomaticTranslationsSupport( $secondary );
  }


  /**
   * @param LanguageDto[] $languages
   *
   * @return LanguageDto[]
   */
  private function addInfoAboutAutomaticTranslationsSupport( array $languages ): array {
    $languagesData = CachedLanguageMappings::getAllLanguagesWithAutomaticSupportInfo();

    if ( ! is_array( $languagesData ) ) {
      return [];
    }

    $languagesData = array_filter(
      $languagesData,
      function ( $languageData ) {
        return is_array( $languageData ) && isset( $languageData['can_be_translated_automatically'] );
      }
    );

    return array_map(
      function ( LanguageDto $language ) use ( $languagesData ) {
        $matchedLanguage = $languagesData[ $language->getCode() ] ?? null;
        if ( ! $matchedLanguage ) {
          return $language;
        }

        $doesSupport = $matchedLanguage['can_be_translated_automatically'];
        $language->setSupportsAutomaticTranslations( $doesSupport );

        return $language;
      },
      $languages
    );
  }


}
