<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Config;

class Layout extends Entity {

	/**
	 * @return array
	 */
	protected function getConfig() {
		return Config::getForLayout();
	}
}
