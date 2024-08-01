<?php

namespace WordfenceLS;

class Utility_Sleep {
	
	/**
	 * Implements sleep in a way that supports fractional seconds. This is necessary because `usleep` is documented
	 * as only supporting partial seconds (i.e., anything sub-1 second) while `sleep` only supports whole number
	 * seconds. For durations above 1 second with a fractional amount, we end up calling both.
	 * 
	 * @param int|float $seconds
	 */
	public static function sleep($seconds) {
		if ($seconds >= 1) {
			sleep((int) $seconds);
			$seconds -= (int) $seconds;
		}
		
		if ($seconds > 0) {
			usleep((int) (1000000 * $seconds));
		}
	}
}