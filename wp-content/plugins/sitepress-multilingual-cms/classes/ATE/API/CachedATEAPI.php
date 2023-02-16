<?php

namespace WPML\TM\ATE\API;

use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\LIB\WP\Transient;
use WPML\FP\Obj;
use WPML\TM\ATE\API\CacheStorage\Storage;
use function WPML\FP\curryN;

class CachedATEAPI {

	const CACHE_OPTION = 'wpml-tm-ate-api-cache';

	/** @var  \WPML_TM_ATE_API */
	private $ateAPI;

	/** @var Storage */
	private $storage;

	private $cachedFns = [ 'get_languages_supported_by_automatic_translations', 'get_language_details', 'get_language_mapping' ];

	/**
	 * @param \WPML_TM_ATE_API $ateAPI
	 */
	public function __construct( \WPML_TM_ATE_API $ateAPI, Storage $storage ) {
		$this->ateAPI = $ateAPI;
		$this->storage = $storage;
	}

	public function __call( $name, $args ) {
		return Lst::includes( $name, $this->cachedFns ) ? $this->callWithCache( $name, $args ) : call_user_func_array( [ $this->ateAPI, $name ], $args );
	}

	private function callWithCache( $fnName, $args ) {
		$result = Obj::pathOr( null, [ $fnName, \serialize( $args ) ], $this->storage->get( self::CACHE_OPTION, [] ) );
		if ( ! $result ) {
			return call_user_func_array( [ $this->ateAPI, $fnName ], $args )->map( $this->cacheValue( $fnName, $args ) );
		}

		return Maybe::of( $result );

	}

	public function cacheValue( $fnName = null, $args = null, $result = null ) {
		$fn = curryN( 3, function ( $fnName, $args, $result ) {
			$data                                  = $this->storage->get( self::CACHE_OPTION, [] );
			$data[ $fnName ][ serialize( $args ) ] = $result;
			$this->storage->save( self::CACHE_OPTION, $data );

			return $result;
		} );

		return call_user_func_array( $fn, func_get_args() );
	}
}