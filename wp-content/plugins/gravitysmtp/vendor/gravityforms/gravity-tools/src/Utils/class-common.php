<?php

namespace Gravity_Forms\Gravity_Tools\Utils;

use Gravity_Forms\Gravity_Tools\License\License_API_Connector;
use Gravity_Forms\Gravity_Tools\License\License_Statuses;

class Common {

	private static $plugins;

	protected $gravity_manager_url;
	protected $support_url;
	protected $key;

	public function __construct( $gravity_manager_url, $support_url, $key ) {
		$this->gravity_manager_url = $gravity_manager_url;
		$this->support_url         = $support_url;
		$this->key = $key;
	}

	/**
	 * Get the support URL
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_support_url() {
		return $this->support_url;
	}

	/**
	 * Post request to Gravity Manager.
	 *
	 * @since 1.0
	 *
	 * @param string $file    The file.
	 * @param string $query   The query string.
	 * @param array  $options The options.
	 *
	 * @return array|WP_Error
	 */
	public function post_to_manager( $file, $query, $options ) {

		if ( ! isset( $options['headers'] ) ) {
			$options['headers'] = array();
		}
		// Forcing Referer to the unfiltered home url when sending requests to gravity manager.
		$options['headers']['Referer'] = get_option( 'home' );

		// Sending filtered version of URL so that gravity manager can remove duplicate URLs when filtered and unfiltered URLs are different.
		$options['headers']['Filtered-Site-URL'] = get_bloginfo( 'url' );

		$request_url  = $this->gravity_manager_url . '/' . $file . '?' . $query;
		$raw_response = wp_remote_post( $request_url, $options );

		return $raw_response;
	}

	/**
	 * Get the stored license key.
	 *
	 * @since 1.0
	 *
	 * @return mixed
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Returns the raw value from a SELECT version() db query.
	 *
	 * @since 1.0
	 *
	 * @return string Returns the raw value from a SELECT version() or SELECT sqlite_version() db query.
	 */
	public static function get_dbms_version() {
		static $value;

		if ( empty( $value ) ) {
			global $wpdb;
			$value = $wpdb->get_var( 'SELECT version();' );

			if ( ( get_class( $wpdb ) === 'WP_SQLite_DB' ) || $wpdb->last_error ) {
				$value = $wpdb->get_var( 'SELECT sqlite_version();' );
			}
		}

		return $value;
	}

	/**
	 * Return current database management system.
	 *
	 * @since 1.0
	 *
	 * @return string either MySQL, MariaDB, or SQLite.
	 */
	public static function get_dbms_type() {
		static $type;
		global $wpdb;

		if ( empty( $type ) ) {
			$type = strpos( strtolower( self::get_dbms_version() ), 'mariadb' ) ? 'MariaDB' : 'MySQL';

			if ( get_class( $wpdb ) === 'WP_SQLite_DB' ) {
				$type = 'SQLite';
			}
		}

		return $type;
	}

