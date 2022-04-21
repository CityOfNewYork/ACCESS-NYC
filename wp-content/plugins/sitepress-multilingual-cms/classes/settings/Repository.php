<?php

namespace WPML\TM\Settings;


class Repository {

	public static function getSetting( $indexes ) {
		$settings     = self::getAllSettings();

		/**
		 * I do not know why the foreach loop looks like that. I have just copied it from WPML_Translation_Job_Helper
		 * @todo Review it later and try simplify if possible
		 */
		foreach ( $indexes as $index ) {
			$settings = isset( $settings[ $index ] ) ? $settings[ $index ] : null;
			if ( ! isset( $settings ) ) {
				break;
			}
		}

		return $settings;
	}

	public static function getCustomFieldsToTranslate() {
		return array_values( array_filter( array_keys(
			self::getSetting( [ 'custom_fields_translation' ] ) ?: [],
			WPML_TRANSLATE_CUSTOM_FIELD
		) ) );
	}

	public static function getCustomFields() {
		return \wpml_collect( self::getSetting( [ 'custom_fields_translation' ] ) ?: [] )
			->filter( function ( $value, $key ) {
				return (bool) $key;
			} )->toArray();
	}

	/**
	 * @return array
	 */
	private static function getAllSettings() {
		/** @var \TranslationManagement $iclTranslationManagement */
		global $iclTranslationManagement;

		if ( ! $iclTranslationManagement ) {
			return [];
		}

		if ( empty( $iclTranslationManagement->settings ) ) {
			$iclTranslationManagement->init();
		}

		return $iclTranslationManagement->get_settings();
	}
}