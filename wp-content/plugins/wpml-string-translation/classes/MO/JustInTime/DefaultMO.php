<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\MO\JustInTime;

use WPML\ST\MO\LoadedMODictionary;

class DefaultMO extends MO {

	public function __construct( LoadedMODictionary $loaded_mo_dictionary, $locale ) {
		parent::__construct( $loaded_mo_dictionary, $locale, 'default' );
	}

	protected function loadTextDomain() {
		load_default_textdomain( $this->locale );
	}
}
