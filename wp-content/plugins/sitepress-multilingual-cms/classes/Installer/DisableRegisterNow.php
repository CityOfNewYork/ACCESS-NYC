<?php

namespace WPML\Installer;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class DisableRegisterNow implements \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'otgs_installer_display_subscription_notice' )
		     ->then( spreadArgs( function ( $notice ) {
			     return $notice['repo'] === 'wpml' ? false : $notice;
		     } ) );
	}
}
