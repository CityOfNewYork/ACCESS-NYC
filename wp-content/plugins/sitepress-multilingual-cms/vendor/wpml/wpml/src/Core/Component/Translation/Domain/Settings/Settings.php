<?php

namespace WPML\Core\Component\Translation\Domain\Settings;

use WPML\Core\SharedKernel\Component\Translation\Domain\TranslationEditorSetting;

class Settings {

  /** @var bool */
  private $isTMAllowed;

  /** @var ReviewMode|null */
  private $reviewMode;

  /** @var TranslationEditorSetting|null */
  private $translationEditor;

  /** @var TranslateEverything */
  private $translateEverything;

  /** @var TranslateAutomaticallyPerPostType */
  private $translateAutomaticallyPerPostType;


  public function __construct(
    bool $isTMAllowed,
    TranslateEverything $translateEverything,
    TranslateAutomaticallyPerPostType $translateAutomaticallyPerPostType,
    ReviewMode $reviewMode = null,
    TranslationEditorSetting $translationEditor = null
  ) {
    $this->isTMAllowed                       = $isTMAllowed;
    $this->translateEverything               = $translateEverything;
    $this->translateAutomaticallyPerPostType = $translateAutomaticallyPerPostType;
    $this->reviewMode                        = $reviewMode;
    $this->translationEditor                 = $translationEditor;
  }


  public function isTMAllowed(): bool {
    return $this->isTMAllowed;
  }


  /**
   * @return ReviewMode|null
   */
  public function getReviewMode() {
    return $this->reviewMode;
  }


  /**
   * @return TranslationEditorSetting|null
   */
  public function getTranslationEditor() {
    return $this->translationEditor;
  }


  public function getTranslateEverything(): TranslateEverything {
    return $this->translateEverything;
  }


  public function getTranslateAutomaticallyPerPostType(): TranslateAutomaticallyPerPostType {
    return $this->translateAutomaticallyPerPostType;
  }


  /**
   * @param ReviewMode|null               $reviewMode
   * @param TranslationEditorSetting|null $translationEditor
   *
   * @return Settings
   * @throws SettingsException
   */
  public function enableTranslateEverything(
    ReviewMode $reviewMode = null,
    TranslationEditorSetting $translationEditor = null
  ): self {
    if ( ! $this->isTMAllowed() ) {
      throw new SettingsException( 'TM is not allowed' );
    }

    if ( ! $reviewMode ) {
      // We have to have a review mode set. Therefore, if a user doesn't specify it, we set the default one.
      $reviewMode = $this->getReviewMode() ?: ReviewMode::createDefault();
    }

    if ( ! $translationEditor ) {
      $translationEditor = $this->getTranslationEditor() ?: TranslationEditorSetting::createDefault();
    }

    $translateEverything = clone $this->getTranslateEverything();
    $translateEverything->enable();

    return new self(
      $this->isTMAllowed(),
      $translateEverything,
      $this->getTranslateAutomaticallyPerPostType(),
      $reviewMode,
      $translationEditor
    );
  }


  public function disableTranslateEverything(): self {
    $translateEverything = clone $this->getTranslateEverything();
    $translateEverything->disable();

    return new self(
      $this->isTMAllowed(),
      $translateEverything,
      $this->getTranslateAutomaticallyPerPostType(),
      $this->reviewMode,
      $this->translationEditor
    );
  }


  public function enableATE(): self {
    return new self(
      $this->isTMAllowed(),
      $this->translateEverything,
      $this->getTranslateAutomaticallyPerPostType(),
      $this->reviewMode,
      new TranslationEditorSetting( TranslationEditorSetting::ATE )
    );
  }


}
