<?php

class wfCurlInterceptionFailedException extends RuntimeException {
}

class wfCurlInterceptor {

	const HOOK_NAME = 'http_api_curl';

	private $handle = null;
	private $options = array();
	private $requireInterception;

	public function __construct($requireInterception = true) {
		$this->requireInterception = $requireInterception;
	}

	private function reset() {
		$this->handle = null;
	}

	public function setOption($option, $value) {
		$this->options[$option] = $value;
	}

	public function getHandle() {
		return $this->handle;
	}

	public function handleHook($handle) {
		$this->handle = $handle;
		curl_setopt_array($handle, $this->options);
	}

	public function intercept($callable) {
		$this->reset();
		$action = array($this, 'handleHook');
		add_action(self::HOOK_NAME, $action);
		$result = $callable();
		if ($this->handle === null && $this->requireInterception)
			throw new wfCurlInterceptionFailedException('Hook was not invoked with a valid cURL handle');
		remove_action(self::HOOK_NAME, $action);
		return $result;
	}

}