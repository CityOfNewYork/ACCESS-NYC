<?php

namespace WPML\API;

use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Relation;
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
	 * Get an array of post types where keys are like: 'post', 'page' and so on
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	public static function getTranslatableWithInfo() {
		global $sitepress;

		$postTypes = $sitepress->get_translatable_documents( true );
		return \apply_filters( 'wpml_get_translatable_types', $postTypes );
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
	 * Attachment post type is excluded.
	 *
	 * @return array  eg. [ 'page', 'post' ]
	 */
	public static function getAutomaticTranslatable() {
		$filters = Logic::allPass( [
			[ Automatic::class, 'isAutomatic' ],
			Logic::complement( Relation::equals( 'attachment' ) )
		] );

		return Fns::filter( $filters, self::getOnlyTranslatable() );
	}

	public static function withNames( $postTypes ) {
		$getPostTypeName = function ( $postType ) {
			return PostType::getPluralName( $postType )->getOrElse( $postType );
		};
		return Fns::map( $getPostTypeName, $postTypes );
	}
}
