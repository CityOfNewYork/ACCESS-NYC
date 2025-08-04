<?php

namespace Gravity_Forms\Gravity_Tools\Cache;

use Gravity_Forms\Gravity_Tools\Utils\Common;

/**
 *
 * Notes:
 * 1. The WordPress Transients API does not support boolean
 * values so boolean values should be converted to integers
 * or arrays before setting the values as persistent.
 *
 * 2. The transients API only deletes the transient from the database
 * when the transient is accessed after it has expired. WordPress doesn't
 * do any garbage collection of transients.
 *
 */
class Cache {

	/**
	 * @var Common
	 */
	protected $common;

	/**
	 * Constructor
	 *
	 * @param Common $common
	 */
	public function __construct( $common ) {
		$this->common = $common;
	}

	const KEY_CRON_EVENTS = 'cron_events_log';

	private static $_transient_prefix = 'GFCache_';
	private static $_cache            = array();

	/**
	 * Get a value from cache.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 * @param $found
	 * @param $is_persistent
	 *
	 * @return false|mixed|string|null
	 */
	public function get( $key, &$found = null, $is_persistent = true ) {
		global $blog_id;
		if ( is_multisite() ) {
			$key = $blog_id . ':' . $key;
		}

		if ( isset( self::$_cache[ $key ] ) ) {
			$found = true;
			$data  = $this->common->rgar( self::$_cache[ $key ], 'data' );

			return $data;
		}

		//If set to not persistent, do not check transient for performance reasons
		if ( ! $is_persistent ) {
			$found = false;

			return false;
		}

		$data = self::get_transient( $key );

		if ( false === ( $data ) ) {
			$found = false;

			return false;
		} else {
			self::$_cache[ $key ] = array( 'data' => $data, 'is_persistent' => true );
			$found                = true;

			return $data;
		}

	}

	/**
	 * Set a value in the cache.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 * @param $data
	 * @param $is_persistent
	 * @param $expiration_seconds
	 *
	 * @return bool
	 */
	public function set( $key, $data, $is_persistent = false, $expiration_seconds = 0 ) {
		global $blog_id;
		$success = true;

		if ( is_multisite() ) {
			$key = $blog_id . ':' . $key;
		}

		if ( $is_persistent ) {
			$success = self::set_transient( $key, $data, $expiration_seconds );
		}

		self::$_cache[ $key ] = array( 'data' => $data, 'is_persistent' => $is_persistent );

		return $success;
	}

	/**
	 * Delete a value from cache.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function delete( $key ) {
		global $blog_id;
		$success = true;

		if ( is_multisite() ) {
			$key = $blog_id . ':' . $key;
		}

		if ( isset( self::$_cache[ $key ] ) ) {
			if ( self::$_cache[ $key ]['is_persistent'] ) {
				$success = self::delete_transient( $key );
			}

			unset( self::$_cache[ $key ] );
		} else {
			$success = self::delete_transient( $key );

		}

		return $success;
	}

	/**
	 * FLush the cache.
	 *
	 * @since 1.0
	 *
	 * @param $flush_persistent
	 *
	 * @return bool
	 */
	public function flush( $flush_persistent = false ) {
		global $wpdb;

		self::$_cache = array();

		if ( false === $flush_persistent ) {
			return true;
		}

		if ( is_multisite() ) {
			$sql = $wpdb->prepare( "
                 DELETE FROM $wpdb->sitemeta
                 WHERE meta_key LIKE %s OR
                 meta_key LIKE %s
                ",
				'\_site\_transient\_timeout\_GFCache\_%',
				'_site_transient_GFCache_%'
			);
		} else {
			$sql = $wpdb->prepare( "
                 DELETE FROM $wpdb->options
                 WHERE option_name LIKE %s OR
                 option_name LIKE %s
                ",
				'\_transient\_timeout\_GFCache\_%',
				'_transient_GFCache_%'
			);
		}

		$rows_deleted = $wpdb->query( $sql );

		$success = $rows_deleted !== false ? true : false;

		return $success;
	}

	/**
	 * Delete a transient by its key.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 *
	 * @return false
	 */
	private function delete_transient( $key ) {
		if ( ! function_exists( 'wp_hash' ) ) {
			return false;
		}
		$key = self::$_transient_prefix . wp_hash( $key );
		if ( is_multisite() ) {
			$success = delete_site_transient( $key );
		} else {
			$success = delete_transient( $key );
		}

		return $success;
	}

	/**
	 * Set a transient by its key.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 * @param $data
	 * @param $expiration
	 *
	 * @return false
	 */
	private function set_transient( $key, $data, $expiration ) {
		if ( ! function_exists( 'wp_hash' ) ) {
			return false;
		}
		$key = self::$_transient_prefix . wp_hash( $key );
		if ( is_multisite() ) {
			$success = set_site_transient( $key, $data, $expiration );
		} else {
			$success = set_transient( $key, $data, $expiration );
		}

		return $success;
	}

	/**
	 * Get a transient by its key.
	 *
	 * @since 1.0
	 *
	 * @param $key
	 *
	 * @return false
	 */
	private function get_transient( $key ) {
		if ( ! function_exists( 'wp_hash' ) ) {
			return false;
		}
		$key = self::$_transient_prefix . wp_hash( $key );
		if ( is_multisite() ) {
			$data = get_site_transient( $key );
		} else {
			$data = get_transient( $key );
		}

		return $data;
	}

}
