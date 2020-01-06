<?php

namespace WPML;

class Plugins {
	public static function loadCoreFirst() {
		$plugins = get_option( 'active_plugins' );

		$isSitePress = function( $value ) { return $value === WPML_PLUGIN_BASENAME; };

		$newOrder = wpml_collect( $plugins )
			->prioritize( $isSitePress )
			->values()
			->toArray();

		if ( $newOrder !== $plugins ) {
			update_option( 'active_plugins', $newOrder );
		}
	}
}
