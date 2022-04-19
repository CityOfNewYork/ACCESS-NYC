<?php
/**
 * GatherContent Plugin
 *
 * @package GatherContent Plugin
 */

namespace GatherContent\Importer;

/**
 * Base class for all classes. Provides some methods for
 * interacting w/ $_GET and $_POST superglobals.
 *
 * @version 3.0.0
 */
abstract class Base {

	/**
	 * The $_GET superglobal.
	 *
	 * @var array|null
	 */
	protected static $_get  = null;

	/**
	 * The $_POST superglobal.
	 *
	 * @var array|null
	 */
	protected static $_post = null;

	/**
	 * Creates an instance of this class.
	 * Superglobals are passed as class arguments to keep the object clean of globals.
	 *
	 * @since 3.0.0
	 *
	 * @param array $_get  Array of $_GET variables.
	 * @param array $_post Array of $_POST variables.
	 *
	 * @throws Exception If the $_GET and $_Post variables are not set on the first initation.
	 */
	protected function __construct( array $_get = null, array $_post = null ) {
		if ( is_array( $_get ) ) {
			self::$_get  = $_get;
		}

		if ( is_array( $_post ) ) {
			self::$_post = $_post;
		}

		if ( null === self::$_get || null === self::$_post ) {
			throw new Exception( __CLASS__ . ' expects the $_GET and $_POST variables as arguments' );
		}
	}

	/**
	 * Get the value from the query array.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $key Key to check/retrieve.
	 *
	 * @return mixed        Query value if it exists.
	 */
	public function _get_val( $key ) {
		return isset( self::$_get[ $key ] ) ? self::$_get[ $key ] : null;
	}

	/**
	 * See if the query array has a value and if its value matches the $value.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $key   Key to check.
	 * @param  string $value Value to check.
	 *
	 * @return bool           Whether Query key/value exists.
	 */
	public function get_val_equals( $key, $value ) {
		return isset( self::$_get[ $key ] ) && $value === self::$_get[ $key ];
	}

	/**
	 * Get the value from the $_POST array.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $key Key to check/retrieve.
	 *
	 * @return mixed       Query value if it exists.
	 */
	public function _post_val( $key ) {
		return isset( self::$_post[ $key ] ) ? self::$_post[ $key ] : null;
	}

	/**
	 * See if the $_POST array has a value and if its value matches the $value.
	 *
	 * @since  3.0.0
	 *
	 * @param  string $key   Key to check.
	 * @param  string $value Value to check.
	 *
	 * @return bool          Whether Query key/value exists.
	 */
	public function post_val_equals( $key, $value ) {
		return isset( self::$_post[ $key ] ) && $value === self::$_post[ $key ];
	}

	/**
	 * Outputs a view.
	 *
	 * @since  3.0.0
	 *
	 * @param  string  $template The template name.
	 * @param  array   $args     Array of args for the template.
	 * @param  boolean $echo     Whether to output result or return it.
	 *
	 * @return mixed             Result of view rendering if requesting to return it.
	 */
	public function view( $template, array $args = array(), $echo = true ) {
		switch ( $template ) {
			case 'input':
				$view = new Views\Input( $args );
				break;

			case 'radio':
				$view = new Views\Radio( $args );
				break;

			default:
				$view = new Views\View( $template, $args );
				break;
		}

		if ( $echo ) {
			$view->load();
		} else {
			return $view->load( false );
		}
	}

}
