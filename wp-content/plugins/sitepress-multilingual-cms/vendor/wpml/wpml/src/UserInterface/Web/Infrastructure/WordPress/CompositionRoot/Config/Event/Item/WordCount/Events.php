<?php

namespace WPML\UserInterface\Web\Infrastructure\WordPress\CompositionRoot\Config\Event\Item\WordCount;

use WPML\DicInterface;
use WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount\CalculateWordsInPackageListener;
use WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount\CalculateWordsInPostListener;
use WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount\CalculateWordsInStringListener;
use WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount\CountListener;
use WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount\OnPostSavedListener;
use WPML\UserInterface\Web\Infrastructure\WordPress\Events\Item\WordCount\OnStringRegisteredInPackageListener;

class Events {

  /** @var DicInterface */
  private $dic;

  /** @var OnStringRegisteredInPackageListener */
  private $onStringRegisteredInPackageListener;

  /** @var OnPostSavedListener */
  private $onPostSavedListener;


  public function __construct( DicInterface $dic ) {
    $this->dic = $dic;
    $this->register();
  }


  /**
   * @return void
   */
  public function register() {
    add_filter(
      'wpml_word_count_calculate_package',
      function ( $currentValue, $stringId ) {
        return $this->dic->make( CalculateWordsInPackageListener::class )
            ->calculate( $currentValue, $stringId );
      },
      10,
      2
    );

    add_filter(
      'wpml_word_count_calculate_string',
      function ( $currentValue, $stringId ) {
        return $this->dic->make( CalculateWordsInStringListener::class )
            ->calculate( $currentValue, $stringId );
      },
      10,
      2
    );

    add_filter(
      'wpml_word_count_calculate_post',
      function ( $currentValue, $stringId ) {
        return $this->dic->make( CalculateWordsInPostListener::class )
            ->calculate( $currentValue, $stringId );
      },
      10,
      2
    );

    add_filter(
      'wpml_word_count_chars',
      function ( $_, $content ) {
        return $this->dic->make( CountListener::class )
            ->onCalculateChars( (string) $content );
      },
      10,
      2
    );

    add_filter(
      'wpml_word_count_words',
      function ( $_, $content ) {
        return $this->dic->make( CountListener::class )
            ->onCalculateWords( (string) $content );
      },
      10,
      2
    );

    add_action(
      'save_post',
      function ( $postId, $post ) {
        $this->getOnPostSavedListener()->onPostSaved( $postId, $post );
      },
      10,
      2
    );

    add_action(
      'wpml_st_package_string_registered',
      function ( $package ) {
        if ( is_object( $package ) && isset( $package->ID ) ) {
          $this->getOnStringRegisteredInPackageListener()->registerPackage( $package->ID );
        }
      }
    );

    add_action(
      'shutdown',
      function () {
        $this->getOnStringRegisteredInPackageListener()->recalculatePackages();
        $this->getOnPostSavedListener()->process();
      }
    );
  }


  private function getOnStringRegisteredInPackageListener(): OnStringRegisteredInPackageListener {
    if ( $this->onStringRegisteredInPackageListener === null ) {
      $this->onStringRegisteredInPackageListener =
        $this->dic->make( OnStringRegisteredInPackageListener::class );
    }

    return $this->onStringRegisteredInPackageListener;
  }


  private function getOnPostSavedListener(): OnPostSavedListener {
    if ( $this->onPostSavedListener === null ) {
      $this->onPostSavedListener =
        $this->dic->make( OnPostSavedListener::class );
    }

    return $this->onPostSavedListener;
  }


}
