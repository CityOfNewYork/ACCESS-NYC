<?php

namespace WPML\PB\Gutenberg\ConvertIdsInBlock;

use WPML\Convert\Ids;

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
	 * @see Ids::convert(), supports string lists with any separator.
	 *
	 * @param array|int|string $ids
	 * @param string           $elementSlug e.g. "page", "category", ...
	 * @param string|null      $elementType "post" or "taxonomy".
	 *
	 * @return array|int
	 */
	public static function convertIds( $ids, $elementSlug, $elementType = null ) {
		// In this context, it's probably better to always fall back to original.
		// But for backward compatibility, we'll decide that based on the translation mode
		// when the $elementType is explicitly passed.
		$fallbackToOriginal = $elementType ? self::isDisplayedAsTranslated( $elementSlug, $elementType ) : true;

		return Ids::convert( $ids, $elementSlug, $fallbackToOriginal );
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
