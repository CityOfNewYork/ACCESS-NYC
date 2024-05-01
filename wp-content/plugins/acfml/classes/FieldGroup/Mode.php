<?php

namespace ACFML\FieldGroup;

use WPML\API\Sanitize;
use WPML\FP\Obj;
use WPML\FP\Relation;

class Mode {

	const KEY = 'acfml_field_group_mode';

	const TRANSLATION  = 'translation';
	const LOCALIZATION = 'localization';
	const ADVANCED     = 'advanced'; // We also use the term "Expert" for that mode.

	const MIXED = 'mixed'; // Inconsistent modes attached to an entity.

	const ENTITY_POST     = 'post';
	const ENTITY_TAXONOMY = 'taxonomy';
	const ENTITY_OPTION   = 'option';

	/**
	 * If nothing is defined, it will default to "advanced".
	 *
	 * @param array|null $fieldGroup
	 *
	 * @return string|null
	 */
	public static function getMode( $fieldGroup ) {
		return Obj::prop( self::KEY, $fieldGroup );
	}

	/**
	 * @param string|null $mode
	 * @param array|null  $fieldGroup
	 *
	 * @return bool
	 */
	private static function is( $mode, $fieldGroup ) {
		return Relation::equals( $mode, self::getMode( $fieldGroup ) );
	}

	/**
	 * @param array|null $fieldGroup
	 *
	 * @return bool
	 */
	public static function isAdvanced( $fieldGroup ) {
		return self::is( self::ADVANCED, $fieldGroup )
			|| self::is( null, $fieldGroup );
	}

	/**
	 * @param string|null     $entityType
	 *
	 * @return string|null
	 */
	public static function getForFieldableEntity( $entityType = null, $id = null ) {
		$filter = wpml_collect( [
			self::ENTITY_POST     => [
				'post_id' => $id ?: Sanitize::stringProp( 'post', $_REQUEST )
			],
			self::ENTITY_TAXONOMY => [
				'taxonomy' => Sanitize::stringProp( 'taxonomy', $_REQUEST )
			],
			self::ENTITY_OPTION   => [
				'options_page' => Sanitize::stringProp( 'page', $_REQUEST )
			],
		] )->get( $entityType, [] );

		if ( $filter ) {
			return self::getForFieldGroups( acf_get_field_groups( $filter ) );
		}

		return null;
	}

	/**
	 * @param array  $fieldGroups
	 *
	 * @return string|null
	 */
	public static function getForFieldGroups( $fieldGroups ) {
		if ( ! $fieldGroups ) {
			return null;
		}

		return wpml_collect( $fieldGroups )
			->map( Obj::propOr( self::ADVANCED, self::KEY ) )
			->reduce( function( $carry, $value ) {
				if ( ! $carry || $carry === $value ) {
					return $value;
				}

				return self::MIXED;
			} );
	}
}
