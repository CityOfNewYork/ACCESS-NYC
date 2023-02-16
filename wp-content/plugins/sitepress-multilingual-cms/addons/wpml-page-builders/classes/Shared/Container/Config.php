<?php

namespace WPML\PB\Container;

class Config {

	public static function getSharedClasses() {
		return [
			'\WPML_PB_Factory',
			'\WPML_PB_Integration',
		];
	}
}