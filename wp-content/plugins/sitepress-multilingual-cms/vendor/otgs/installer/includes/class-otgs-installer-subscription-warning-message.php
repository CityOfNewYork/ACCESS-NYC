<?php

namespace OTGS\Installer;

use OTGS\Installer\FP\Obj;
use OTGS\Installer\FP\Str;
use OTGS_Installer_Subscription;

use function OTGS\Installer\FP\pipe;

class Subscription_Warning_Message {

	private $wpInstaller;
	private $settings;
	private $menuURL;

	public function __construct( \WP_Installer $wpInstaller ) {
		$this->wpInstaller = $wpInstaller;
		$this->settings    = $wpInstaller->settings;
		$this->menuURL     = $wpInstaller::menu_url();
	}

	public function get( $repositoryId, $subscriptionId ) {
		$subscription     = Obj::path( [ 'repositories', $repositoryId, 'subscription' ], $this->settings );
		$subscriptionData = Obj::prop( 'data', $subscription );
		$repositoryData   = Obj::path( [ 'repositories', $repositoryId, 'data' ], $this->settings );
		$product          = Obj::prop( 'product-name', $repositoryData);
		$repositoryURL    = Obj::prop( 'url', $repositoryData ) . '/account/?utm_source=plugin&utm_medium=gui&utm_campaign=installer&utm_term=expiring-soon';

		$subscriptionId        = $subscriptionId ?: Obj::propOr( 'subscription_type', $subscriptionData );
		$expires               = Obj::prop( 'expires', $subscriptionData );
		$doesntHaveAutoRenewal = isset( $subscriptionData ) && Obj::has( 'hasAutoRenewal', $subscriptionData ) && Obj::prop( 'hasAutoRenewal', $subscriptionData ) === false;

		$neverExpires = isset( $subscription ) && isset( $subscriptionData ) && ! isset( $expires )
		                && (
			                (int) $subscriptionData->status === OTGS_Installer_Subscription::SUBSCRIPTION_STATUS_ACTIVE_NO_EXPIRATION ||
			                (int) $subscriptionData->status === OTGS_Installer_Subscription::SUBSCRIPTION_STATUS_ACTIVE
		                );
		if ( $this->wpInstaller->repository_has_valid_subscription( $repositoryId ) && ! $neverExpires ) {
			$subscriptionExpirationPath = [ 'subscriptions_meta', 'expiration', $subscriptionId ];

			// Returns true if warning property length > 0 and false otherwise
			$warningPropertyLength = function ( $propertyName ) use ( $repositoryData, $subscriptionExpirationPath ) {
				if ( Obj::hasPath( $subscriptionExpirationPath, $repositoryData ) ) {
					$warningPropertyPath = array_merge( $subscriptionExpirationPath, [ $propertyName ] );

					$warningPropertyPathLength = pipe( Obj::path( $warningPropertyPath ), Str::len() );

					return $warningPropertyPathLength( $repositoryData ) > 0;
				}

				return false;
			};

			$warningDaysSet    = $warningPropertyLength( 'days_warning' );
			$warningMessageSet = $warningPropertyLength( 'warning_message' );

			if ( $warningDaysSet && $warningMessageSet ) {
				$daysWarning   = Obj::path( array_merge( $subscriptionExpirationPath, [ 'days_warning' ] ), $repositoryData );
				$customMessage = Obj::path( array_merge( $subscriptionExpirationPath, [ 'warning_message' ] ), $repositoryData );
			} else {
				// defaults
				$daysWarning = 30;

				$customMessage = "<a style='margin-left:0px;' href='{$repositoryURL}' target='_blank'>" . __( 'Renew today', 'installer' ) . '</a>' . ' ' . __( 'to protect your site from breaking changes in future WordPress releases.', 'installer' ) . '<br>';
			}

			if ( strtotime( $expires ) < strtotime( sprintf( '+%d day', $daysWarning ) ) ) {
				if ( $doesntHaveAutoRenewal || ! Obj::has( 'hasAutoRenewal', $subscriptionData ) ) {
					$daysToExpiration = (int) ceil( ( strtotime( $expires ) - time() ) / 86400 );

					$message = sprintf( _n( 'Your %s account expires in %d day.', 'Your %s account expires in %d days.', $daysToExpiration, 'installer' ), $product, $daysToExpiration );

					return $message . ' ' . $customMessage;
				}
			}
		}

		return '';
	}
}