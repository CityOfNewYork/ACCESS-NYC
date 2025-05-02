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
	 * The parameters from $_GET that have been sanitised.
	 *
	 * @var array|null
	 */
	protected static $getParameters = null;

	/**
	 * The parameters from $_POST that have been sanitised.
	 *
	 * @var array|null
	 */
	protected static $postParameters = null;

	/**
	 * Creates an instance of this class.
	 * Superglobals are passed as class arguments to keep the object clean of globals.
	 *
	 * @param array $getParameters Array of $_GET variables that have been sanitised.
	 * @param array $postParameters Array of $_POST variables that have been sanitised.
	 *
	 * @throws Exception If the $_GET and $_Post variables are not set on the first initation.
	 * @since 3.0.0
	 *
	 */
	protected function __construct( array $getParameters = null, array $postParameters = null ) {
		/**
		 * These checks and sets are required as the first time something initialises this type of class
		 * it will set them, then after that any other class extending this one doesn't need to bother
		 * and therefore can pass null.
		 */
		if ( is_array( $getParameters ) ) {
			self::$getParameters = $getParameters;
		}

		if ( is_array( $postParameters ) ) {
			self::$postParameters = $postParameters;
		}

		if ( null === self::$getParameters || null === self::$postParameters ) {
			throw new Exception( __CLASS__ . ' expects the $_GET and $_POST variables as arguments', 500 );
		}
	}

	/**
	 * Get the value from the query array.
	 *
	 * @param string $key Key to check/retrieve.
	 *
	 * @return mixed        Query value if it exists.
	 * @since  3.0.0
	 *
	 */
	public function _get_val( $key ) {
		return isset( self::$getParameters[ $key ] ) ? self::$getParameters[ $key ] : null;
	}

	/**
	 * Returns an array of the key=>value | null in $_GET if the given keys exist.
	 *
	 * @param $keys
	 *
	 * @return array|null
	 */
	public function _get_vals( $keys ) {
		return array_reduce( $keys, function ( $carry, $key ) {
			$carry[ $key ] = $this->_get_val( $key );

			return $carry;
		}, [] );
	}

	/**
	 * See if the query array has a value and if its value matches the $value.
	 *
	 * @param string $key Key to check.
	 * @param string $value Value to check.
	 *
	 * @return bool           Whether Query key/value exists.
	 * @since  3.0.0
	 *
	 */
	public function get_val_equals( $key, $value ) {
		return isset( self::$getParameters[ $key ] ) && $value === self::$getParameters[ $key ];
	}

	/**
	 * Get the value from the $_POST array.
	 *
	 * @param string $key Key to check/retrieve.
	 *
	 * @return mixed       Query value if it exists.
	 * @since  3.0.0
	 *
	 */
	public function _post_val( $key ) {
		return isset( self::$postParameters[ $key ] ) ? self::$postParameters[ $key ] : null;
	}

	/**
	 * See if the $_POST array has a value and if its value matches the $value.
	 *
	 * @param string $key Key to check.
	 * @param string $value Value to check.
	 *
	 * @return bool          Whether Query key/value exists.
	 * @since  3.0.0
	 *
	 */
	public function post_val_equals( $key, $value ) {
		return isset( self::$postParameters[ $key ] ) && $value === self::$postParameters[ $key ];
	}

	/**
	 * Outputs a view.
	 *
	 * @param string $template The template name.
	 * @param array $args Array of args for the template.
	 * @param boolean $echo Whether to output result or return it.
	 *
	 * @return mixed             Result of view rendering if requesting to return it.
	 * @since  3.0.0
	 *
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
