<?php

namespace WPML\UserInterface\Web\Core\Component\Preferences\Application;

use WPML\UserInterface\Web\Core\Port\Script\ScriptDataProviderInterface;
use WPML\UserInterface\Web\Core\Port\Script\ScriptPrerequisitesInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\PageRequirementsInterface;

class AutomaticTranslationsSectionController implements
  ScriptPrerequisitesInterface,
  PageRequirementsInterface,
  ScriptDataProviderInterface {

  /** @var AtePreferencesLoader */
  private $atePreferencesLoader;

  /** @var LanguagePreferencesLoader */
  private $languagePreferencesLoader;


  public function __construct(
    AtePreferencesLoader $atePreferencesLoader,
    LanguagePreferencesLoader $languagePreferencesLoader
  ) {
    $this->atePreferencesLoader      = $atePreferencesLoader;
    $this->languagePreferencesLoader = $languagePreferencesLoader;
  }


  /**
   * After migrating whole Settings into react, controller should implement PageRenderInterface
   * @return void
   */
  public static function render() {
    echo '<div id="automatic-translations-section"></div>';
  }


  public function scriptPrerequisitesMet(): bool {
    return $this->isOnMainSettingsTab();
  }


  public function requirementsMet(): bool {
    return $this->isOnMainSettingsTab();
  }


  public function jsWindowKey(): string {
    return 'wpmlScriptData';
  }


  public function initialScriptData(): array {
    $atePreferencesData      = $this->atePreferencesLoader->get();
    $languagePreferencesData = $this->languagePreferencesLoader->get();
    $otherData               = [];

    return array_merge(
      $atePreferencesData,
      $languagePreferencesData,
      $otherData
    );
  }


  private function isOnMainSettingsTab(): bool {
    // LEGACY: Legacy is using the GET parameter 'sm' to manage the tabs of the
    // settings page. This controller only handles the main settings page.
    return ! array_key_exists( 'sm', $_GET ) || $_GET['sm'] === 'mcsetup';
  }


}
