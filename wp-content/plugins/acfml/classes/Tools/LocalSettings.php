<?php

namespace ACFML\Tools;

use WPML\FP\Obj;

class LocalSettings {

	/**
	 * @var string name
	 */
	const SCAN_LOCAL_FILES = 'acfml_tools_local_settings_scan_files';

	/**
	 * @return bool
	 */
	public static function shouldRunScan() {
		$storedSetting = (bool) get_option( self::SCAN_LOCAL_FILES, defined( 'ACFML_SCAN_LOCAL_FIELDS' ) && constant( 'ACFML_SCAN_LOCAL_FIELDS' ) );
		if ( $storedSetting ) {
			return true;
		}

		$scanOnce = is_admin()
			// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
			&& 'acf-tools' === Obj::prop( 'page', $_GET )
			// phpcs:ignore WordPress.VIP.SuperGlobalInputUsage.AccessDetected
			&& LocalUI::SCAN_MODE_ONCE === Obj::prop( LocalUI::POST_SCAN_MODE, $_POST );
		if ( $scanOnce ) {
			return true;
		}

		return false;
	}

	/**
	 * @param bool $enabled
	 *
	 * @return void
	 */
	public static function enableScanMode( $enabled ) {
		update_option( self::SCAN_LOCAL_FILES, (bool) $enabled );
	}

	/**
	 * @return string
	 */
	public static function getScanMode() {
		$storedSetting =  (bool) get_option( self::SCAN_LOCAL_FILES, defined( 'ACFML_SCAN_LOCAL_FIELDS' ) && constant( 'ACFML_SCAN_LOCAL_FIELDS' ) );
		if ( $storedSetting ) {
			return LocalUI::SCAN_MODE_ALWAYS;
		}

		return LocalUI::SCAN_MODE_NONE;
	}
}
