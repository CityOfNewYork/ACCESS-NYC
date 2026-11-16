<?php
/**
 * Abstract Formatter Strategy.
 * Base class to define formatter strategy.
 *
 * @package wpconnect/x-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\X_WP_Sync_Core\Models\Import_Field_Context;

/**
 * Class Abstract_Formatter_Strategy
 */
abstract class Abstract_Formatter_Strategy {
	/**
	 * Formatter strategy slug
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Constructor.
	 * Checks if the slug has been defined.
	 *
	 * @throws \Exception Formatter strategy slug must be defined.
	 */
	public function __construct() {
		if ( ! $this->slug ) {
			throw new \Exception( 'Formatter strategy slug must be defined' );
		}
	}

	/**
	 * Return formatter strategy slug.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * Format value then check formatted value, return [ 'success' => true ] if the format match, return [ 'success' => false ] if it does not.
	 * Add messages to the result to explain why it failed or warning if it succeeded with comments.
	 *
	 * @param mixed                $value The value to format.
	 * @param Import_Field_Context $context The import field context for the formatter.
	 *
	 * @return mixed
	 */
	abstract public function format( $value, $context );
}
