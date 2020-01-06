<?php

namespace WPML\Utilities;

interface ILock {

	public function create( $release_timeout = null );
	public function release();
}