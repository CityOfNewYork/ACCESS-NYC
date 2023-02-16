<?php

namespace WPML;

class Timer {

	private $endTime;

	public function start( $timeOut ) {
		$this->endTime = time() + $timeOut;
	}

	public function hasTimedOut() {
		return time() > $this->endTime;
	}

	public function hasNotTimedOut() {
		return ! $this->hasTimedOut();
	}
}
