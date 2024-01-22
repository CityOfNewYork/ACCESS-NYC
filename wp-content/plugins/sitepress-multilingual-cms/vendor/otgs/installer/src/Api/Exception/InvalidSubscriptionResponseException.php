<?php

namespace OTGS\Installer\Api\Exception;

class InvalidSubscriptionResponseException extends \Exception {
	public function __construct() {
		parent::__construct("Unable to parse subscription data from service.");
	}
}