<?php

namespace WPML\Compatibility\WPBakery;

use WPML\Element\API\PostTranslations;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML_Custom_Field_Setting_Factory;
use WPML_PB_Last_Translation_Edit_Mode;
use function WPML\FP\partial;
use function WPML\FP\partialRight;

class Styles implements \IWPML_Frontend_Action, \IWPML_Backend_Action {

	const META_CUSTOM_CSS = '_wpb_shortcodes_custom_css';

	/** @var WPML_Custom_Field_Setting_Factory $metaSettingFactory */
	private $metaSettingFactory;

	public function __construct( WPML_Custom_Field_Setting_Factory $metaSettingFactory ) {
		$this->metaSettingFactory = $metaSettingFactory;
	}

	public function add_hooks() {
		add_action( 'save_post', [ $this, 'copyCssFromOriginal' ] );
		add_action( 'init', [ $this, 'adjustMetaSetting' ] );
	}

	/**
	 * @param int $postId
	 */
	public function copyCssFromOriginal( $postId ) {
		// $ifUsingTranslationEditor :: int -> bool
		$ifUsingTranslationEditor = [ WPML_PB_Last_Translation_Edit_Mode::class, 'is_translation_editor' ];

		// $ifUsingWpBakery :: int -> bool
		$ifUsingWpBakery = partialRight( 'get_post_meta', '_wpb_vc_js_status', true );

		// $getCssValue :: int -> string
		$getCssValue = partialRight( 'get_post_meta', self::META_CUSTOM_CSS, true );

		// $setCssForTranslation :: int -> void
		$setCssForTranslation = partial( 'update_post_meta', $postId, self::META_CUSTOM_CSS );

		Maybe::of( $postId )
			->filter( $ifUsingTranslationEditor )
			->map( PostTranslations::getOriginalId() )
			->filter( $ifUsingWpBakery )
			->map( $getCssValue )
			->filter( Fns::identity() )
			->map( $setCssForTranslation );
	}

	/**
	 * As a general rule, we will copy the CSS meta field only once, so
	 * it will work fine and independently if the translation is done
	 * with the native WP editor. Otherwise, we will programmatically
	 * copy the CSS meta to the translation.
	 *
	 * This adjustment code is required since we are changing the original
	 * setting from "copy" to "copy_once" (it will also be updated on the
	 * remote config file).
	 */
	public function adjustMetaSetting() {
		$metaSetting = $this->metaSettingFactory->post_meta_setting( self::META_CUSTOM_CSS );

		if ( ! $metaSetting->is_unlocked() ) {
			$metaSetting->set_to_copy_once();
		}
	}
}
