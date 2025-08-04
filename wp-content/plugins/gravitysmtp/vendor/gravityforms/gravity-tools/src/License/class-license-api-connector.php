<?php

namespace Gravity_Forms\Gravity_Tools\License;

use GFCommon;
use Gravity_Forms\Gravity_Tools\Utils\Common;
use WP_Error;
use Gravity_Forms\Gravity_Tools\License\License_API_Response_Factory;

/**
 * Class License_API_Connector
 *
 * Connector providing methods to communicate with the License API.
 *
 * @since   1.0
 *
 * @package Gravity_Forms\Gravity_Tools\License
 */
class License_API_Connector {

	private static $plugins;

	private static $license_info;

	/**
	 * @var \Gravity_Api $strategy
	 */
	protected $strategy;

	/**
	 * @var \GFCache $cache
	 */
	protected $cache;

	/**
	 * @var License_API_Response_Factory $response_factory
	 */
	protected $response_factory;

	/**
	 * @var Common
	 */
	protected $common;

	/**
	 * @var string
	 */
	protected $namespace;

	public function __construct( $strategy, $cache, License_API_Response_Factory $response_factory, $common, $namespace ) {
		$this->strategy         = $strategy;
		$this->cache            = $cache;
		$this->response_factory = $response_factory;
		$this->common           = $common;
		$this->namespace        = $namespace;
	}

	/**
	 * Check if cache debug is enabled.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_debug() {
		return defined( 'GF_CACHE_DEBUG' ) && GF_CACHE_DEBUG;
	}

	/**
	 * If the site was registered with the legacy process.
	 *
	 * @since 1.0
	 *
	 * @return bool
	 */
	public function is_legacy_registration() {
		return $this->strategy->is_legacy_registration();
	}

	/**
	 * Clear the cache for a given key.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 */
	public function clear_cache_for_key( $key ) {
		$this->cache->delete( 'rg_gforms_license_info_' . $key );
	}

	/**
	 * Get the license info.
	 *
	 * @since 1.0
	 *
	 * @param string $key
	 * @param bool   $cache
	 *
	 * @return License_API_Response
	 */
	public function check_license( $key = false, $cache = true ) {
		$license_info      = false;
		$key               = $key ? trim( $key ) : $this->strategy->get_key();
		$license_info_data = $this->cache->get( 'rg_gf_license_info_' . $key );

		if ( $this->is_debug() ) {
			$cache = false;
		}

		if ( $license_info_data && $cache ) {
			$license_info = $this->common->safe_unserialize( $license_info_data, License_API_Response::class );
			if ( $license_info ) {
				return $license_info;
			} else {
				$this->clear_cache_for_key( $key );
			}
		}

		$license_info = $this->response_factory->create(
			$this->strategy->check_license( $key ),
			false
		);

		if ( $license_info->can_be_used() ) {
			$this->cache->set( 'rg_gf_license_info_' . $key, serialize( $license_info ), true, DAY_IN_SECONDS );
		}

		return $license_info;
	}

	/**
	 * Get the license and plugins information.
	 *
	 * @since 1.0
	 *
	 * @param bool $cache If we should use the cached data.
	 *
	 * @return array|null
	 */
	public function get_version_info( $cache = true ) {
		if ( ! is_null( self::$plugins ) && $cache ) {
			$plugins      = self::$plugins;
			$license_info = self::$license_info;
		} else {
			$plugins            = $this->get_plugins( $cache );
			$license_info       = $this->common->get_key() ? $this->check_license( false, $cache ) : new WP_Error( License_Statuses::NO_DATA );
			self::$plugins      = $plugins;
			self::$license_info = $license_info;
		}

		return array(
			'is_valid_key' => ! is_wp_error( $license_info ) && $license_info->can_be_used(),
			'reason'       => $license_info->get_error_message(),
			'version'      => $this->common->rgars( $plugins, 'gravityforms/version' ),
			'url'          => $this->common->rgars( $plugins, 'gravityforms/url' ),
			'is_error'     => is_wp_error( $license_info ) || $license_info->has_errors(),
			'offerings'    => $plugins,
		);
	}

	/**
	 * Check if the saved license key is valid.
	 *
	 * @since 1.0
	 *
	 * @return true|WP_Error
	 */
	public function is_valid_license() {
		$license_info = $this->check_license();

		return $license_info->is_valid();
	}

	/**
	 * Registers a site to the specified key, or if $new_key is blank, unlinks a key from an existing site.
	 * Requires that the $new_key is saved in options before calling this function
	 *
	 * @since 1.0 Implement the license enforcement process.
	 *
	 * @param string $new_key Unhashed Gravity Forms license key.
	 *
	 * @return GF_License_API_Response
	 */
	public function update_site_registration( $new_key, $is_md5 = false ) {
		// Get new license key information.
		$version_info = $this->common->get_version_info( false );

		if ( $version_info['is_valid_key'] ) {
			$data = $this->strategy->check_license( $new_key );

			$result = $this->response_factory->create( $data );
		} else {

			// Invalid key, do not change site registration.
			$error = new WP_Error( License_Statuses::INVALID_LICENSE_KEY, License_Statuses::get_message_for_code( License_Statuses::INVALID_LICENSE_KEY ) );

			$result = $this->response_factory->create( $error );
		}

		return $result;
	}

	/**
	 * Purge site credentials if the license info contains certain errors.
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function maybe_purge_site_credentials() {

		// Check if the license info contains the revoke site error.
		$license_info = $this->check_license();

		$errors = array(
			'gravityapi_site_revoked',
			'gravityapi_fail_authentication',
			'gravityapi_site_url_changed',
		);

		if ( is_wp_error( $license_info ) && in_array( $license_info->get_error_code(), $errors, true ) ) {
			// Purge site data to ensure we can get a fresh start.
			$this->strategy->purge_site_credentials();
		}

	}

	/**
	 * Retrieve a list of plugins from the API.
	 *
	 * @since 1.0
	 *
	 * @param bool $cache Whether to respect the cached data.
	 *
	 * @return mixed
	 */
	public function get_plugins( $cache = true ) {
		$cache_key = sprintf( '%s_gforms_plugins', $this->namespace );
		$plugins = $this->cache->get( $cache_key, $found_in_cache );

		if ( $this->is_debug() ) {
			$cache = false;
		}

		if ( $found_in_cache && $cache ) {
			return $plugins;
		}

		$plugins = $this->strategy->get_plugins_info();

		$this->cache->set( $cache_key, $plugins, true, DAY_IN_SECONDS );

		return $plugins;
	}
}
