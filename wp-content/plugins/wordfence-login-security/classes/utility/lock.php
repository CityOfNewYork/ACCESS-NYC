<?php

namespace WordfenceLS;

interface Utility_Lock {

	const DEFAULT_DELAY = 100000;

	public function acquire($delay = self::DEFAULT_DELAY);

	public function release();

}