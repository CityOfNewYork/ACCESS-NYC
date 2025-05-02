<?php

namespace WPML\UserInterface\Web\Core\Component\Dashboard\Application;

use WPML\Core\Port\Persistence\OptionsInterface;
use WPML\UserInterface\Web\Core\SharedKernel\Config\PageRequirementsInterface;

class DashboardRequirements implements PageRequirementsInterface {

  const SETUP_OPTIONS = 'WPML(setup)';

  /** @var ?bool */
  private $tmAllowed;

  /** @var OptionsInterface */
  private $options;


  public function __construct( OptionsInterface $options ) {
    $this->options = $options;
  }


  public function requirementsMet(): bool {
    if ( ! is_null( $this->tmAllowed ) ) {
      return $this->tmAllowed;
    }

    $wpmlSetup = $this->options->get( self::SETUP_OPTIONS );
    $this->tmAllowed = is_array( $wpmlSetup ) ? $wpmlSetup['is-tm-allowed'] : false;

    return $this->tmAllowed;
  }


}
