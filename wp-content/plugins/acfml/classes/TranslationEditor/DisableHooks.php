<?php

namespace ACFML\TranslationEditor;

use ACFML\FieldGroup\Mode;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use WPML\FP\Obj;
use function WPML\FP\spreadArgs;

class DisableHooks implements \IWPML_Backend_Action {

	/**
	 * @return void
	 */
	public function add_hooks() {
		Hooks::onFilter( 'wpml_tm_editor_exclude_posts', 10, 2 )
			->then( spreadArgs( [ $this, 'disableTranslationEditor' ] ) );
	}

	/**
	 * @param array $excludedPosts
	 * @param int[] $postIds
	 *
	 * @return array
	 */
	public function disableTranslationEditor( $excludedPosts, $postIds ) {
		// $getFirstFieldGroupWithLocalization :: int -> array|null
		$getFirstFieldGroupWithLocalization = function( $postId ) {
			return wpml_collect( acf_get_field_groups( [ 'post_id' => $postId ] ) )
				->first( Relation::propEq( Mode::KEY, Mode::LOCALIZATION ) );
		};

		// $reducer :: ( array, int ) -> array
		$appendNewExcludedIds = function( $carry, $postId ) use ( $getFirstFieldGroupWithLocalization ) {
			$group = $getFirstFieldGroupWithLocalization( $postId );

			if ( $group ) {
				$carry[ $postId ] = sprintf(
				/* translators: %1$s: ACF field group name. */
					esc_html__( 'This content must be translated manually due to the translation option you selected for the "%1$s" field group.', 'acfml' ),
					Obj::propOr( '', 'title', $group )
				);
			}

			return $carry;
		};

		return wpml_collect( $postIds )
			->diff( array_keys( $excludedPosts ) )
			->reduce( $appendNewExcludedIds, $excludedPosts );
	}
}
