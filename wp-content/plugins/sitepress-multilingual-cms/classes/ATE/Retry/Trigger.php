<?php

namespace WPML\TM\ATE\Retry;

use WPML\WP\OptionManager;

class Trigger {
	const RETRY_TIMEOUT = 10 * MINUTE_IN_SECONDS;

	const OPTION_GROUP = 'WPML\TM\ATE\Retry';
	const RETRY_LAST = 'last';

	/**
	 * @return bool
	 */
	public function isRetryRequired() {
		$retrySync = OptionManager::getOr( 0, self::RETRY_LAST, self::OPTION_GROUP );

		return ( time() - self::RETRY_TIMEOUT ) > $retrySync;
	}

	public function setLastRetry( $time ) {
		OptionManager::updateWithoutAutoLoad( self::RETRY_LAST, self::OPTION_GROUP, $time );
	}
}
