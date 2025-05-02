<?php

namespace WPML\Legacy\Component\Language\Application\Query;

use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

class LanguagesQuery implements LanguagesQueryInterface {

  const DEFAULT_LANGUAGE_CODE = 'en';

  /** @var \SitePress */
  private $sitepress;


  /**
   * @param \SitePress $sitepress Type only defined here to allow injecting.
   */
  public function __construct( $sitepress ) {
    $this->sitepress = $sitepress;
  }


  public function getDefaultCode(): string {
    return (string) $this->sitepress->get_default_language() ?: static::DEFAULT_LANGUAGE_CODE;
  }


  public function getCurrentLanguageCode(): string {
    return (string) $this->sitepress->get_current_language() ?: $this->getDefaultCode();
  }


  public function getDefault(): LanguageDto {
    /** @var array<string,string> $details */
    $details = $this->sitepress->get_language_details(
      $this->getDefaultCode()
    );

    return $this->buildLanguage( $details );
  }


  /**
   * @return LanguageDto[]
   */
  public function getActive(): array {
    $result = [];
    /** @var array<string,string>[] $languages */
    $languages = $this->sitepress->get_active_languages();

    foreach ( $languages as $language ) {
      $result[] = $this->buildLanguage( $language );
    }

    return $result;
  }


  /**
   * @param $withRespectToCurrentLang bool Set to <code>true</code> to get secondary languages excluding current language.
   *                                  Otherwise will exclude default language.
   * @param string $currentLang Allows to define the current language code when $withRespectToCurrentLang is true.
   *                            If not provided $this->getCurrentLanguageCode() will be used.
   *
   * @return LanguageDto[]
   */
  public function getSecondary( bool $withRespectToCurrentLang = false, $currentLang = null ): array {
    $defaultCode = $this->getDefaultCode();

    if ( $withRespectToCurrentLang ) {
      $defaultCode = $currentLang ?: $this->getCurrentLanguageCode();
    }

    return array_values(
      array_filter(
        $this->getActive(),
        function ( LanguageDto $language ) use ( $defaultCode ) {
          return $language->getCode() !== $defaultCode;
        }
      )
    );
  }


  /**
   * @param array<string,string> $details
   */
  private function buildLanguage( array $details ): LanguageDto {
    $result = new LanguageDto(
      $details['code'],
      $details['english_name'],
      $details['native_name']
    );

    /** @var string $flagUrl */
    $flagUrl = $this->sitepress->get_flag_url( $details['code'] );

    $result->setDisplayName( $details['display_name'] );
    $result->setCountryFlagUrl( $flagUrl );

    return $result;
  }


}
