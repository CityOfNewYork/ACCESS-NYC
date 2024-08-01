<?php

namespace WordfenceLS;

class Model_2faInitializationData {

	private $user;
	private $raw_secret;
	private $base32_secret;
	private $otp_url;
	private $recovery_codes;

	public function __construct($user) {
		$this->user = $user;
		$this->raw_secret = Model_Crypto::random_bytes(20);
	}

	public function get_user() {
		return $this->user;
	}

	public function get_raw_secret() {
		return $this->raw_secret;
	}

	public function get_base32_secret() {
		if ($this->base32_secret === null)
			$this->base32_secret = Utility_BaseConversion::base32_encode($this->raw_secret);
		return $this->base32_secret;
	}

	private function generate_otp_url() {
		return "otpauth://totp/" . rawurlencode(preg_replace('~^https?://(?:www\.)?~i', '', home_url()) . ':' . $this->user->user_login) . '?secret=' . $this->get_base32_secret() . '&algorithm=SHA1&digits=6&period=30&issuer=' . rawurlencode(preg_replace('~^https?://(?:www\.)?~i', '', home_url()));
	}

	public function get_otp_url() {
		if ($this->otp_url === null)
			$this->otp_url = $this->generate_otp_url();
		return $this->otp_url;
	}

	public function get_recovery_codes() {
		if ($this->recovery_codes === null)
			$this->recovery_codes = Controller_Users::shared()->regenerate_recovery_codes();
		return $this->recovery_codes;
	}

}