<?php

namespace WPML\TM\ATE\Sitekey;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\Core\BackgroundTask\Model\BackgroundTask;
use WPML\FP\Either;
use WPML\BackgroundTask\AbstractTaskEndpoint;
use WPML\Core\BackgroundTask\Model\TaskEndpointInterface;
use WPML\Utilities\Lock;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class Endpoint extends AbstractTaskEndpoint implements IHandler, TaskEndpointInterface {
	const LOCK_TIME = 30;
	const MAX_RETRIES = 0;

	public function isDisplayed() {
		return false;
	}

	public function runBackgroundTask( BackgroundTask $task ) {
		if( function_exists( 'OTGS_Installer' ) ) {
			$sitekey = OTGS_Installer()->get_site_key( 'wpml' );
			if ( $sitekey && make( \WPML_TM_AMS_API::class )->send_sitekey( $sitekey ) ) {
				OptionManager::update( 'TM-has-run', Sync::class, true );
			}
		}
		$task->finish();
		return $task;
	}

	public function getTotalRecords( Collection $data ) {
		return 1;
	}

	public function getDescription( Collection $data ) {
		return __('Initializing AMS credentials.', 'sitepress');
	}
}
