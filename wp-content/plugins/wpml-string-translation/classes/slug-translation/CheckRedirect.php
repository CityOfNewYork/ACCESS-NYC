<?php

namespace WPML\ST\DisplayAsTranslated;

use WPML\FP\Lst;
use WPML\FP\Str;
use WPML\FP\Obj;
use WPML\LIB\WP\Hooks;
use WPML\LIB\WP\Post;
use function WPML\Container\make;
use function WPML\FP\spreadArgs;

class CheckRedirect implements \IWPML_Frontend_Action {

	public function add_hooks() {
		Hooks::onFilter( 'wpml_is_redirected', 10, 3 )
		     ->then( spreadArgs( [ self::class, 'checkForSlugTranslation' ] ) );
	}

	public static function checkForSlugTranslation( $redirect, $post_id, $q ) {
		global $sitepress;

		if ( $redirect ) {
			$postType = Post::getType( $post_id );
			if (
				make( \WPML_ST_Post_Slug_Translation_Settings::class )->is_translated( $postType )
				&& $sitepress->is_display_as_translated_post_type( $postType )
			) {
				$adjustLanguageDetails = Obj::set(
					Obj::lensProp( 'language_code' ), $sitepress->get_current_language()
				);

				add_filter( 'wpml_st_post_type_link_filter_language_details', $adjustLanguageDetails );

				if ( \WPML_Query_Parser::is_permalink_part_of_request(
					get_permalink( $post_id ),
					explode( '?', $_SERVER['REQUEST_URI'] )[0] )
				) {
					$redirect = false;
				}

				remove_filter( 'wpml_st_post_type_link_filter_language_details', $adjustLanguageDetails );
			}
		}

		return $redirect;
	}
}
