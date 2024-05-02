<?php

namespace ACFML\Strings\Traversable;

use ACFML\Strings\Config;
use ACFML\Strings\Helper\ContentTypeLabels;
use WPML\FP\Obj;

class OptionsPage extends Entity {

	/** @var string $idKey */
	protected $idKey = 'menu_slug';

	/**
	 * @return array
	 */
	protected function getConfig() {
		return Config::getForOptionsPage();
	}

}
