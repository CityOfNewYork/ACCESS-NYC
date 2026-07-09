<?php

namespace WordfenceLS;

/**
 * An implementation of the Utility_Lock that doesn't actually do any locking
 */
class Utility_NullLock implements Utility_Lock {

	public function acquire($delay = self::DEFAULT_DELAY) {
		//Do nothing
	}

	public function release() {
		//Do nothing
	}

}