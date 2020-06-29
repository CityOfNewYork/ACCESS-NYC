<?php

namespace WPML\FP\System;

use WPML\FP\Either;

class System {

	public static function getPostData() {
		return function() { return Either::right( wpml_collect( $_POST ) ); };
	}
}
