<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

class Base {

	/**
	 * @param array $block
	 *
	 * @return array
	 */
	public function convert( array $block ) {
		return $block;
	}

	/**
	 * @param array|int $ids
	 * @param string    $elementSlug e.g. "page", "category", ...
	 * @param string    $elementType "post" or "taxonomy".
	 *
	 * @return array|int
	 */
	public static function convertIds( $ids, $elementSlug, $elementType ) {
		$isDisplayAsTranslated = self::isDisplayedAsTranslated( $elementSlug, $elementType );

		$getTranslation = function ( $id ) use ( $elementSlug, $isDisplayAsTranslated ) {
			$newId = (int) wpml_object_id_filter( $id, $elementSlug );

			if ( ! $newId && $isDisplayAsTranslated ) {
				return (int) $id;
			}

			return $newId;
		};

		if ( is_array( $ids ) ) {
			return wpml_collect( $ids )
				->map( $getTranslation )
				->toArray();
		}

		return $getTranslation( $ids );
	}

	/**
	 * @param string $slug
	 * @param string $type
	 *
	 * @return bool
	 */
	private static function isDisplayedAsTranslated( $slug, $type ) {
		/** @var \SitePress $sitepress */
		global $sitepress;

		return 'post' === $type
			? $sitepress->is_display_as_translated_post_type( $slug )
			: $sitepress->is_display_as_translated_taxonomy( $slug );
	}
}
