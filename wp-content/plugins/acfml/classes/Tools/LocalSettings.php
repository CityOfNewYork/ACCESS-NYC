<?php

namespace ACFML\Tools;

class LocalSettings {

	/**
	 * @var string name
	 */
	const SCAN_LOCAL_FILES = 'acfml_tools_local_settings_scan_files';

	/**
	 * @return bool
	 */
	public static function isScanModeEnabled() {
		return (bool) get_option( self::SCAN_LOCAL_FILES, defined( 'ACFML_SCAN_LOCAL_FIELDS' ) && constant( 'ACFML_SCAN_LOCAL_FIELDS' ) );
	}

	/**
	 * @param bool $enabled
	 *
	 * @return void
	 */
	public static function enableScanMode( $enabled ) {
		update_option( self::SCAN_LOCAL_FILES, (bool) $enabled );
	}
}