	/**
	 * Get the total emails sent.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public static function get_emails_sent() {
		$count = get_option( 'gform_email_count' );

		if ( ! $count ) {
			$count = 0;
		}

		return $count;
	}

	/**
	 * Get the number of API calls.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public static function get_api_calls() {
		$count = get_option( 'gform_api_count' );

		if ( ! $count ) {
			$count = 0;
		}

		return $count;
	}


	/**
	 * Return the version of MySQL or MariaDB currently in use.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public static function get_db_version() {
		static $version;

		if ( empty( $version ) ) {
			$version = preg_replace( '/[^0-9.].*/', '', self::get_dbms_version() );
		}

		return $version;
	}

	/**
	 * Unserializes a string while suppressing errors, checks if the result is of the expected type.
	 *
	 * @since 1.0
	 *
	 * @param string $string   The string to be unserialized.
	 * @param string $expected The expected type after unserialization.
	 * @param bool   $default  The default value to return if unserialization failed.
	 *
	 * @return false|mixed
	 */
	public static function safe_unserialize( $string, $expected, $default = false ) {

		$data = is_string( $string ) ? @unserialize( $string ) : $string;

		if ( is_a( $data, $expected ) ) {
			return $data;
		}

		return $default;
	}

	/**
	 * Checks for the existence of a MySQL table.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $table_name Table to check for.
	 *
	 * @uses   wpdb::get_var()
	 *
	 * @return bool
	 */
	public function table_exists( $table_name ) {

		global $wpdb;

		$count = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );

		return ! empty( $count );

	}


	/**
	 * Helper function for getting values from query strings or arrays
	 *
	 * @since 1.0
	 *
	 * @param string $name  The key
	 * @param array  $array The array to search through.  If null, checks query strings.  Defaults to null.
	 *
	 * @return string The value.  If none found, empty string.
	 */
	public function rgget( $name, $array = null ) {
		if ( ! isset( $array ) ) {
			$array = $_GET;
		}

		if ( ! is_array( $array ) ) {
			return '';
		}

		if ( isset( $array[ $name ] ) ) {
			return $array[ $name ];
		}

		return '';
	}


	/**
	 * Helper function to obtain POST values.
	 *
	 * @since 1.0
	 *
	 * @param string $name            The key
	 * @param bool   $do_stripslashes Optional. Performs stripslashes_deep.  Defaults to true.
	 *
	 * @return string The value.  If none found, empty string.
	 */
	public function rgpost( $name, $do_stripslashes = true ) {
		if ( isset( $_POST[ $name ] ) ) {
			return $do_stripslashes ? stripslashes_deep( $_POST[ $name ] ) : $_POST[ $name ];
		}

		return '';
	}


	/**
	 * Get a specific property of an array without needing to check if that property exists.
	 *
	 * Provide a default value if you want to return a specific value if the property is not set.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $array   Array from which the property's value should be retrieved.
	 * @param string $prop    Name of the property to be retrieved.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	public function rgar( $array, $prop, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		if ( isset( $array[ $prop ] ) ) {
			$value = $array[ $prop ];
		} else {
			$value = '';
		}

		return empty( $value ) && $default !== null ? $default : $value;
	}


	/**
	 * Gets a specific property within a multidimensional array.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $array   The array to search in.
	 * @param string $name    The name of the property to find.
	 * @param string $default Optional. Value that should be returned if the property is not set or empty. Defaults to null.
	 *
	 * @return null|string|mixed The value
	 */
	public function rgars( $array, $name, $default = null ) {

		if ( ! is_array( $array ) && ! ( is_object( $array ) && $array instanceof ArrayAccess ) ) {
			return $default;
		}

		$names = explode( '/', $name );
		$val   = $array;
		foreach ( $names as $current_name ) {
			$val = $this->rgar( $val, $current_name, $default );
		}

		return $val;
	}


	/**
	 * Determines if a value is empty.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $name  The property name to check.
	 * @param array  $array Optional. An array to check through.  Otherwise, checks for POST variables.
	 *
	 * @return bool True if empty.  False otherwise.
	 */
	public function rgempty( $name, $array = null ) {

		if ( is_array( $name ) ) {
			return empty( $name );
		}

		if ( ! $array ) {
			$array = $_POST;
		}

		$val = $this->rgar( $array, $name );

		return empty( $val );
	}


	/**
	 * Checks if the string is empty
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $text The string to check.
	 *
	 * @return bool True if empty.  False otherwise.
	 */
	public function rgblank( $text ) {
		return empty( $text ) && ! is_array( $text ) && strval( $text ) != '0';
	}


	/**
	 * Gets a property value from an object
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param object $obj  The object to check
	 * @param string $name The property name to check for
	 *
	 * @return string The property value
	 */
	public function rgobj( $obj, $name ) {
		if ( isset( $obj->$name ) ) {
			return $obj->$name;
		}

		return '';
	}

	/**
	 * Converts a delimiter separated string to an array.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $sep    The delimiter between values
	 * @param string $string The string to convert
	 * @param int    $count  The expected number of items in the resulting array
	 *
	 * @return array $ary The exploded array
	 */
	public function rgexplode( $sep, $string, $count ) {
		$ary = explode( $sep, $string );
		while ( count( $ary ) < $count ) {
			$ary[] = '';
		}

		return $ary;
	}

}