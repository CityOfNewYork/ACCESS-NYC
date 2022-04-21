<?php

namespace WPML\TM\API\ATE;

use WPML\FP\Either;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\WordPress;
use WPML\WP\OptionManager;
use function WPML\Container\make;

class Account {
	/**
	 * @return array
	 */
	public static function getCredits() {
		return WordPress::handleError( make( \WPML_TM_AMS_API::class )->getCredits() )
		                ->filter( Fns::identity() )
		                ->map( Fns::tap( OptionManager::update( 'TM', 'Account::credits' ) ) )
		                ->alt( Either::of( [ 'error' => 'communication error' ] ) )
		                ->get();
	}

	/**
	 * @param array $creditInfo
	 *
	 * @return bool
	 */
	public static function hasActiveSubscription( array $creditInfo ) {
		return (bool) Obj::propOr( false, 'active_subscription', $creditInfo );
	}

	/**
	 * @param array $creditInfo
	 *
	 * @return int
	 */
	public static function getAvailableBalance( array $creditInfo ) {
		return (int) Obj::propOr( 0, 'available_balance', $creditInfo );
	}

	/**
	 * @return bool
	 */
	public static function isAbleToTranslateAutomatically() {
		$creditInfo = OptionManager::getOr( [], 'TM', 'Account::credits' );

		if ( ! array_key_exists( 'active_subscription', $creditInfo ) ) {
			$creditInfo = self::getCredits();
		}

		return self::hasActiveSubscription( $creditInfo ) || self::getAvailableBalance( $creditInfo ) > 0;
	}
}
