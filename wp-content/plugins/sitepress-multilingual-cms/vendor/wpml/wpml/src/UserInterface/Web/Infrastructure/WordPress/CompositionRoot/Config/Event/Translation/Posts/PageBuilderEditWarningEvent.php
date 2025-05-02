<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\Event\Translation\Posts;

use WPML\DicInterface;
use WPML\UserInterface\Web\Core\Component\Notices\WarningTranslationEdit\Application\WarningTranslationEditController;

class PageBuilderEditWarningEvent {

  /** @var DicInterface */
  private $dic;

  /** @var WarningTranslationEditController|null */
  private $warningTranslationEditController;


  public function __construct( DicInterface $dic ) {
    $this->dic = $dic;
    $this->register();
  }


  /**
   * @return void
   */
  public function register() {
    /**
     * @psalm-suppress HookNotFound Custom hook 'wpml_maybe_display_modal_page_builder_warning'.
     */
    add_action(
      'wpml_maybe_display_modal_page_builder_warning',
      function( int $postId, string $pageBuilderName ) {
        $this->getWarningTranslationEditController()->maybeShowPageBuilderWarning( $postId, $pageBuilderName );
      },
      10,
      2
    );

  }


  private function getWarningTranslationEditController(): WarningTranslationEditController {

    if ( $this->warningTranslationEditController === null ) {
      $this->warningTranslationEditController = $this->dic->make(
        WarningTranslationEditController::class
      );
    }

    return $this->warningTranslationEditController;

  }


}
