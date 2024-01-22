<?php

namespace OTGS\Installer\Api\Exception;

class InvalidResponseException extends \Exception {
	public function __construct() {
		parent::__construct("Unable to parse data from service response.");
	}
}