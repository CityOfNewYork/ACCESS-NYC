<?php

namespace OTGS\Installer;

class Settings {

	public static function load() {
		$settings = get_option( 'wp_installer_settings' );

		if ( is_array( $settings ) || empty( $settings ) ) { //backward compatibility 1.1
			return $settings;
		} else {
			$settings = base64_decode( $settings );
			if ( self::is_gz_on() ) {
				$settings = gzuncompress( $settings );
			}
			return unserialize( $settings );
		}

	}

	public static function save( $settings ) {
		$settings = serialize( $settings );
		if ( self::is_gz_on() ) {
			$settings = gzcompress( $settings );
		}
		$settings = base64_encode( $settings );

		update_option( 'wp_installer_settings', $settings, 'no' );
	}

	public static function is_gz_on() {
		return function_exists( 'gzuncompress' ) && function_exists( 'gzcompress' );
	}

}
