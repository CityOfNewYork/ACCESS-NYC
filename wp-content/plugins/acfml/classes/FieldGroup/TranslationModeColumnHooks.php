<?php

namespace ACFML\FieldGroup;

use ACFML\Helper\FieldGroup;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class TranslationModeColumnHooks implements \IWPML_Backend_Action {

	const COLUMN_KEY = 'acfml-translation-options';

	/**
	 * We need to put a higher priority, because ACF will overwrite
	 * the columns on current_screen hook
	 */
	const COLUMN_HOOK_PRIORITY = 11;

	public function add_hooks() {
		if ( FieldGroup::isListScreen() ) {
			Hooks::onFilter( 'manage_acf-field-group_posts_columns', self::COLUMN_HOOK_PRIORITY )
				 ->then( spreadArgs( [ $this, 'translationOptionsColumTitle' ] ) );
			Hooks::onAction( 'manage_acf-field-group_posts_custom_column', 10, 2 )
				 ->then( spreadArgs( [ $this, 'translationOptionsColumContent' ] ) );
		}
	}

	/**
	 * @param array $columns
	 *
	 * @return array
	 */
	public function translationOptionsColumTitle( $columns ) {
		$columns[ self::COLUMN_KEY ] = __( 'Translation Option', 'acfml' );

		return $columns;
	}

	/**
	 * @param string $column
	 * @param int    $postId
	 *
	 * @return void
	 */
	public function translationOptionsColumContent( $column, $postId ) {
		if ( self::COLUMN_KEY === $column ) {
			echo wpml_collect( [
				Mode::ADVANCED     => esc_html__( 'Expert', 'acfml' ),
				Mode::TRANSLATION  => esc_html__( 'Same fields across languages', 'acfml' ),
				Mode::LOCALIZATION => esc_html__( 'Different fields across languages', 'acfml' ),
			] )->get( Mode::getMode( acf_get_field_group( $postId ) ), '__' );
		}
	}
}
