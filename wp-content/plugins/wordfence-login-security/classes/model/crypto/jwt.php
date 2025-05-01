<?php

namespace WordfenceLS\Crypto;

use WordfenceLS\Controller_Time;
use WordfenceLS\Model_Crypto;

/**
 * Class Model_JWT
 * @package Wordfence2FA\Crypto
 * @property array $payload
 * @property int $expiration
 */
class Model_JWT {
	private $_payload;
	private $_expiration;
	
	/**
	 * Decodes and returns the payload of a JWT. This also validates the signature and expiration. Currently assumes HS256 JWTs.
	 *
	 * @param string $token
	 * @return Model_JWT|bool The decoded JWT or false if the token is invalid or fails validation.
	 */
	public static function decode_jwt($token) {
		$components = explode('.', $token);
		if (count($components) != 3) {
			return false;
		}
		
		$key = Model_Crypto::shared_hash_secret();
		$body = $components[0] . '.' . $components[1];
		$signature = hash_hmac('sha256', $body, $key, true);
		$testSignature = self::base64url_decode($components[2]);
		if (!hash_equals($signature, $testSignature)) {
			return false;
		}
		
		$json = self::base64url_decode($components[1]);
		$payload = @json_decode($json, true);
		$expiration = false;
		if (!is_array($payload)) {
			return false;
		}
		else if (isset($payload['_exp'])) {
			$expiration = $payload['_exp'];
			
			if ($payload['_exp'] < Controller_Time::time()) {
				return false;
			}
			
			unset($payload['_exp']);
		}
		
		return new self($payload, $expiration);
	}
	
	/**
	 * Model_JWT constructor.
	 * 
	 * @param array $payload
	 * @param bool|int $expiration
	 */
	public function __construct($payload, $expiration = false) {
		$this->_payload = $payload;
		$this->_expiration = $expiration;
	}
	
	public function __toString() {
		$payload = $this->_payload;
		if ($this->_expiration !== false) {
			$payload['_exp'] = $this->_expiration;
		}
		$key = Model_Crypto::shared_hash_secret();
		$header = '{"alg":"HS256","typ":"JWT"}';
		$body = self::base64url_encode($header) . '.' . self::base64url_encode(json_encode($payload));
		$signature = hash_hmac('sha256', $body, $key, true);
		return $body . '.' . self::base64url_encode($signature);
	}
	
	public function __isset($key) {
		switch ($key) {
			case 'payload':
			case 'expiration':
				return true;
		}
		
		throw new \OutOfBoundsException('Invalid key: ' . $key);
	}
	
	public function __get($key) {
		switch ($key) {
			case 'payload':
				return $this->_payload;
			case 'expiration':
				return $this->_expiration;
		}
		
		throw new \OutOfBoundsException('Invalid key: ' . $key);
	}
	
	/**
	 * Utility
	 */
	
	/**
	 * Base64URL-encodes the given payload. This is identical to base64_encode except it substitutes characters
	 * not safe for use in URLs.
	 *
	 * @param string $payload
	 * @return string
	 */
	public static function base64url_encode($payload) {
		return self::base64url_convert_to(base64_encode($payload));
	}
	
	public static function base64url_convert_to($base64) {
		$intermediate = rtrim($base64, '=');
		$intermediate = str_replace('+', '-', $intermediate);
		$intermediate = str_replace('/', '_', $intermediate);
		return $intermediate;
	}
	
	/**
	 * Base64URL-decodes the given payload. This is identical to base64_encode except it allows for the characters
	 * substituted by base64url_encode.
	 *
	 * @param string $payload
	 * @return string
	 */
	public static function base64url_decode($payload) {
		return base64_decode(self::base64url_convert_from($payload));
	}
	
	public static function base64url_convert_from($base64url) {
		$intermediate = str_replace('_', '/', $base64url);
		$intermediate = str_replace('-', '+', $intermediate);
		return $intermediate;
	}
}