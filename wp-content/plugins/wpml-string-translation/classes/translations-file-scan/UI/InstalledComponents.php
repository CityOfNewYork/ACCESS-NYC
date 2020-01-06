<?php
/**
 * @author OnTheGo Systems
 */

namespace WPML\ST\MO\Scan\UI;

use WPML\Collect\Support\Collection;
use WPML_ST_Translations_File_Entry;

class InstalledComponents {

	/**
	 * @param Collection $components Collection of WPML_ST_Translations_File_Entry objects.
	 *
	 * @return Collection
	 */
	public static function filter( Collection $components ) {
		return $components
			->reject( self::isPluginMissing() )
			->reject( self::isThemeMissing() );
	}

	/**
	 * WPML_ST_Translations_File_Entry -> bool
	 *
	 * @return \Closure
	 */
	public static function isPluginMissing() {
		return function( WPML_ST_Translations_File_Entry $entry ) {
			return 'plugin' === $entry->get_component_type()
			       && ! is_readable( WP_PLUGIN_DIR . '/' . $entry->get_component_id() );
		};
	}

	/**
	 * WPML_ST_Translations_File_Entry -> bool
	 *
	 * @return \Closure
	 */
	public static function isThemeMissing() {
		return function( WPML_ST_Translations_File_Entry $entry ) {
			return 'theme' === $entry->get_component_type()
			       && ! wp_get_theme( $entry->get_component_id() )->exists();
		};
	}
}
