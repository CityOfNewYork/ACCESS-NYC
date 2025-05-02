<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application\Endpoint\GetTranslationEditorType;

use WPML\Core\Component\Translation\Application\Repository\SettingsRepository;
use WPML\Core\Port\Endpoint\EndpointInterface;

class GetTranslationEditorTypeController implements EndpointInterface {

  /** @var SettingsRepository */
  private $translationSettingsRepository;


  public function __construct( SettingsRepository $translationSettingsRepository ) {
    $this->translationSettingsRepository = $translationSettingsRepository;
  }


  public function handle( $requestData = null ): array {
    $translationEditor = $this->translationSettingsRepository
        ->getSettings()
        ->getTranslationEditor();

    return [
      'translationEditorType' => $translationEditor ?
        $translationEditor->getValue() :
        'CTE'
    ];
  }


}
