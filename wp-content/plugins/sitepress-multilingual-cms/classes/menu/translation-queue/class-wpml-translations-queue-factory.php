<?php

use function WPML\Container\make;
use WPML\TM\Editor\Editor;

/**
 * @author OnTheGo Systems
 */
class WPML_Translations_Queue_Factory {
	/**
	 * @return \WPML_Translations_Queue|null
	 */
	public function create() {
		global $sitepress;

		if ( ! $sitepress ) {
			return null;
		}

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

		$screen_options_factory = new WPML_UI_Screen_Options_Factory( $sitepress );

		return new WPML_Translations_Queue(
			$sitepress,
			$screen_options_factory,
			make( Editor::class )
		);
	}
}
