<?php

namespace WPML\TM\ATE\Sitekey;

use WPML\Core\BackgroundTask\Service\BackgroundTaskService;
use WPML\LIB\WP\Hooks;
use WPML\WP\OptionManager;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class Sync implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	/** @var BackgroundTaskService */
	private $backgroundTaskService;

	/**
	 * @param BackgroundTaskService $backgroundTaskService
	 */
	public function __construct( BackgroundTaskService $backgroundTaskService ) {
		$this->backgroundTaskService = $backgroundTaskService;
	}


	public function add_hooks() {
		if ( ! OptionManager::getOr( false, 'TM-has-run', self::class ) && \WPML_TM_ATE_Status::is_enabled_and_activated() ) {
			$this->backgroundTaskService->addOnce(
				make( Endpoint::class ),
				wpml_collect( [] )
			);
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
