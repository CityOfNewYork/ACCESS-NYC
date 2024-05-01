<?php

namespace ACFML\Helper;

use function WPML\FP\partial;

class Resources {

	/**
	 * @param string $app
	 *
	 * @return callable|\Closure
	 */
	public static function enqueueApp( $app ) {
		return partial( [ '\WPML\LIB\WP\App\Resources', 'enqueue' ],
			$app, ACFML_PLUGIN_URL, ACFML_PLUGIN_PATH, ACFML_VERSION, 'acfml'
		);
	}
}
