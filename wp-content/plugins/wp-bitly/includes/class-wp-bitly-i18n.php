<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://watermelonwebworks.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      2.6.0
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 * @author     Watermelon Web Works <projects@watermelonwebworks.com>
 */
class Wp_Bitly_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.6.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-bitly',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
