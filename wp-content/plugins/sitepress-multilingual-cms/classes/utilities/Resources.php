<?php

namespace WPML\Core\WP\App;

use function WPML\FP\partial;

class Resources {
	// enqueueApp :: string $app -> ( string $localizeData )
	public static function enqueueApp( $app ) {
		return partial( [ '\WPML\LIB\WP\App\Resources', 'enqueue' ],
			$app, ICL_PLUGIN_URL, WPML_PLUGIN_PATH, ICL_SITEPRESS_VERSION, 'sitepress'
		);
	}
}

