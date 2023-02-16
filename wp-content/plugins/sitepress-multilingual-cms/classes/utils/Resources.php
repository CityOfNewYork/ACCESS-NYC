<?php

namespace WPML\TM\WP\App;

use function WPML\FP\partial;

class Resources {
	// enqueueApp :: string $app -> ( string $localizeData )
	public static function enqueueApp( $app ) {
		return partial( [ '\WPML\LIB\WP\App\Resources', 'enqueue' ],
			$app, WPML_TM_URL, WPML_TM_PATH, WPML_TM_VERSION, 'wpml-translation-management'
		);
	}
}

