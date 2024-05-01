<?php

namespace WPML\MediaTranslation;

class MediaSettings {
	private static $settings;
	private static $settings_option_key = '_wpml_media';
	private static $default_settings = [
		'version'                  => false,
		'media_files_localization' => [
			'posts'         => true,
			'custom_fields' => true,
			'strings'       => true,
		],
		'wpml_media_2_3_migration' => true,
		'setup_run'                => false,
	];

	public static function init_settings() {
		if ( ! self::$settings ) {
			self::$settings = get_option( self::$settings_option_key, [] );
		}

		self::$settings = array_merge( self::$default_settings, self::$settings );
	}


	public static function get_setting( $name, $default = false ) {
		self::init_settings();
		if ( ! isset( self::$settings[ $name ] ) || ! self::$settings[ $name ] ) {
			return $default;
		}

		return self::$settings[ $name ];
	}
}