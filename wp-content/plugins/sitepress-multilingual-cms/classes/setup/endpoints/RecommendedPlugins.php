<?php


namespace WPML\Setup\Endpoint;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Obj;

class RecommendedPlugins implements IHandler {

	public function run( Collection $data ) {
		return Either::of( OTGS_Installer()->get_recommendations( 'wpml' ) );
	}
}
