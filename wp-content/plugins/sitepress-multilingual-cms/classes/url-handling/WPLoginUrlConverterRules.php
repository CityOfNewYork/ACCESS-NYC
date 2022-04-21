<?php

namespace WPML\UrlHandling;

use WPML\LIB\WP\Option;

class WPLoginUrlConverterRules implements \IWPML_Action {

	const UPDATE_RULES_KEY = 'wpml_login_page_translation_update_rules';

	public function add_hooks() {
		if ( Option::getOr( self::UPDATE_RULES_KEY, false ) ) {
			add_filter( 'init', [ self::class, 'update' ] );
		}
	}

	public static function markRulesForUpdating() {
		Option::update( self::UPDATE_RULES_KEY, true );
	}

	public static function update() {
		global $wp_rewrite;

		if ( ! function_exists( 'save_mod_rewrite_rules' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		$wp_rewrite->rewrite_rules();
		save_mod_rewrite_rules();
		$wp_rewrite->flush_rules( false );

		Option::update( self::UPDATE_RULES_KEY, false );
	}
}