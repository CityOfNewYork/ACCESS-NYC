<?php

namespace WPML\Core\SharedKernel\Component\Language\Application\Query;

use WPML\Core\SharedKernel\Component\Language\Application\Query\Dto\LanguageDto;


interface LanguagesQueryInterface {


  public function getDefaultCode(): string;


  public function getCurrentLanguageCode(): string;


  public function getDefault(): LanguageDto;


  /** @return LanguageDto[] */
  public function getActive();


  /**
   * @param bool $withRespectToCurrentLang
   * @param string|null $currentLang Allows to define the current language code when $withRespectToCurrentLang is true.
   *                                 I.e. this is used on the Dashboard, to pass the current "source language", which can
   *                                 differ from the current global (admin bar selected) language.
   *
   * @return LanguageDto[]
   */
  public function getSecondary( bool $withRespectToCurrentLang = false, $currentLang = null );


}
