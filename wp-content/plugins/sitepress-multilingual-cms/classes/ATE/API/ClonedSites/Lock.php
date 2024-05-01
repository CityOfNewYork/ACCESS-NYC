<?php


namespace WPML\TM\ATE\ClonedSites;

use WPML\FP\Obj;

class Lock {
	const CLONED_SITE_OPTION = 'otgs_wpml_tm_ate_cloned_site_lock';

	public function lock( $lockData ) {
		if ( $this->isLockDataPresent( $lockData ) ) {
			update_option(
				self::CLONED_SITE_OPTION,
				[
					'stored_fingerprint'            => $lockData['stored_fingerprint'],
					'received_fingerprint'          => $lockData['received_fingerprint'],
					'fingerprint_confirmed'         => $lockData['fingerprint_confirmed'],
					'identical_url_before_movement' => isset( $lockData['identical_url_before_movement'] ) ? $lockData['identical_url_before_movement'] : false,
				],
				'no'
			);
		}
	}

	/**
	 * @return array{urlCurrentlyRegisteredInAMS: string, urlUsedToMakeRequest: string, siteMoved: bool}
	 */
	public function getLockData() {
		$option = get_option( self::CLONED_SITE_OPTION, [] );

		$urlUsedToMakeRequest = Obj::propOr(
			'',
			'wp_url',
			is_string( $option['received_fingerprint'] ) ? json_decode( $option['received_fingerprint'] ) : $option['received_fingerprint']
		);

		$urlCurrentlyRegisteredInAMS = Obj::pathOr( '', [ 'stored_fingerprint', 'wp_url' ], $option );

		return [
			'urlCurrentlyRegisteredInAMS' => $urlCurrentlyRegisteredInAMS,
			'urlUsedToMakeRequest'        => $urlUsedToMakeRequest,
			'identicalUrlBeforeMovement'  => Obj::propOr( false, 'identical_url_before_movement', $option ),
		];
	}

	/**
	 * @return string
	 */
	public function getUrlRegisteredInAMS() {
		$lockData = $this->getLockData();

		return $lockData['urlCurrentlyRegisteredInAMS'];
	}

	private function isLockDataPresent( $lockData ) {
		return isset( $lockData['stored_fingerprint'] )
		       && isset( $lockData['received_fingerprint'] )
		       && isset( $lockData['fingerprint_confirmed'] );
	}

	public function unlock() {
		delete_option( self::CLONED_SITE_OPTION );
	}

	public static function isLocked() {
		return (bool) get_option( self::CLONED_SITE_OPTION, false ) && \WPML_TM_ATE_Status::is_enabled();
	}

}
