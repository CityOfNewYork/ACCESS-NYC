<?php

namespace WPML\TM\ATE\ClonedSites;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\UIPage;

class ApiCommunication {

	const SITE_CLONED_ERROR = 426;

	const SITE_MOVED_OR_COPIED_MESSAGE = "WPML has detected a change in your site's URL. To continue translating your site, go to your <a href='%s'>WordPress Dashboard</a> and tell WPML if your site has been <a href='%s'>moved or copied</a>.";
	const SITE_MOVED_OR_COPIED_DOCS_URL = 'https://wpml.org/documentation/translating-your-contents/advanced-translation-editor/using-advanced-translation-editor-when-you-move-or-use-a-copy-of-your-site/?utm_source=plugin&utm_medium=gui&utm_campaign=wpmltm';

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
			$errorMessage = sprintf(
				__( self::SITE_MOVED_OR_COPIED_MESSAGE, 'sitepress-multilingual-cms' ),
				UIPage::getTMDashboard(),
				self::SITE_MOVED_OR_COPIED_DOCS_URL
			);

			return new \WP_Error( self::SITE_CLONED_ERROR, $errorMessage );
		}

		return $response;
	}

	/**
	 * @param string $endpointUrl
	 *
	 * @return \WP_Error|null
	 */
	public function checkCloneSiteLock( $endpointUrl = '' ) {
		$isOnWhiteList = function ( $endpointUrl ) {
			$endpointsWhitelist = \apply_filters( 'wpml_ate_locked_endpoints_whitelist', [] );

			return (bool) Lst::find( Str::includes( Fns::__, $endpointUrl ), $endpointsWhitelist );
		};

		if ( Lock::isLocked() && ! $isOnWhiteList( $endpointUrl ) ) {
			$errorMessage = sprintf( __( self::SITE_MOVED_OR_COPIED_MESSAGE, 'sitepress' ),
				UIPage::getTMDashboard(),
				self::SITE_MOVED_OR_COPIED_DOCS_URL
			);

			return new \WP_Error( self::SITE_CLONED_ERROR, $errorMessage );
		}

		return null;
	}

	private function handleClonedDetection( $error_data ) {
		$error = array_pop( $error_data );
		$this->lock->lock( $error );
	}
}
