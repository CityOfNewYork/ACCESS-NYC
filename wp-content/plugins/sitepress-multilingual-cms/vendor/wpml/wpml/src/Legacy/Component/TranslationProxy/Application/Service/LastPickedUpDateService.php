<?php

namespace WPML\Legacy\Component\TranslationProxy\Application\Service;

use WPML\Core\Component\TranslationProxy\Application\Service\LastPickedUpDateServiceInterface;

class LastPickedUpDateService implements LastPickedUpDateServiceInterface {

  /** @var \WPML_TM_Last_Picked_Up */
  private $legacyLastPickedUp;


  public function __construct( \WPML_TM_Last_Picked_Up $legacyLastPickedUp ) {
    $this->legacyLastPickedUp = $legacyLastPickedUp;
  }


  public function get() {
    $lastPickup = $this->legacyLastPickedUp->get();

    return is_numeric( $lastPickup ) ? (int) $lastPickup : null;
  }


}
