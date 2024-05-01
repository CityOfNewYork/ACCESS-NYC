<?php

namespace ACFML\FieldGroup;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslationEditorHooks implements \IWPML_Backend_Action {

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onFilter( 'wpml_use_tm_editor', 10, 2 )
			->then( spreadArgs( [ $this, 'disableTranslationEditor' ] ) );
	}

	/**
	 * @param bool $useEditor
	 * @param int  $postId
	 *
	 * @return bool
	 */
	public function disableTranslationEditor( $useEditor, $postId ) {
		$alreadyDisabled = ! $useEditor;

		return $alreadyDisabled ? false : Mode::LOCALIZATION !== Mode::getForFieldableEntity( 'post', $postId );
	}
}
