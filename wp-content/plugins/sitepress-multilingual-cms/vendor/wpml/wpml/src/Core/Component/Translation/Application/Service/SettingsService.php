<?php

namespace WPML\Core\Component\Translation\Application\Service;

use WPML\Core\Component\Translation\Application\Repository\SettingsRepository;
use WPML\Core\Component\Translation\Application\Service\Event\SetTranslateEverythingEvent;
use WPML\Core\Component\Translation\Domain\Settings\ReviewMode;
use WPML\Core\Component\Translation\Domain\Settings\SettingsException;
use WPML\Core\Port\Event\DispatcherInterface;

class SettingsService {

  /** @var SettingsRepository */
  private $translationSettingsRepository;

  /** @var DispatcherInterface */
  private $eventDispatcher;


  public function __construct(
    SettingsRepository $translationSettingsRepository,
    DispatcherInterface $eventDispatcher
  ) {
    $this->translationSettingsRepository = $translationSettingsRepository;
    $this->eventDispatcher               = $eventDispatcher;
  }


  /**
   * @param ?string $reviewMode
   *
   * @return string
   */
  public function saveReviewOption( $reviewMode ) {
    $reviewMode = $reviewMode ? new ReviewMode( $reviewMode ) : ReviewMode::createDefault();

    $this->translationSettingsRepository->saveReviewMode( $reviewMode );

    return $reviewMode->getValue();
  }


  /**
   * @return void
   */
  public function enableATE() {
    $settings = $this->translationSettingsRepository->getSettings()->enableATE();
    $this->translationSettingsRepository->saveSettings( $settings );
  }


  /**
   * @param bool   $translateExisting
   * @param string $reviewOption
   *
   * @return void
   * @throws SettingsException
   */
  public function enableTranslateEverything( bool $translateExisting = false, string $reviewOption = null ) {
    $settings = $this->translationSettingsRepository->getSettings();

    if ( $settings->getTranslateEverything()->isEnabled() ) {
      return;
    }

    $settings = $settings->enableTranslateEverything(
      $reviewOption ? new ReviewMode( $reviewOption ) : null,
      $settings->getTranslationEditor()
    );

    $this->translationSettingsRepository->saveSettings( $settings );

    if ( $settings->getTranslateAutomaticallyPerPostType()->hasAnyAutomaticTranslationDisabled() ) {
      $this->translationSettingsRepository->deleteAutomaticPerPostTypeOption();
    }

    $reviewMode      = $settings->getReviewMode();
    $reviewModeValue = $reviewMode ? $reviewMode->getValue() : null;

    $this->eventDispatcher->dispatch(
      new SetTranslateEverythingEvent(
        true,
        [ 'translateExisting' => $translateExisting, 'reviewMode' => $reviewModeValue ]
      )
    );
  }


  /**
   * @return void
   */
  public function disableTranslateEverything() {
    $settings = $this->translationSettingsRepository->getSettings();
    if ( $settings->getTranslateEverything()->isEnabled() ) {
      $settings = $settings->disableTranslateEverything();
      $this->translationSettingsRepository->saveSettings( $settings );
      $this->eventDispatcher->dispatch( new SetTranslateEverythingEvent( false ) );
    }
  }


}
