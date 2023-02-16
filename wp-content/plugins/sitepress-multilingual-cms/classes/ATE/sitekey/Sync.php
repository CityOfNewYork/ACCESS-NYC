<?php

namespace WPML\TM\ATE\Sitekey;

use WPML\Core\BackgroundTask;
use WPML\LIB\WP\Hooks;
use WPML\WP\OptionManager;
use function WPML\FP\spreadArgs;

class Sync implements \IWPML_Backend_Action {

	public function add_hooks() {
		if ( ! OptionManager::getOr( false, 'TM-has-run', self::class ) && \WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			BackgroundTask::add( Endpoint::class );
		}

		$clearHasRun = function ( $repo ) {
			if ( $repo === 'wpml' ) {
				OptionManager::update( 'TM-has-run', self::class, false );
			}
		};
		Hooks::onAction( 'otgs_installer_site_key_update' )
		     ->then( spreadArgs( $clearHasRun ) );
	}
}
