<?php

class Installer_Upgrader_Skins extends WP_Upgrader_Skin {

	function __construct( $args = array() ) {
		$defaults      = array( 'url' => '', 'nonce' => '', 'title' => '', 'context' => false );
		$this->options = wp_parse_args( $args, $defaults );
	}

	function header() {

	}

	function footer() {

	}

	function error( $error ) {
		$this->installer_error = $error;
	}

	function add_strings() {

	}

	function feedback( $string, ...$args ) {

	}

	function before() {

	}

	function after() {

	}

	public function request_filesystem_credentials( $error = false, $context = '', $allow_relaxed_file_ownership = false ) {
		ob_start();
		$credentials = parent::request_filesystem_credentials( $error, $context, $allow_relaxed_file_ownership );
		ob_end_clean();

		if ( ! $credentials ) {
			$message = __( 'We were not able to copy some plugin files. This is usually due to issues with permissions for WordPress content or plugins folder.', 'installer' );
			$this->error( new WP_Error( 'files_not_writable', $message, $context ) );
		}
		return $credentials;
	}

}
