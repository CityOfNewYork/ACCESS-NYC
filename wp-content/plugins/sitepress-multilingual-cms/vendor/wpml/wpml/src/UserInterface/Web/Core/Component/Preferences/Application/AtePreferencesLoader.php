<?php

namespace WPML\UserInterface\Web\Core\Component\Preferences\Application;

use WPML\Core\Component\Translation\Application\Repository\SettingsRepository;

class AtePreferencesLoader {

  /** @var SettingsRepository */
  private $translationSettingsRepository;


  public function __construct( SettingsRepository $translationSettingsRepository ) {
    $this->translationSettingsRepository = $translationSettingsRepository;
  }


  /**
   * @return array{
   *   reviewMode: string|null,
   *   shouldTranslateAutomaticallyDrafts: bool,
   *   isAteEnabled: bool,
   *   useAteForOldTranslationsCreatedWithCte: bool,
   *   isTranslateEverythingEnabled: bool
   * }
   */
  public function get(): array {
    $settings   = $this->translationSettingsRepository->getSettings();
    $reviewMode = $settings->getReviewMode();

    $translationEditor = $this->translationSettingsRepository
        ->getSettings()
        ->getTranslationEditor();

    $isAteEnabled                           = $translationEditor && $translationEditor->getValue() === 'ATE';
    $useAteForOldTranslationsCreatedWithCte = $isAteEnabled &&
                                              $translationEditor->useAteForOldTranslationsCreatedWithCte();

    return [
      'reviewMode'                             => $reviewMode ? $reviewMode->getValue() : null,
      'shouldTranslateAutomaticallyDrafts'     =>
        $this->translationSettingsRepository->shouldTranslateAutomaticallyDrafts(),
      'isAteEnabled'                           => $isAteEnabled,
      'useAteForOldTranslationsCreatedWithCte' => $useAteForOldTranslationsCreatedWithCte,
      'isTranslateEverythingEnabled'           => $settings->getTranslateEverything()->isEnabled(),
    ];

  }


}
