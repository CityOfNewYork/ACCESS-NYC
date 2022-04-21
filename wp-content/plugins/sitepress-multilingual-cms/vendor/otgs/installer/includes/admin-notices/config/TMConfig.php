<?php

namespace OTGS\Installer\AdminNotices;

class TMConfig {
	public static function pages() {
		if ( ! defined( 'WPML_TM_FOLDER' ) ) {
			return [];
		}

		return [
			WPML_TM_FOLDER . '/menu/settings',
			WPML_TM_FOLDER . '/menu/main.php',
			WPML_TM_FOLDER . '/menu/translations-queue.php',
			WPML_TM_FOLDER . '/menu/string-translation.php',
			WPML_TM_FOLDER . '/menu/settings.php',
		];
	}
}
