<?php

namespace WPML\ST\WP\App;

use function WPML\FP\partial;

class Resources {
	// enqueueApp :: string $app -> ( string $localizeData )
	public static function enqueueApp( $app ) {
		return partial(
			[ '\WPML\LIB\WP\App\Resources', 'enqueue' ],
			$app,
			WPML_ST_URL,
			WPML_ST_PATH,
			WPML_ST_VERSION,
			'wpml-string-translation'
		);
	}
}
