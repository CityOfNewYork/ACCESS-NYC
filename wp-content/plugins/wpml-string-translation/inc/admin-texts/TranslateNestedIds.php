<?php

namespace WPML\ST\AdminTexts;

use WPML\Convert\Ids;
use WPML\FP\Obj;
use WPML\FP\Lst;
use WPML\FP\Str;

class TranslateNestedIds {

	const TYPE_POST_IDS     = 'post-ids';
	const TYPE_TAXONOMY_IDS = 'taxonomy-ids';

	/** @var \SitePress $sitepress */
	private $sitepress;

	/**
	 * @param \SitePress $sitepress
	 */
	public function __construct( $sitepress ) {
		$this->sitepress = $sitepress;
	}

	/**
	 * @see Ids::convert(), supports string lists with any separator.
	 *
	 * @param array|int|string $ids
	 * @param string           $elementType "post-ids" or "taxonomy-ids".
	 * @param string           $elementSlug e.g. "page", "category", ...
	 *
	 * @return array|int|string
	 */
	public function convertIds( $ids, $elementType, $elementSlug ) {
		$fallbackToOriginal = $this->isDisplayedAsTranslated( $elementType, $elementSlug );
		return Ids::convert( $ids, $elementSlug, $fallbackToOriginal );
	}

	/**
	 * @param array|string|int $entry
	 * @param array            $path e.g. [ 'nested_key_1', 'nested_key_1_1', ... ]. An empty path means that $entry itself holds the translatable IDs.
	 * @param string           $elementType "post-ids" or "taxonomy-ids".
	 * @param string           $elementSlug e.g. "page", "category", ... Can be "any_post" or "any_term".
	 *
	 * @return array|string|int
	 */
	public function convertByPath( $entry, $path, $elementType, $elementSlug ) {
		$currentKey  = reset( $path );
		/** @var array $nextPath */
		$nextPath    = Lst::drop( 1, $path );
		$hasWildCard = false !== strpos( $currentKey, '*' );

		if ( $hasWildCard && is_array( $entry ) ) {
			$regex = $this->getWildcardRegex( $currentKey );

			foreach ( $entry as $key => $attr ) {
				if ( Str::match( $regex, $key ) ) {
					$entry[ $key ] = $this->convertByPath( $attr, $nextPath, $elementType, $elementSlug );
				}
			}
		} elseif ( $currentKey && isset( $entry[ $currentKey ] ) ) {
			$entry[ $currentKey ] = $this->convertByPath( $entry[ $currentKey ], $nextPath, $elementType, $elementSlug );
		} elseif ( ! $nextPath ) {
			$entry = $this->convertIds( $entry, $elementType, $elementSlug );
		}

		return $entry;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	private function getWildcardRegex( $key ) {
		return '/^' . str_replace( '*', 'S+', preg_quote( $key, '/' ) ) . '$/';
	}

	/**
	 * @param string $type
	 * @param string $slug
	 *
	 * @return bool
	 */
	private function isDisplayedAsTranslated( $type, $slug ) {
		if ( in_array( $slug, [ Ids::ANY_POST, Ids::ANY_TERM ], true ) ) {
			return true;// TODO Check this, it aligns with blocks and shortcodes, BTW.
		}
		if ( self::TYPE_POST_IDS === $type && $this->sitepress->is_display_as_translated_post_type( $slug ) ) {
			return true;
		}
		if ( self::TYPE_TAXONOMY_IDS === $type && $this->sitepress->is_display_as_translated_taxonomy( $slug ) ) {
			return true;
		}
		return false;
	}

}
