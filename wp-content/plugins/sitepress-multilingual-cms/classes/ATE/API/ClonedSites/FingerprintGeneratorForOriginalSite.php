<?php

namespace WPML\TM\ATE\ClonedSites;


use WPML\TM\ATE\API\FingerprintGenerator;
use WPML\FP\Obj;

/**
 * We need this class in order to be able to make an API calls against the original site ( the site that was cloned to the current url ).
 */
class FingerprintGeneratorForOriginalSite extends FingerprintGenerator {

	/**
	 * @var Lock
	 */
	private $lock;

	public function __construct( Lock $lock ) {
		$this->lock = $lock;
	}

	protected function getSiteUrl() {
		if ( Lock::isLocked() ) {
			return Obj::prop( 'urlCurrentlyRegisteredInAMS', $this->lock->getLockData() );
		}

		return parent::getSiteUrl();
	}

}