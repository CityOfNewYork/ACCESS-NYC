<?php

namespace OTGS\Installer\Api\Exception;

class InvalidProductsResponseException extends \Exception {
	public function __construct() {
		parent::__construct("Unable to parse product url data from service.");
	}
}