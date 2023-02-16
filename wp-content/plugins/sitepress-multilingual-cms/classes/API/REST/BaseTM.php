<?php

namespace WPML\TM\REST;

abstract class Base extends \WPML\Rest\Base {

	/**
	 * @return string
	 */
	public function get_namespace() {
		return 'wpml/tm/v1';
	}
}
