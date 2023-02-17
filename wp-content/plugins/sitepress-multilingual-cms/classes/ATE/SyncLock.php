<?php

namespace WPML\TM\ATE;

use WPML\Utilities\KeyedLock;
use function WPML\Container\make;

class SyncLock {
	/** @var KeyedLock */
	private $keyedLock;

	public function __construct() {
		$this->keyedLock = make( KeyedLock::class, [ ':name' => 'ate_sync' ] );
	}

	/**
	 * @param null~string $key
	 *
	 * @return false|string
	 */
	public function create( $key = null ) {
		return $this->keyedLock->create( $key, 30 );
	}

	/**
	 * @return bool
	 */
	public function release() {
		return $this->keyedLock->release();
	}
}