<?php

namespace WPML\TaxonomyTermTranslation;

use WPML\Element\API\Languages;
use WPML\FP\Fns;
use WPML\FP\Maybe;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;
use WPML\LIB\WP\Hooks;
use WPML\Settings\PostType\Automatic;
use WPML\Setup\Option;
use function WPML\FP\pipe;
use function WPML\FP\spreadArgs;

class AutoSync implements \IWPML_Backend_Action, \IWPML_REST_Action, \IWPML_AJAX_Action {

	public function add_hooks() {
		if ( Option::shouldTranslateEverything() ) {
			Hooks::onAction( 'wpml_pro_translation_completed', 10, 3 )
				->then( spreadArgs( self::syncTaxonomyHierarchy() ) );

			remove_action( 'save_post', 'display_tax_sync_message' );
		}
	}

	/**
	 * @return \Closure (int, array, object) -> void
	 */
	private static function syncTaxonomyHierarchy() {
		return function( $newPostId, $fields, $job ) {
			// $isPostJob :: object -> bool
			$isPostJob = Relation::propEq( 'element_type_prefix', 'post' );

			// $getPostType :: object -> string
			$getPostType = pipe(
				Obj::prop( 'original_post_type' ),
				Str::replace( 'post_', '' )
			);

			// $isAutomaticPostType :: string -> bool
			$isAutomaticPostType = [ Automatic::class, 'isAutomatic' ];

			// $isTranslatableTax :: string -> bool
			$isTranslatableTax = function( $taxonomy ) {
				return \WPML_Element_Sync_Settings_Factory::createTax()->is_sync( $taxonomy );
			};

			// $syncTaxonomyHierarchies :: array -> void
			$syncTaxonomyHierarchies = function( $taxonomies ) {
				wpml_get_hierarchy_sync_helper( 'term' )->sync_element_hierarchy( $taxonomies, Languages::getDefaultCode() );
			};

			Maybe::of( $job )
				->filter( $isPostJob )
				->map( $getPostType )
				->filter( $isAutomaticPostType )
				->map( 'get_object_taxonomies' )
				->map( Fns::filter( $isTranslatableTax ) )
				->map( $syncTaxonomyHierarchies );
		};
	}
}
