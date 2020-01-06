<?php

namespace WPML\ST\TranslationFile;

class EntryQueries {

	/**
	 * @param string $type
	 *
	 * @return \Closure
	 */
	public static function isType( $type ) {
		return function ( \WPML_ST_Translations_File_Entry $entry ) use ( $type ) {
			return $entry->get_component_type() === $type;
		};
	}

	/**
	 * @param $extension
	 *
	 * @return \Closure
	 */
	public static function isExtension( $extension ) {
		return function ( \WPML_ST_Translations_File_Entry $file ) use ( $extension ) {
			return $file->get_extension() === $extension;
		};
	}

	/**
	 * @return \Closure
	 */
	public static function getResourceName() {
		return function ( \WPML_ST_Translations_File_Entry $entry ) {
			$function = 'get' . ucfirst( $entry->get_component_type() ) . 'Name';
			return self::$function( $entry );
		};
	}

	/**
	 * @return \Closure
	 */
	public static function getDomain() {
		return function( \WPML_ST_Translations_File_Entry $file ) {
			return $file->get_domain();
		};
	}
	/**
	 * @param \WPML_ST_Translations_File_Entry $entry
	 *
	 * @return string
	 */
	private static function getPluginName( \WPML_ST_Translations_File_Entry $entry ) {
		$data = get_plugin_data( WP_PLUGIN_DIR . '/' . $entry->get_component_id(), false, false );
		return $data['Name'];
	}

	/**
	 * @param \WPML_ST_Translations_File_Entry $entry
	 *
	 * @return string
	 */
	private static function getThemeName( \WPML_ST_Translations_File_Entry $entry ) {
		return $entry->get_component_id();
	}

	/**
	 * @param \WPML_ST_Translations_File_Entry $entry
	 *
	 * @return mixed|string|void
	 */
	private static function getOtherName( \WPML_ST_Translations_File_Entry $entry ) {
		return 'WordPress';
	}

}
