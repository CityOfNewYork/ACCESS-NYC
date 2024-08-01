<?php

namespace WordfenceLS\Crypto;

use WordfenceLS\Model_Crypto;

abstract class Model_Symmetric {
	/**
	 * Returns $data encrypted with the shared symmetric key or false if unable to do so.
	 *
	 * @param string $data
	 * @return bool|array
	 */
	public static function encrypt($data) {
		if (!Model_Crypto::has_required_crypto_functions()) {
			return false;
		}
		
		$symmetricKey = Model_Crypto::shared_symmetric_secret();
		$iv = Model_Crypto::random_bytes(16);
		$encrypted = @openssl_encrypt($data, 'aes-256-cbc', $symmetricKey, OPENSSL_RAW_DATA, $iv);
		if ($encrypted) {
			return array('data' => base64_encode($encrypted), 'iv' => base64_encode($iv));
		}
		return false;
	}
	
	/**
	 * Returns the decrypted value of a payload encrypted by Model_Symmetric::encrypt
	 *
	 * @param array $encrypted
	 * @return bool|string
	 */
	public static function decrypt($encrypted) {
		if (!Model_Crypto::has_required_crypto_functions()) {
			return false;
		}
		
		if (!isset($encrypted['data']) || !isset($encrypted['iv'])) {
			return false;
		}
		
		$symmetricKey = Model_Crypto::shared_symmetric_secret();
		$iv = base64_decode($encrypted['iv']);
		$encrypted = base64_decode($encrypted['data']);
		$data = @openssl_decrypt($encrypted, 'aes-256-cbc', $symmetricKey, OPENSSL_RAW_DATA, $iv);
		return $data;
	}
}