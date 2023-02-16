<?php

class WPML_Frontend_Redirection_Url {

	/** @var string $url */
	private $url;

	/**
	 * @param string $url
	 */
	public function __construct( $url ) {
		$this->url = $url;
	}

	/**
	 * URL is being checked for apostrophes. If there are any, apostrophes are encoded.
	 *
	 * @return string URL with the encoded apostrophes.
	 */
	public function encode_apostrophes_in_url() {
		return str_replace( "'", rawurlencode( "'" ), $this->url );
	}
}
