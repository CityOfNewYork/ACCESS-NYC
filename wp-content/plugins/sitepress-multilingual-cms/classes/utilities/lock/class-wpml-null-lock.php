<?php

namespace WPML\Utilities;

class NullLock implements ILock {

	public function create( $release_timeout = null ) {
		return true;
	}

	public function release() {
		return true;
	}

}
