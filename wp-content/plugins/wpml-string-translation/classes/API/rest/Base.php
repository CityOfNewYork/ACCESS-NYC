<?php

namespace WPML\ST\Rest;

abstract class Base extends \WPML\Rest\Base {

	const NAMESPACE = 'wpml/st/v1';

	/**
	 * @return string
	 */
	public function get_namespace() {
		return self::NAMESPACE;
	}
}
