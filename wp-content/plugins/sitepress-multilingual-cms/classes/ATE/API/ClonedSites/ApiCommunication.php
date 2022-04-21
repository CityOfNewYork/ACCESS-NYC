<?php

namespace WPML\TM\ATE\ClonedSites;

class ApiCommunication {

	const SITE_CLONED_ERROR = 426;

	/**
	 * @var Lock
	 */
	private $lock;

	/**
	 * @param Lock $lock
	 */
	public function __construct( Lock $lock ) {
		$this->lock = $lock;
	}

	public function handleClonedSiteError( $response ) {
		if ( self::SITE_CLONED_ERROR === $response['response']['code'] ) {
			$parsedResponse = json_decode( $response['body'], true );
			if ( isset( $parsedResponse['errors'] ) ) {
				$this->handleClonedDetection( $parsedResponse['errors'] );
			}
			return new \WP_Error( self::SITE_CLONED_ERROR, 'Site Moved or Copied - Action Required' );
		}

		return $response;
	}

	public function checkCloneSiteLock() {
		if ( Lock::isLocked() ) {
			return new \WP_Error( self::SITE_CLONED_ERROR, 'Site Moved or Copied - Action Required - ATE communication locked.' );
		}

		return null;
	}

	public function unlockClonedSite() {
		return $this->lock->unlock();
	}

	private function handleClonedDetection( $error_data ) {
		$error = array_pop( $error_data );
		$this->lock->lock( $error );
	}
}
