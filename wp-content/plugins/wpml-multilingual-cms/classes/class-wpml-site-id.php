<?php

/**
 * Class for handling a unique ID of the site.
 *
 * @author OnTheGo Systems
 */
class WPML_Site_ID {
	/**
	 * The name prefix of the option where the ID is stored.
	 */
	const SITE_ID_KEY = 'WPML_SITE_ID';

	/**
	 * The default scope.
	 */
	const SITE_SCOPES_GLOBAL = 'global';

	/**
	 * Memory cache of the IDs.
	 *
	 * @var array
	 */
	private $site_ids = array();

	/**
	 * Read and, if needed, generate the site ID based on the scope.
	 *
	 * @param string $scope      Defaults to "global".
	 *                           Use a different value when the ID is used for specific scopes.
	 *
	 * @param bool   $create_new Forces the creation of a new ID.
	 *
	 * @return string|null The generated/stored ID or null if it wasn't possible to generate/store the value.
	 */
	public function get_site_id( $scope = self::SITE_SCOPES_GLOBAL, $create_new = false ) {
		$generate = ! $this->read_value( $scope ) || $create_new;
		if ( $generate && ! $this->generate_site_id( $scope ) ) {
			return null;
		}

		return $this->get_from_cache( $scope );
	}

	/**
	 * Geenrates the ID.
	 *
	 * @param string $scope The scope of the ID.
	 *
	 * @return bool
	 */
	private function generate_site_id( $scope ) {
		$site_url  = get_site_url();
		$site_uuid = uuid_v5( $site_url, wp_generate_uuid4() );
		$time_uuid = uuid_v5( time(), wp_generate_uuid4() );

		return $this->write_value( uuid_v5( $site_uuid, $time_uuid ), $scope );
	}

	/**
	 * Read the value from cache, if present, or from the DB.
	 *
	 * @param string $scope The scope of the ID.
	 *
	 * @return string
	 */
	private function read_value( $scope ) {
		if ( ! $this->get_from_cache( $scope ) ) {
			$this->site_ids[ $scope ] = get_option( $this->get_option_key( $scope ), null );
		}

		return $this->site_ids[ $scope ];
	}

	/**
	 * Writes the value in DB and cache.
	 *
	 * @param string $value The value to write.
	 * @param string $scope The scope of the ID.
	 *
	 * @return bool
	 */
	private function write_value( $value, $scope ) {
		if ( update_option( $this->get_option_key( $scope ), $value, false ) ) {
			$this->site_ids[ $scope ] = $value;

			return true;
		}

		return false;
	}

	/**
	 * Gets the options key name based on the scope.
	 *
	 * @param string $scope The scope of the ID.
	 *
	 * @return string
	 */
	private function get_option_key( $scope ) {
		return self::SITE_ID_KEY . ':' . $scope;
	}

	/**
	 * Gets the value from the memory cache.
	 *
	 * @param string $scope The scope of the ID.
	 *
	 * @return mixed|null
	 */
	private function get_from_cache( $scope ) {
		if ( array_key_exists( $scope, $this->site_ids ) && $this->site_ids[ $scope ] ) {
			return $this->site_ids[ $scope ];
		}

		return null;
	}
}
