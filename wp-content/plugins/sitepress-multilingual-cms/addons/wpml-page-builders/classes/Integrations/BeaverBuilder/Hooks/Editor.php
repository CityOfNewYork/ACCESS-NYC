<?php

namespace WPML\PB\BeaverBuilder\Hooks;

use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Editor implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_pb_is_editing_translation_with_native_editor', 10, 2 )
			->then( spreadArgs( function( $isTranslationWithNativeEditor, $translatedPostId ) {
				return $isTranslationWithNativeEditor
					|| (
							Obj::path( [ 'fl_builder_data', 'action' ], $_POST ) === 'save_layout'
							&& (int) Obj::path( [ 'fl_builder_data', 'post_id' ], $_POST ) === $translatedPostId
				       );

			} ) );
	}
}
