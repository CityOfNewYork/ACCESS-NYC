<?php


namespace WPML\TM\ATE\ClonedSites;

class Lock {
	const CLONED_SITE_OPTION = 'otgs_wpml_tm_ate_cloned_site_lock';

	public function lock( $lockData ) {
		if ( $this->isLockDataPresent( $lockData ) ) {
			update_option(
				self::CLONED_SITE_OPTION,
				[
					'stored_fingerprint'    => $lockData['stored_fingerprint'],
					'received_fingerprint'  => $lockData['received_fingerprint'],
					'fingerprint_confirmed' => $lockData['fingerprint_confirmed'],
				],
				'no'
			);
		}
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
