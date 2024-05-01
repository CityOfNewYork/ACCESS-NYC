<?php

namespace OTGS\Installer\Api\Exception;

class InvalidSubscription extends \Exception {
	public function __construct( $details ) {
		parent::__construct( "Unable to register: " . $details );
	}
}