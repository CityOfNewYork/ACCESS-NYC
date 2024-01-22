<?php

namespace WPML\TM\ATE\ClonedSites\Endpoints;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Lst;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\WordPress;
use WPML\TM\API\ATE\Account;
use WPML\TM\ATE\ClonedSites\Endpoints\GetCredits\AMSAPIFactory;
use WPML\TM\ATE\ClonedSites\FingerprintGeneratorForOriginalSite;
use WPML\TM\ATE\ClonedSites\Lock;
use function WPML\Container\make;

class GetCredits implements IHandler {

	/** @var AMSAPIFactory */
	private $amsAPIFactory;

	public function __construct( AMSAPIFactory $amsAPIFactory ) {
		$this->amsAPIFactory = $amsAPIFactory;
	}

	public function run( Collection $data ) {
		$getCredit = function () {
			try {
				$credits = $this->amsAPIFactory->create()->getCredits();

				return is_array( $credits )
					? Either::right( $credits )
					: Either::left( __( 'Communication error', 'sitepress' ) );
			} catch ( \Exception $e ) {
				return Either::left( __( 'Communication error', 'sitepress' ) );
			}
		};

		$addCreditEndpointToWhitelist = function ( $otherEndpoints ) {
			return Lst::append( \WPML_TM_ATE_AMS_Endpoints::ENDPOINTS_CREDITS, $otherEndpoints );
		};

		return Hooks::callWithFilter( $getCredit, 'wpml_ate_locked_endpoints_whitelist', $addCreditEndpointToWhitelist );
	}

}