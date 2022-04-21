<?php

namespace WPML\Installer;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class AddSiteUrl implements \IWPML_Backend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'otgs_installer_add_site_url', 10, 2 )
		     ->then( spreadArgs( function ( $url, $repoID ) {
			     return $repoID === 'wpml' ? ( $url . '&wpml_version=' . Obj::prop( 'Version', get_plugin_data( WPML_PLUGIN_PATH . '/' . WPML_PLUGIN_FILE ) ) ) : $url;
		     } ) );
	}
}
