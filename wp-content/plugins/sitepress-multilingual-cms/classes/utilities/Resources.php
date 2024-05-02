<?php

namespace WPML\Core\WP\App;

use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\LIB\WP\Nonce;
use function WPML\FP\partial;

class Resources {
	// enqueueApp :: string $app -> ( string $localizeData )
	public static function enqueueApp( $app ) {
		return function( $localize = null, $dependencies = [] ) use ( $app ) {
			\WPML\LIB\WP\App\Resources::enqueueWithDeps( $app, ICL_PLUGIN_URL, WPML_PLUGIN_PATH, ICL_SITEPRESS_VERSION, 'sitepress', $localize, $dependencies );
		};
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public static function enqueueGlobalVariable( $name, $value ) {
		$print_javascript_variable = function() use ( $name, $value ) {

			$value = Obj::over(
				Obj::lensPath( [ 'endpoints' ] ),
				Fns::map( function ( $endpoint ) {
					return [
						'endpoint' => $endpoint,
						'nonce'    => Nonce::create( $endpoint )
					];
				} ),
				$value
			);

			echo "<script type=\"text/javascript\">\n";
			echo $name . ' = ' . json_encode( $value );
			echo "</script>\n";
		};
		add_action('wp_print_scripts', $print_javascript_variable, 0);
	}
}

