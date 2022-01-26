<?php

/**
 * Logging for the plugin
 *
 * @link       https://watermelonwebworks.com
 * @since      2.6.0
 *
 * @package    Wp_Bitly
 * @subpackage Wp_Bitly/includes
 */


class Wp_Bitly_Logger {

	/**
     * The options class.
     *
     * @since    2.6.0
     * @access   protected
     * @var      class $wp_bitly_options
     */
    protected $wp_bitly_options;

	/**
	 * Initialize 
	 *
	 * @since    2.6.0
	 */
	public function __construct() {
		$this->wp_bitly_options = new Wp_Bitly_Options(); 
	}


	/**
	 * Logging function
	 *
	 * @since    2.6.0
	 */


	public function wpbitly_debug_log($towrite, $message, $bypass = true)
	{

	    if (!$this->wp_bitly_options->get_option('debug') || !$bypass) {
	        return;
	    }

	    $log = fopen(WPBITLY_LOG, 'a');

	    fwrite($log, '# [ ' . date('F j, Y, g:i a') . " ]\n");
	    fwrite($log, '# [ ' . $message . " ]\n\n");
	    fwrite($log, (is_array($towrite) ? print_r($towrite, true) : var_export($towrite, 1)));
	    fwrite($log, "\n\n\n");

	    fclose($log);

	}
	

}
