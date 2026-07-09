<?php

class wfWebsiteEphemeralPayloadRetrievalException extends RuntimeException {
}

class wfWebsiteEphemeralPayloadExpiredException extends wfWebsiteEphemeralPayloadRetrievalException {

	const STATUS = 404;

	public function __construct() {
		parent::__construct('Ephemeral payload expired', self::STATUS);
	}

}

class wfWebsiteEphemeralPayloadRateLimitedException extends wfWebsiteEphemeralPayloadRetrievalException {

	const STATUS = 429;

	public function __construct() {
		parent::__construct('Request limit reached', self::STATUS);
	}

}

/**
 * Utilities related to the Wordfence website (wordfence.com)
 */
class wfWebsite {

	private static $INSTANCE = null;

	private $url;

	private function __construct($url) {
		$this->url = trailingslashit($url);
	}

	public function getUrl($relative) {
		return $this->url . $relative;
	}

	public function retrievePayload($token, &$expired) {
		$url = $this->getUrl("api/ephemeral-payload/$token");
		$response = wp_remote_get($url);
		$status = wp_remote_retrieve_response_code($response);
		if (!is_wp_error($response) && $status === 200) {
			return wp_remote_retrieve_body($response);
		}
		switch ($status) {
		case wfWebsiteEphemeralPayloadExpiredException::STATUS:
			throw new wfWebsiteEphemeralPayloadExpiredException();
		case wfWebsiteEphemeralPayloadRateLimitedException::STATUS:
			throw new wfWebsiteEphemeralPayloadRateLimitedException();
		default:
			throw new wfWebsiteEphemeralPayloadRetrievalException('Failed to retrieve ephemeral payload', (int) $status);
		}
	}

	public static function getInstance() {
		if (self::$INSTANCE === null)
			self::$INSTANCE = new self(WORDFENCE_WWW_BASE_URL);
		return self::$INSTANCE;
	}

	public static function url($relative) {
		return self::getInstance()->getUrl($relative);
	}

}