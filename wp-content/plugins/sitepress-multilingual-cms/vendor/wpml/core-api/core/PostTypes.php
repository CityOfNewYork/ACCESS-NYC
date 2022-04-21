<?php

namespace WPML\API;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\LIB\WP\PostType;
use WPML\Settings\PostType\Automatic;

class PostTypes {

	/**
	 * @return array  eg. [ 'page', 'post' ]
	 */
	public static function getTranslatable() {
		global $sitepress;

		return Obj::keys( $sitepress->get_translatable_documents() );
	}

	/**
	 * @return array  eg. [ 'page', 'post' ]
	 */
	public static function getDisplayAsTranslated() {
		global $sitepress;

		return Obj::keys( $sitepress->get_display_as_translated_documents() );
	}

	/**
	 * Gets post types that are translatable and excludes ones that are display as translated.
	 *
	 * @return array  eg. [ 'page', 'post' ]
	 */
	public static function getOnlyTranslatable() {
		return Obj::values( Lst::diff( self::getTranslatable(), self::getDisplayAsTranslated() ) );
	}

	/**
	 * Gets post types that are automatically translatable.
	 *
	 * @return array  eg. [ 'page', 'post' ]
	 */
	public static function getAutomaticTranslatable() {
		return Fns::filter( [ Automatic::class, 'isAutomatic' ], self::getOnlyTranslatable() );
	}

	public static function withNames( $postTypes ) {
		$getPostTypeName = function ( $postType ) {
			return PostType::getPluralName( $postType )->getOrElse( $postType );
		};
		return Fns::map( $getPostTypeName, $postTypes );
	}
}
