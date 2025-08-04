<?php

namespace Gravity_Forms\Gravity_SMTP\Utils;

class Basic_Encrypted_Hash {

	const METHOD = 'aes-256-ctr';

	public function encrypt( $message ) {
		$nonce_size = openssl_cipher_iv_length( self::METHOD );
		$nonce      = openssl_random_pseudo_bytes( $nonce_size );

		$cipher = openssl_encrypt(
			$message,
			self::METHOD,
			SECURE_AUTH_KEY,
			OPENSSL_RAW_DATA,
			$nonce
		);

		return base64_encode( $nonce . $cipher );
	}

	public function decrypt( $message ) {
		$message = base64_decode( $message, true );

		if ( $message === false ) {
			return new \WP_Error( 'bad_encryption', 'Encryption failure' );
		}

		$nonce_size = openssl_cipher_iv_length( self::METHOD );
		$nonce      = mb_substr( $message, 0, $nonce_size, '8bit' );
		$cipher     = mb_substr( $message, $nonce_size, null, '8bit' );

		$plaintext = openssl_decrypt(
			$cipher,
			self::METHOD,
			SECURE_AUTH_KEY,
			OPENSSL_RAW_DATA,
			$nonce
		);

		return $plaintext;
	}

}