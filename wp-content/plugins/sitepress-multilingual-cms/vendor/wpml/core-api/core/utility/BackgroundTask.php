<?php

namespace WPML\Core;

use WPML\Core\WP\App\Resources;
use WPML\FP\Either;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;

/**
 * Class BackgroundTask
 * @package WPML\Core
 *
 * class to add background ajax tasks.
 * Call the `add` function with the class name of the endpoint and any data that the end point requires.
 * The Ajax endpoint will then be called on document ready with the data provided.
 */
class BackgroundTask implements \IWPML_Backend_Action {
	private static $endpoints = [];

	/**
	 * @param string     $endpoint - Class name of the handler usually implementing the IHandler interface
	 * @param null|array $data
	 */
	public static function add( $endpoint, $data = null ) {
		self::$endpoints[ $endpoint ] = $data;
	}

	public function add_hooks() {
		Hooks::onAction( 'wp_loaded' )
		     ->then( [ self::class, 'getEndpoints' ] )
		     ->then( [ self::class, 'localize' ] )
		     ->then( Resources::enqueueApp( 'backgroundTask' ) );
	}

	public static function getEndPoints() {
		return count( self::$endpoints )
			? Either::right( self::$endpoints )
			: Either::left( null );
	}

	public static function localize( $endpoints ) {
		return [
			'name' => 'wpml_background_task',
			'data' => [
				'endpoints'     => Obj::keys( $endpoints ),
				'endpointsData' => self::$endpoints,
			]
		];
	}
}
