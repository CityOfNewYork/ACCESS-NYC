<?php

namespace WPML\ST\Rest;

abstract class Base extends \WPML\Rest\Base {

	/**
	 * @return string
	 */
	public function get_namespace() {
		return 'wpml/st/v1';
	}
}