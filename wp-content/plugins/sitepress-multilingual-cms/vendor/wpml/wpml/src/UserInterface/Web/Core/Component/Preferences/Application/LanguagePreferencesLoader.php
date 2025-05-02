<?php

namespace WPML\UserInterface\Web\Core\Component\Preferences\Application;

use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;

class LanguagePreferencesLoader {

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct( LanguagesQueryInterface $languagesQuery ) {
    $this->languagesQuery = $languagesQuery;
  }


  /**
   * @return array<string, array{
   *   code: string,
   *   name: string,
   *   flagUrl: string|null,
   * }>
   */
  private function getLanguages(): array {
    return array_reduce(
      $this->languagesQuery->getActive(),
      function ( array $carry, LanguageDto $language ) {
        $carry[ $language->getCode() ] = [
          'code'                             => $language->getCode(),
          'name'                             => $language->getDisplayName(),
          'flagUrl'                          => $language->getCountryFlagUrl(),
          'doesSupportAutomaticTranslations' => $language->doesSupportAutomaticTranslations(),
        ];

        return $carry;
      },
      []
    );
  }


  /**
   * @return string[]
   */
  private function getLanguagesTo(): array {
    return array_map(
      function ( LanguageDto $language ) {
        return $language->getCode();
      },
      $this->languagesQuery->getSecondary( true )
    );
  }


  /**
   * @return array{
   *   languages: array<string, array{
   *   code: string,
   *   name: string,
   *   flagUrl: string|null,
   * }>,
   *   languagesSettings: array{
   *   from: string,
   *   to: string[],
   *   default: string,
   * }
   * }
   */
  public function get(): array {
    return [
      'languages'         => $this->getLanguages(),
      'languagesSettings' => [
        'from'    => $this->languagesQuery->getCurrentLanguageCode(),
        'to'      => $this->getLanguagesTo(),
        'default' => $this->languagesQuery->getDefaultCode(),
      ]
    ];
  }


}
