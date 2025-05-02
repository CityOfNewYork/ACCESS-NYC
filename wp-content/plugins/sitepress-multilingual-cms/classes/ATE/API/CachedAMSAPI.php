<?php

namespace WPML\TM\ATE\API;

use WPML\FP\Lst;
use WPML\FP\Maybe;
use WPML\TM\ATE\API\CacheStorage\Storage;
use function WPML\FP\curryN;

class CachedAMSAPI {

	const CACHE_OPTION = 'wpml-tm-ams-api-cache';

	/** @var  \WPML_TM_AMS_API */
	private $amsApi;

	/** @var Storage */
	private $storage;

	private $cachedFns = [ 'getGlossaryCount' ];

	/**
	 * @param \WPML_TM_AMS_API $amsApi
	 */
	public function __construct( \WPML_TM_AMS_API $amsApi, Storage $storage ) {
		$this->amsApi  = $amsApi;
		$this->storage = $storage;
	}

	public function __call( $name, $args ) {
		return Lst::includes( $name, $this->cachedFns ) ? $this->callWithCache( $name, $args ) : call_user_func_array( [ $this->amsApi, $name ], $args );
	}

	private function callWithCache( $fnName, $args ) {
		$data = $this->storage->get( self::CACHE_OPTION, [] );
		$key  = $this->getKey( $args );

		if ( ! array_key_exists( $fnName, $data ) || ! array_key_exists( $key, $data[ $fnName ] ) ) {
			return call_user_func_array( [ $this->amsApi, $fnName ], $args )->map( $this->cacheValue( $fnName, $args ) );
		}

		return Maybe::of( $data[ $fnName ][ $key ] );

	}

	public function cacheValue( $functionName = null, $args = null, $result = null ) {
		$function = curryN( 3, function ( $functionName, $args, $result ) {
			$cachedData                                            = $this->storage->get( self::CACHE_OPTION, [] );
			$cachedData[ $functionName ][ $this->getKey( $args ) ] = $result;
			$this->storage->save( self::CACHE_OPTION, $cachedData );

			return $result;
		} );

		return call_user_func_array( $function, func_get_args() );
	}

	/**
	 * @param mixed $parameters
	 *
	 * @return string
	 */
	private function getKey( $parameters ) {
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		return \serialize( $parameters );
	}
}