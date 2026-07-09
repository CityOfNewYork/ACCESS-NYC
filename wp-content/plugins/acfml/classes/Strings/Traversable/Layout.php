<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Config;

class Layout extends Entity {

	/** @var string $idKey */
	protected $idKey = 'key';

	/**
	 * @return array
	 */
	protected function getConfig() {
		return Config::getForLayout();
	}
}
