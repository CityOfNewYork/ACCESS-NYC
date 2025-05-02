<?php

namespace WPML\Core\Component\Translation\Application\Event\Links;

use WPML\Core\Component\Translation\Domain\Links\HandleUpdateOriginal;
use WPML\Core\Component\Translation\Domain\Links\HandleUpdateTranslation;
use WPML\Core\Component\Translation\Domain\Links\Item;
use WPML\Core\Port\Event\EventListenerInterface;
use WPML\PHP\Exception\InvalidTypeException;

class ItemUpdateListener implements EventListenerInterface {

  /** @var HandleUpdateOriginal */
  private $handleOriginal;

  /** @var HandleUpdateTranslation */
  private $handleTranslation;


  public function __construct(
    HandleUpdateOriginal $handleOriginal,
    HandleUpdateTranslation $handleTranslation
  ) {
    $this->handleOriginal = $handleOriginal;
    $this->handleTranslation = $handleTranslation;
  }


  /** @return void */
  public function onItemSave( Item $item ) {
    try {
      $this->handleOriginal->handle( $item );
      $this->handleTranslation->handle( $item );
    } catch ( InvalidTypeException $e ) {
      // CMS error - some type name has changed.
      return;
    }
  }


}
