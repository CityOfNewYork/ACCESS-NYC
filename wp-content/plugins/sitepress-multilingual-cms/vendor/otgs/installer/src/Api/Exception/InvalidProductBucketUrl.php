<?php

namespace OTGS\Installer\Api\Exception;

class InvalidProductBucketUrl extends \Exception {
	public function __construct( $details ) {
		parent::__construct( "Unable to fetch product url: " . $details );
	}
}