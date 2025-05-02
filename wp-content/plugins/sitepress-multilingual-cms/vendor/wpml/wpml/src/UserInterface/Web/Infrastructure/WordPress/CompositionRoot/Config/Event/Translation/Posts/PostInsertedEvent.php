<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\Event\Translation\Posts;

use WPML\DicInterface;
use WPML\Infrastructure\WordPress\Component\Translation\Application\Event\Posts\LanguageOfAutosavedDraftPostListener;

class PostInsertedEvent {

  /** @var DicInterface */
  private $dic;

  /** @var LanguageOfAutosavedDraftPostListener|null */
  private $setLanguageToAutosavedDraftPostListener;


  public function __construct( DicInterface $dic ) {
    $this->dic = $dic;
    $this->register();
  }


  /**
   * @return void
   */
  public function register() {
    add_action(
      'wp_after_insert_post',
      function ( $postId, $post, $update, $postBefore ) {
        $this->getSetLanguageToAutosavedDraftPostListener()->setLanguage( $postId, $post, $update, $postBefore );
      },
      10,
      4
    );
  }


  private function getSetLanguageToAutosavedDraftPostListener(): LanguageOfAutosavedDraftPostListener {
    if ( $this->setLanguageToAutosavedDraftPostListener === null ) {
      $this->setLanguageToAutosavedDraftPostListener = $this->dic->make(
        LanguageOfAutosavedDraftPostListener::class
      );
    }

    return $this->setLanguageToAutosavedDraftPostListener;
  }


}
