<?php

namespace OTGS\Installer\Api\Exception;

class ClientException extends \Exception {
	public function __construct( $details ) {
		parent::__construct( "Connection error: Unable to get data from service. Detailed error: " . $details );
	}
}