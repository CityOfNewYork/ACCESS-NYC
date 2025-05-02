<?php

namespace WPML\Core\Component\Translation\Domain\Links;

/**
 * The implementation of the actual link adjustment is still in the legacy code.
 */
interface AdjustLinksInterface {


  /** @return void */
  public function adjust( Item $item, Item $triggerItem = null );


}
