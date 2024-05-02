<?php

namespace ACFML\FieldGroup;

use ACFML\Notice\Links;
use WPML\FP\Fns;
use WPML\FP\Logic;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Post;

class DetectNonTranslatableLocations {

	const KEY = 'acfml_non_translatable_locations';

	const PROP_DISMISSED = 'dismissed';
	const PROP_HASH      = 'hash';
	const PROP_DETECTED  = 'detected';

	const DETECTED_POST_TYPE = 'post';
	const DETECTED_TAXONOMY  = 'taxonomy';

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function process( $fieldGroup ) {
		$fieldGroupId = (int) Obj::prop( 'ID', $fieldGroup );
		$locations    = (array) Obj::prop( 'location', $fieldGroup );
		$hash         = md5( wp_json_encode( $locations ) );

		if ( self::get( $fieldGroupId, self::PROP_HASH ) !== $hash ) {
			self::updateAll(
				$fieldGroupId,
				[
					self::PROP_HASH      => $hash,
					self::PROP_DISMISSED => false,
					self::PROP_DETECTED  => $this->findNonTranslatableLocation( $locations ),
				]
			);
		}
	}

	/**
	 * As there are an infinite range of possibilities
	 * for locations, we cannot detect all cases.
	 *
	 * We'll limit ourselves to positive comparisons,
	 * and only for CPTs and taxonomies.
	 *
	 * @param array[] $locations
	 *
	 * @return null|string
	 */
	private function findNonTranslatableLocation( $locations ) {
		$isNotPositiveComparison = Logic::complement( Relation::propEq( 'operator', '==' ) );
		$isPostType              = Relation::propEq( 'param', 'post_type' );
		$isTaxonomy              = Relation::propEq( 'param', 'taxonomy' );
		$getValue                = Obj::prop( 'value' );

		foreach ( $locations as $locationGroup ) {
			foreach ( $locationGroup as $location ) {
				if ( $isNotPositiveComparison( $location ) ) {
					continue;
				} elseif ( $isPostType( $location ) && ! apply_filters( 'wpml_is_translated_post_type', true, $getValue( $location ) ) ) {
					return self::DETECTED_POST_TYPE;
				} elseif ( $isTaxonomy( $location ) && ! apply_filters( 'wpml_is_translated_taxonomy', true, $getValue( $location ) ) ) {
					return self::DETECTED_TAXONOMY;
				}
			}
		}

		return null;
	}

	/**
	 * @param int $fieldGroupId
	 *
	 * @return null|string
	 */
	public static function getDetectedType( $fieldGroupId ) {
		$get = Obj::prop( Fns::__, self::getAll( $fieldGroupId ) );

		if ( $get( self::PROP_DISMISSED ) ) {
			return null;
		}

		return $get( self::PROP_DETECTED );
	}

	/**
	 * @param null|string $nonTranslatableType
	 *
	 * @return string
	 */
	public static function getTitle( $nonTranslatableType ) {
		return DetectNonTranslatableLocations::DETECTED_TAXONOMY === $nonTranslatableType
			? esc_html__( 'Set translation preferences for the attached taxonomy', 'acfml' )
			: esc_html__( 'Set translation preferences for the attached post type', 'acfml' );
	}

	/**
	 * @param null|string $nonTranslatableType
	 *
	 * @return string
	 */
	public static function getDescription( $nonTranslatableType ) {
		return DetectNonTranslatableLocations::DETECTED_TAXONOMY === $nonTranslatableType
			? esc_html__( 'If you want to translate your fields, go to the WPML Settings page and make the taxonomy attached to this field group translatable. ', 'acfml' )
			: sprintf(
				/* translators: %1$s and %2$s will wrap the string in a <a> link html tag */
				esc_html__( 'If you want to translate your fields, go to the WPML Settings page and %1$smake the post type attached to this field group translatable%2$s.', 'acfml' ),
				'<a href="' . Links::getDocTranslatePostType() . '" class="wpml-external-link" target="_blank">',
				'</a>'
			);
	}

	/**
	 * @param int $fieldGroupId
	 *
	 * @return void
	 */
	public static function dismiss( $fieldGroupId ) {
		self::updateAll( $fieldGroupId, Obj::assoc( self::PROP_DISMISSED, true, self::getAll( $fieldGroupId ) ) );
	}

	/**
	 * @param int    $fieldGroupId
	 * @param string $prop
	 *
	 * @return mixed
	 */
	private static function get( $fieldGroupId, $prop ) {
		return Obj::prop( $prop, self::getAll( $fieldGroupId ) );
	}

	/**
	 * @param int $fieldGroupId
	 *
	 * @return array
	 */
	private static function getAll( $fieldGroupId ) {
		return (array) Post::getMetaSingle( $fieldGroupId, self::KEY );
	}

	/**
	 * @param int   $fieldGroupId
	 * @param array $data
	 *
	 * @return void
	 */
	private static function updateAll( $fieldGroupId, $data ) {
		Post::updateMeta( $fieldGroupId, self::KEY, $data );
	}
}
