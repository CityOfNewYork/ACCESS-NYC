<?php

namespace WPML\TM\REST;

use IWPML_Deferred_Action_Loader;
use IWPML_REST_Action_Loader;
use WPML\TM\ATE\REST\Retry;
use WPML\TM\ATE\REST\Sync;
use \WPML\TM\ATE\REST\FixJob;
use WPML\TM\ATE\REST\Download;
use WPML\TM\ATE\REST\PublicReceive;
use function WPML\Container\make;

class FactoryLoader implements IWPML_REST_Action_Loader, IWPML_Deferred_Action_Loader {

	const REST_API_INIT_ACTION = 'rest_api_init';

	/**
	 * @return string
	 */
	public function get_load_action() {
		return self::REST_API_INIT_ACTION;
	}

	public function create() {
		return [
			Sync::class          => make( Sync::class ),
			Download::class      => make( Download::class ),
			Retry::class         => make( Retry::class ),
			PublicReceive::class => make( PublicReceive::class ),
			FixJob::class        => make( FixJob::class ),
		];
	}
}
