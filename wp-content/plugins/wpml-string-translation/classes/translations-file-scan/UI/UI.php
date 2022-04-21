<?php

namespace WPML\ST\MO\Scan\UI;

use \WPML\ST\WP\App\Resources;
use WPML\LIB\WP\Hooks as WPHooks;

class UI {

	public static function add_hooks( callable $getModel, $isSTPage ) {
		WPHooks::onAction( 'admin_enqueue_scripts' )
			->then( $getModel )
			->then( [ self::class, 'localize' ] )
			->then( Resources::enqueueApp( 'mo-scan' ) );

		if ( ! $isSTPage ) {
			WPHooks::onAction( [ 'admin_notices', 'network_admin_notices' ] )
				->then( [ self::class, 'add_admin_notice' ] );
		}
	}

	public static function add_admin_notice() {
		?>
		<div id="wpml-mo-scan-st-page"></div>
		<?php
	}

	public static function localize( $model ) {
		return [
			'name' => 'wpml_mo_scan_ui_files',
			'data' => $model,
		];
	}
}
