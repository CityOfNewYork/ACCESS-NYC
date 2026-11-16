<?php
/**
 * Markdown Formatter.
 *
 * @package wpconnect/air-wp-sync-core
 */

namespace Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Formatters;

use Air_WP_Sync_Pro_Vendor\WPConnect\Air_WP_Sync_Core\Third_Party\Parsedown;

/**
 * Class Markdown_Formatter
 */
class Markdown_Formatter {
	/**
	 * Parsedown instance
	 *
	 * @var Parsedown
	 */
	protected $parsedown;

	/**
	 * Constructor.
	 *
	 * @param Parsedown $parsedown Parsedown instance.
	 */
	public function __construct( $parsedown ) {
		$this->parsedown = $parsedown;
	}

	/**
	 * Convert Markdown to HTML
	 *
	 * @param string $value Markdown text.
	 *
	 * @return string
	 */
	public function format( $value ) {
		// Fix double backslash escaping.
		$value = str_replace( '\_', '_', $value );
		$html  = $this->parsedown->setBreaksEnabled( true )->text( $value );
		return $html;
	}
}
