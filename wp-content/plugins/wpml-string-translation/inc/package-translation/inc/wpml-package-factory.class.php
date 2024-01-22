<?php

class WPML_ST_Package_Factory {

	/** @var WPML_WP_Cache_Factory $cache_factory */
	private $cache_factory;

	public function __construct( WPML_WP_Cache_Factory $cache_factory = null ) {
		$this->cache_factory = $cache_factory;
		wp_cache_add_non_persistent_groups( __CLASS__ );
	}

	/**
	 * @param \stdClass|\WPML_Package|array|int $package_data
	 *
	 * @return WPML_Package
	 */
	public function create( $package_data ) {
		$cache_item = $this->get_cache_item( $package_data );

		if ( ! $cache_item->exists() ) {
			$cache_item->set( new WPML_Package( $package_data ) );
		}

		return $cache_item->get();
	}

	/**
	 * @param array|int|stdClass|WPML_Package $package_data
	 *
	 * @return WPML_WP_Cache_Item
	 */
	private function get_cache_item( $package_data ) {
		if ( ! $this->cache_factory ) {
			$this->cache_factory = new WPML_WP_Cache_Factory();
		}

		$package_key = md5( (string) json_encode( $package_data ) );

		return $this->cache_factory->create_cache_item( __CLASS__, $package_key );
	}
}
