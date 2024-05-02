<?php

namespace WPML\TM\ATE\ClonedSites\Endpoints\GetCredits;

use WPML\TM\ATE\ClonedSites\FingerprintGeneratorForOriginalSite;

class AMSAPIFactory {

	/**
	 * It creates an instance of \WPML_TM_AMS_API which uses a special Fingerprint generator.
	 * It let us make a call against the AMS API with the fingerprint of the original site.
	 *
	 * @return \WPML_TM_AMS_API
	 */
	public function create() {
		$lock = new \WPML\TM\ATE\ClonedSites\Lock();

		return new \WPML_TM_AMS_API(
			new \WP_Http(),
			new \WPML_TM_ATE_Authentication(),
			new \WPML_TM_ATE_AMS_Endpoints(),
			new \WPML\TM\ATE\ClonedSites\ApiCommunication( $lock ),
			new FingerprintGeneratorForOriginalSite( $lock )
		);
	}

}