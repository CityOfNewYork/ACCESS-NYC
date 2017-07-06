<?php

class WPML_Data_Encryptor {

	/**
	 * @var string $method
	 */
	private $method;
	/**
	 * @var string $key
	 */
	private $key;
	/**
	 * @var string $iv
	 */
	private $iv;

	/**
	 * @var string
	 */
	private $library = false;

	/**
	 * WPML_Data_Encrypter constructor.
	 *
	 * @param string $method
	 *
	 */
	public function __construct( $method = 'AES-256-CTR' ) {

		if ( function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' )
		     && version_compare( phpversion(), '5.3.2', '>' ) ) {

			$methods = openssl_get_cipher_methods();
			if ( ! in_array( $method, $methods ) && ! empty( $methods ) ) {
				$this->method = $methods[0];
			} else {
				$this->method = $method;
			}
			$this->library = 'openssl';
			$this->key   = substr( sha1( $this->random_string( 12 ), true ), 0, 16 );
			$this->iv    = openssl_random_pseudo_bytes( 16 );

		} else if ( function_exists( 'mcrypt_encrypt' ) && function_exists( 'mcrypt_decrypt' ) ) { // PHP 5.2 support
			$this->library = 'mcrypt';
			$this->key     = substr( NONCE_KEY, 0, 24 );
			$this->iv      = mcrypt_create_iv( mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ), MCRYPT_RAND );

		}
	}

	private function random_string( $length ) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
		$rand  = '';
		for ( $i = 0; $i < $length; $i ++ ) {
			$rand .= substr( $chars, rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $rand;
	}

	/**
	 * @param string $data
	 *
	 * @return string
	 */
	public function encrypt( $data ) {
		if ( $this->library === 'openssl' ) {
			$encrypted_data = openssl_encrypt( $data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv );
		} elseif ( $this->library === 'mcrypt' ) { // PHP 5.2 support
			$encrypted_data = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $this->key, $data, MCRYPT_MODE_ECB, $this->iv );
			$encrypted_data = preg_replace( '/\x00/', '', $encrypted_data ); // strip padding added to match the block size
		} else {
			$encrypted_data = $data;
		}

		return $encrypted_data;
	}

	/**
	 * @param string $encrypted_data
	 *
	 * @return string
	 */
	public function decrypt( $encrypted_data ) {

		if ( $this->library === 'openssl' ) {
			$data = openssl_decrypt( $encrypted_data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv );
		} elseif ( $this->library === 'mcrypt' ) { // PHP 5.2 support
			$data = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $this->key, $encrypted_data, MCRYPT_MODE_ECB, $this->iv );
			$data = preg_replace( '/\x00/', '', $data );
		} else {
			$data = $encrypted_data;
		}

		return $data;
	}

	/**
	 * @param string $library
	 */
	public function set_crypt_library( $library ){
		$this->library = $library;
	}

	/**
	 * @return string
	 */
	public function get_crypt_library(){
		return $this->library;
	}

}