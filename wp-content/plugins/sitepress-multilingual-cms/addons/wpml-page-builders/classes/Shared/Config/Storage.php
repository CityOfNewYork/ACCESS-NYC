<?php

namespace WPML\PB\Config;

use WPML\WP\OptionManager;

class Storage {

	const OPTION_GROUP = 'api-pb-config';

	/** @var OptionManager $optionManager */
	private $optionManager;

	/** @var string $pbKey */
	private $pbKey;

	public function __construct(
		OptionManager $optionManager,
		$pbKey
	) {
		$this->optionManager = $optionManager;
		$this->pbKey         = $pbKey;
	}

	public function get() {
		return $this->optionManager->get( self::OPTION_GROUP, $this->pbKey, [] );
	}

	public function update( array $pbConfig ) {
		$this->optionManager->set( self::OPTION_GROUP, $this->pbKey, $pbConfig, false );
	}
}
