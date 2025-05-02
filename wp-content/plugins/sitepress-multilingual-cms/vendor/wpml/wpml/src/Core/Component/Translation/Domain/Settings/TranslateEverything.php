<?php

namespace WPML\Core\Component\Translation\Domain\Settings;

class TranslateEverything {

  /** @var bool */
  private $isEnabled;

  /** @var bool */
  private $hasEverBeenEnabled;

  /**
   * @var array<string, string[]> $completedPosts e.g ['post' => ['fr', 'de'], 'page' => ['fr']]
   */
  private $completedPosts = [];

  /**
   * @var array<string, string[]> $completedPackages e.g ['gravity form' => ['fr', 'de'], 'block' => ['fr']]
   */
  private $completedPackages = [];

  /**
   * @var string[] $completedStrings e.g ['fr', 'de']
   */
  private $completedStrings = [];


  public function __construct( bool $isEnabled, bool $hasEverBeenEnabled = false ) {
    $this->isEnabled          = $isEnabled;
    $this->hasEverBeenEnabled = $hasEverBeenEnabled;
  }


  public function isEnabled(): bool {
    return $this->isEnabled;
  }


  public function enable(): self {
    $this->isEnabled          = true;
    $this->hasEverBeenEnabled = true;

    return $this;
  }


  public function disable(): self {
    $this->isEnabled = false;

    return $this;
  }


  public function hasEverBeenEnabled(): bool {
    return $this->hasEverBeenEnabled;
  }


  /**
   * @return array<string, string[]>
   */
  public function getCompletedPackages(): array {
    return $this->completedPackages;
  }


  /**
   * @param array<string, string[]> $completedPackages
   *
   * @return void
   */
  public function setCompletedPackages( array $completedPackages ) {
    $this->completedPackages = $completedPackages;
  }


  /**
   * @param string[] $packageTypes
   *
   * @return void
   */
  public function removeCompletedPackages( array $packageTypes ) {
    foreach ( $packageTypes as $packageType ) {
      unset( $this->completedPackages[ $packageType ] );
      unset( $this->completedPackages[ ucfirst( $packageType ) ] );
    }
  }


  /**
   * @return array<string, string[]>
   */
  public function getCompletedPosts(): array {
    return $this->completedPosts;
  }


  /**
   * @param array<string, string[]> $completedPosts
   *
   * @return void
   */
  public function setCompletedPosts( array $completedPosts ) {
    $this->completedPosts = $completedPosts;
  }


  /**
   * @param string[] $postTypes
   *
   * @return void
   */
  public function removeCompletedPosts( array $postTypes ) {
    foreach ( $postTypes as $postType ) {
      unset( $this->completedPosts[ $postType ] );
    }
  }


  /**
   * @return string[]
   */
  public function getCompletedStrings(): array {
    return $this->completedStrings;
  }


  /**
   * @param string[] $completedStrings
   *
   * @return void
   */
  public function setCompletedStrings( array $completedStrings ) {
    $this->completedStrings = $completedStrings;
  }


}
