<?php
/**
 * Factory for Wp Settings
 *
 * @package WPML\ST
 */

namespace WPML\ST\WpSettings;

use IWPML_Action;
use function WPML\Container\make;

class Factory implements \IWPML_Backend_Action_Loader, \IWPML_Frontend_Action_Loader {

	/**
	 * Create hooks.
	 *
	 * @return IWPML_Action[]
	 */
	public function create() {
		return [
			make( DateTimeFormatsDefaultLocaleValues::class ),
		];
	}
}
