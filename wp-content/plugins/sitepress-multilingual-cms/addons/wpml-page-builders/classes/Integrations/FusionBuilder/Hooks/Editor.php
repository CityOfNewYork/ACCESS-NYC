<?php

namespace WPML\Compatibility\FusionBuilder\Hooks;

use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Editor implements \IWPML_Backend_Action, \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_is_editing_translation_with_native_editor', 10, 2 )
			->then(
				spreadArgs(
					function( $isTranslationWithNativeEditor, $translatedPostId ) {
						return $isTranslationWithNativeEditor
						|| (
							Relation::propEq( 'action', 'fusion_app_save_post_content', $_POST )
							&& (int) Obj::prop( 'post_id', $_POST ) === $translatedPostId
						  );
					}
				)
			);

		Hooks::onAction( 'fusion_builder_enqueue_live_scripts' )
			 ->then( [ $this, 'displayModalPageBuilderWarning' ] );
	}

	/**
	 * @return void
	 */
	public function displayModalPageBuilderWarning() {
		if ( is_user_logged_in() && isset( $_GET['fb-edit'] ) && get_the_ID() ) {
			do_action( 'wpml_maybe_display_modal_page_builder_warning', get_the_ID(), 'Fusion Builder' );
		}
	}
}
