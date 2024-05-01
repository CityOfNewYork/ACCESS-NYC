<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\API\PostTypes;
use WPML\Settings\PostType\Automatic;
use WPML\Setup\Option;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Templates implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	const POST_TYPE = 'elementor_library';

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onFilter( 'elementor/theme/conditions/cache/regenerate/query_args' )
			->then( spreadArgs( Obj::assoc( 'suppress_filters', true ) ) );

		if ( \is_admin() && self::shouldUpdateTemplatesOption() ) {
			Hooks::onAction( 'init' )
				->then( [ self::class, 'setTemplatesAutomaticallyTranslatable' ] );
		}
	}

	/**
	 * @return bool
	 */
	private static function shouldUpdateTemplatesOption() {
		if ( ! Option::getTranslateEverything() ) {
			return false;
		}

		$templatesOptionIsUnlocked = apply_filters( 'wpml_sub_setting', false, 'custom_posts_unlocked_option', self::POST_TYPE );
		if ( $templatesOptionIsUnlocked ) {
			return false;
		}

		$translatableOnlyPostTypes = PostTypes::getOnlyTranslatable();
		if ( ! in_array( self::POST_TYPE, $translatableOnlyPostTypes, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @return void
	 */
	public static function setTemplatesAutomaticallyTranslatable() {
		$settingsHelper = wpml_load_settings_helper();

		$settingsHelper->set_post_type_translatable( self::POST_TYPE );
		// If the option is locked, WPML will overwrite its option to display_as_translated in WPML->Settings.
		$settingsHelper->set_post_type_translation_unlocked_option( self::POST_TYPE, true );

		Automatic::set( self::POST_TYPE, true );
	}
}
