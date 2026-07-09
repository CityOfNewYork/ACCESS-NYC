<?php

namespace ACFML\FieldGroup;

use ACFML\Helper\Fields;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\FP\Str;

class FieldNamePatterns {

	const OPTION_KEY = 'acfml_field_name_patterns';

	/**
	 * @var array
	 */
	private $localPatterns = [];

	/**
	 * @var array $cachedMatches
	 */
	private $cachedMatches = [];

	/**
	 * @var array $cachedLocalMatches
	 */
	private $cachedLocalMatches = [];

	/**
	 * @param array $fieldGroup
	 *
	 * @return void
	 */
	public function updateFieldNamePatterns( $fieldGroup ) {
		$namePatterns = wpml_collect();

		if ( ! Mode::isAdvanced( $fieldGroup ) ) {
			$getFieldNamePattern = function( $field, $fieldPattern ) use ( $namePatterns ) {
				$namePatterns->push( $fieldPattern );

				return $field;
			};

			Fields::iterate( acf_get_fields( $fieldGroup ), $getFieldNamePattern, Fns::identity() );
		}

		$this->updateGroup( $fieldGroup['key'], $namePatterns->toArray() );
	}

	/**
	 * @param string $groupKey
	 * @param array  $groupPatterns
	 *
	 * @return void
	 */
	public function updateGroup( $groupKey, $groupPatterns ) {
		$allPatterns = $this->getAllPatterns();

		if ( $groupPatterns ) {
			$allPatterns[ $groupKey ] = $groupPatterns;
		} else {
			unset( $allPatterns[ $groupKey ] );
		}

		update_option( self::OPTION_KEY, $allPatterns, false );
	}

	/**
	 * @param string $fieldName
	 *
	 * @return string|null
	 */
	public function findMatchingGroup( $fieldName ) {
		if ( array_key_exists( $fieldName, $this->cachedMatches ) ) {
			return $this->cachedMatches[ $fieldName ];
		}

		$this->cachedMatches[ $fieldName ] = null;

		foreach ( $this->getAllPatterns() as $groupKey => $patterns ) {
			if ( $this->matches( $fieldName, $patterns ) ) {
				$this->cachedMatches[ $fieldName ] = $groupKey;
				break;
			}
		}

		return $this->cachedMatches[ $fieldName ];
	}

	/**
	 * @param string $fieldName
	 * @param string $source
	 *
	 * @return string|null
	 */
	public function findMatchingLocalGroup( $fieldName, $source = 'json' ) {
		if ( ! array_key_exists( $source, $this->cachedLocalMatches ) ) {
			$this->cachedLocalMatches[ $source ] = [];
		}

		if ( array_key_exists( $fieldName, $this->cachedLocalMatches[ $source ] ) ) {
			return $this->cachedLocalMatches[ $source ][ $fieldName ];
		}

		$this->cachedLocalMatches[ $source ][ $fieldName ] = null;

		foreach ( $this->getAllLocalPatterns( $source ) as $groupKey => $patterns ) {
			if ( $this->matches( $fieldName, $patterns ) ) {
				$this->cachedLocalMatches[ $source ][ $fieldName ] = $groupKey;
				break;
			}
		}

		return $this->cachedLocalMatches[ $source ][ $fieldName ];
	}

	/**
	 * @param string   $fieldName
	 * @param string[] $patterns
	 *
	 * @return bool
	 */
	private function matches( $fieldName, $patterns ) {
		foreach ( $patterns as $pattern ) {
			if ( Str::match( '/^' . $pattern . '$/', $fieldName ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	private function getAllPatterns() {
		return (array) get_option( self::OPTION_KEY, [] );
	}

	/**
	 * @param string $source
	 *
	 * @return array<string,array<string>>
	 */
	private function buildLocalPatterns( $source = 'json' ) {
		$this->localPatterns[ $source ] = wpml_collect( acf_get_field_groups() )
			->filter( function( $fieldGroup ) use ( $source ) {
				if ( Mode::isAdvanced( $fieldGroup ) ) {
					return false;
				}
				if ( ! Relation::propEq( 'local', $source, $fieldGroup ) ) {
					return false;
				}
				if ( ! Relation::propEq( 'ID', 0, $fieldGroup ) ) {
					return false;
				}
				return true;
			} )
		->keyBy( 'key' )
		->map( function( $fieldGroup ) {
			$fieldGroupKey       = Obj::prop( 'key', $fieldGroup );
			$namePatterns        = wpml_collect();
			$getFieldNamePattern = function( $field, $fieldPattern ) use ( $namePatterns ) {
				$namePatterns->push( $fieldPattern );
				return $field;
			};
			Fields::iterate( acf_get_fields( $fieldGroupKey ), $getFieldNamePattern, Fns::identity() );
			return $namePatterns->filter()->toArray();
		} )
		->toArray();

		return $this->localPatterns[ $source ];
	}

	/**
	 * @param string $source
	 *
	 * @return array<string,array<string>>
	 */
	private function getAllLocalPatterns( $source = 'json' ) {
		if ( isset( $this->localPatterns[ $source ] ) ) {
			return $this->localPatterns[ $source ];
		}
		return $this->buildLocalPatterns( $source );
	}
}
