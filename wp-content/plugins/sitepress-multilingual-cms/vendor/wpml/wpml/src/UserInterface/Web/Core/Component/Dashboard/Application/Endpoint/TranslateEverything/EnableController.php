<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\TranslateEverything;

use WPML\Core\Component\Translation\Application\Repository\SettingsRepository;
use WPML\Core\Component\Translation\Application\Service\SettingsService;
use WPML\Core\Port\Endpoint\EndpointInterface;
use WPML\Core\SharedKernel\Component\Language\Application\Query\LanguagesQueryInterface;
use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorSetting;
use WPML\PHP\Exception\Exception;

class EnableController implements EndpointInterface {

  /** @var SettingsService */
  private $settingsService;

  /** @var SettingsRepository */
  private $translationSettingsRepository;

  /** @var LanguagesQueryInterface */
  private $languagesQuery;


  public function __construct(
    SettingsService $settingsService,
    SettingsRepository $translationSettingsRepository,
    LanguagesQueryInterface $languagesQuery
  ) {
    $this->settingsService               = $settingsService;
    $this->translationSettingsRepository = $translationSettingsRepository;
    $this->languagesQuery                = $languagesQuery;
  }


  public function handle( $requestData = null ): array {
    $translationEditor = $this->translationSettingsRepository
        ->getSettings()
        ->getTranslationEditor();

    if ( ! isset( $translationEditor ) || $translationEditor->getValue() !== TranslationEditorSetting::ATE ) {
      return [
        'success' => false,
        'error'   => [
          'code'    => 'ate_not_active',
          'message' => __(
            'In order to translate automatically, you first need to enable WPML\'s Advanced Translation Editor',
            'wpml'
          ),
        ],
      ];
    }

    if ( ! $this->languagesQuery->getDefault()->doesSupportAutomaticTranslations() ) {
      return [
        'success' => false,
        'error' => [
          'code' => 'default_language_does_not_support_automatic_translations',
          'message' => $this->languagesQuery->getDefault()->getDisplayName(),
        ],
      ];
    }

    $requestData = $requestData ?: [];

    $reviewMode = isset( $requestData['reviewMode'] ) && is_scalar( $requestData['reviewMode'] )
      ? (string) $requestData['reviewMode']
      : null;

    $translateExisting = (bool) ( $requestData['translateExistingContent'] ?? false );

    try {
      $this->settingsService->enableTranslateEverything( $translateExisting, $reviewMode );

      return [
        'success' => true,
      ];

    } catch ( Exception $e ) {
      return [
        'success' => false,
        'error' => [
          'code' => 'unexpected_error',
          'message' => $e->getMessage(),
        ],
      ];
    }

  }


}
