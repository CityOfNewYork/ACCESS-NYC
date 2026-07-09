<?php

class wfJWT {

	private $claims;
	const JWT_TTL = 600;
	const ISSUER = 600;

	public static function extractTokenContents($token) {
		if (!is_string($token)) {
			throw new InvalidArgumentException('Token is not a string. ' . gettype($token) . ' given.');
		}

		// Verify the token matches the JWT format.
		if (!preg_match('/^[a-zA-Z0-9\-_]+?\.[a-zA-Z0-9\-_]+?\.[a-zA-Z0-9\-_]+?$/', $token)) {
			throw new wfJWTException('Invalid token format.');
		}
		list($header, $body, $signature) = explode('.', $token);

		// Test that the token is valid and not expired.
		$decodedHeader = base64_decode($header);

		if (!(is_string($decodedHeader) && $decodedHeader)) {
			throw new wfJWTException('Token header is invalid.');
		}

		$header = json_decode($decodedHeader, true);
		if (!is_array($header)) {
			throw new wfJWTException('Token header is invalid.');
		}

		$decodedBody = base64_decode($body);

		if (!(is_string($decodedBody) && $decodedBody)) {
			throw new wfJWTException('Token body is invalid.');
		}

		$body = json_decode($decodedBody, true);
		if (!is_array($body)) {
			throw new wfJWTException('Token body is invalid.');
		}

		return array(
			'header'    => $header,
			'body'      => $body,
			'signature' => $signature,
		);

	}

	/**
	 * @param mixed $subject
	 */
	public function __construct($subject = null) {
		$this->claims = $this->getClaimDefaults();
		$this->claims['sub'] = $subject;
	}

	/**
	 * @return string
	 */
	public function encode() {
		$header = $this->encodeString($this->buildHeader());
		$body = $this->encodeString($this->buildBody());
		return sprintf('%s.%s.%s', $header, $body,
			$this->encodeString($this->sign(sprintf('%s.%s', $header, $body))));
	}

	/**
	 * @param string $token
	 * @return array
	 * @throws wfJWTException|InvalidArgumentException
	 */
	public function decode($token) {
		if (!is_string($token)) {
			throw new InvalidArgumentException('Token is not a string. ' . gettype($token) . ' given.');
		}

		// Verify the token matches the JWT format.
		if (!preg_match('/^[a-zA-Z0-9\-_]+?\.[a-zA-Z0-9\-_]+?\.[a-zA-Z0-9\-_]+?$/', $token)) {
			throw new wfJWTException('Invalid token format.');
		}
		list($header, $body, $signature) = explode('.', $token);

		// Verify signature matches the supplied payload.
		if (!$this->verifySignature($this->decodeString($signature), sprintf('%s.%s', $header, $body))) {
			throw new wfJWTException('Invalid signature.');
		}

		// Test that the token is valid and not expired.
		$decodedHeader = base64_decode($header);

		if (!(is_string($decodedHeader) && $decodedHeader)) {
			throw new wfJWTException('Token header is invalid.');
		}

		$header = json_decode($decodedHeader, true);
		if (!(
			is_array($header) &&
			array_key_exists('alg', $header) &&
			$header['alg'] === 'HS256' &&
			$header['typ'] === 'JWT'
		)) {
			throw new wfJWTException('Token header is invalid.');
		}

		$decodedBody = base64_decode($body);

		if (!(is_string($decodedBody) && $decodedBody)) {
			throw new wfJWTException('Token body is invalid.');
		}

		$body = json_decode($decodedBody, true);
		if (!(
			is_array($body) &&

			// Check the token not before now timestamp.
			array_key_exists('nbf', $body) &&
			is_numeric($body['nbf']) &&
			$body['nbf'] <= time() &&

			// Check the token is not expired.
			array_key_exists('exp', $body) &&
			is_numeric($body['exp']) &&
			$body['exp'] >= time() &&

			// Check the issuer and audience is ours.
			$body['iss'] === 'Wordfence ' . WORDFENCE_VERSION &&
			$body['aud'] === 'Wordfence Central'
		)) {
			throw new wfJWTException('Token is invalid or expired.');
		}

		return array(
			'header' => $header,
			'body'   => $body,
		);
	}

	/**
	 * @param string $string
	 * @return string
	 */
	public function sign($string) {
		$salt = wp_salt('auth');

		return hash_hmac('sha256', $string, $salt, true);
	}

	/**
	 * @param string $signature
	 * @param string $message
	 * @return bool
	 */
	public function verifySignature($signature, $message) {
		return hash_equals($this->sign($message), $signature);
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->encode();
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function encodeString($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}

	/**
	 * @param string $data
	 * @return bool|string
	 */
	public function decodeString($data) {
		return base64_decode(strtr($data, '-_', '+/'));
	}

	/**
	 * @return mixed|string
	 */
	protected function buildHeader() {
		return '{"alg":"HS256","typ":"JWT"}';
	}

	/**
	 * @return mixed|string
	 */
	protected function buildBody() {
		return json_encode($this->getClaims());
	}

	/**
	 * @return array
	 */
	protected function getClaimDefaults() {
		$now = time();
		return array(
			'iss' => 'Wordfence ' . WORDFENCE_VERSION,
			'aud' => 'Wordfence Central',
			'nbf' => $now,
			'iat' => $now,
			'exp' => $now + self::JWT_TTL,
		);
	}

	/**
	 * @param array $claims
	 */
	public function addClaims($claims) {
		if (!is_array($claims)) {
			throw new InvalidArgumentException(__METHOD__ . ' expects argument 1 to be array.');
		}
		$this->setClaims(array_merge($this->getClaims(), $claims));
	}

	/**
	 * @return array
	 */
	public function getClaims() {
		return $this->claims;
	}

	/**
	 * @param array $claims
	 */
	public function setClaims($claims) {
		$this->claims = $claims;
	}
}

class wfJWTException extends Exception {

}