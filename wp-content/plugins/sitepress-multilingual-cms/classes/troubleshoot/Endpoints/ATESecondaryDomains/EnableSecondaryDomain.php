<?php

namespace WPML\TM\Troubleshooting\Endpoints\ATESecondaryDomains;

use WPML\Ajax\IHandler;
use WPML\Collect\Support\Collection;
use WPML\FP\Either;
use WPML\FP\Lst;
use WPML\TM\ATE\API\FingerprintGenerator;
use WPML\TM\ATE\ClonedSites\Lock;
use WPML\TM\ATE\ClonedSites\SecondaryDomains;
use function WPML\Container\make;

class EnableSecondaryDomain implements IHandler {

	/** @var Lock */
	private $lock;

	/** @var SecondaryDomains */
	private $secondsDomains;

	public function __construct( Lock $lock, SecondaryDomains $secondsDomains ) {
		$this->lock           = $lock;
		$this->secondsDomains = $secondsDomains;
	}

	public function run( Collection $data ) {
		$lockData = $this->lock->getLockData();

		$this->secondsDomains->add( $lockData['urlUsedToMakeRequest'], $lockData['urlCurrentlyRegisteredInAMS'] );
		$this->lock->unlock();

		return Either::of( true );
	}
}