<?php

namespace WPML\PB\Elementor\Hooks;

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Editor implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_is_editing_translation_with_native_editor', 10, 2 )
			->then(
				spreadArgs(
					function( $isTranslationWithNativeEditor, $translatedPostId ) {
						return $isTranslationWithNativeEditor
						  || (
							(int) Obj::prop( 'editor_post_id', $_POST ) === $translatedPostId
							&& Relation::propEq( 'action', 'elementor_ajax', $_POST )
						  );
					}
				)
			);

		Hooks::onAction( 'elementor/editor/footer' )
			->then( [ $this, 'maybeDisplayModalPageBuilderWarning' ] );
	}

	/**
	 * @return void
	 */
	public function maybeDisplayModalPageBuilderWarning() {
		$postId = (int) Obj::prop( 'post', $_GET );

		if ( is_user_logged_in() && $postId ) {
			do_action( 'wpml_maybe_display_modal_page_builder_warning', $postId, 'Elementor' );
		}
	}
}


