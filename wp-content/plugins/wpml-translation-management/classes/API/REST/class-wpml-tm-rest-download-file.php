<?php

class WPML_TM_Rest_Download_File {

	public function send( $file_name, $content, $content_type = 'application/x-xliff+xml' ) {
		add_filter( 'wp_die_handler', array( $this, 'get_wp_die_handler' ) );
		add_filter( 'rest_pre_echo_response', array( $this, 'force_wp_rest_server_download' ) );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . strlen( $content ) );

		return $content;
	}

	public function get_wp_die_handler() {
		return '__return_empty_string';
	}

	public function force_wp_rest_server_download( $content ) {
		echo $content;
		wp_die();
	}
}
