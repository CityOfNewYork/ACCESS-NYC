<?php

namespace ACFML\FieldGroup;

use WPML\FP\Str;

class FieldNamePatterns {

	const OPTION_KEY = 'acfml_field_name_patterns';

	/**
	 * @var array $cachedMatches
	 */
	private $cachedMatches = [];

	/**
	 * @param int   $groupId
	 * @param array $groupPatterns
	 *
	 * @return void
	 */
	public function updateGroup( $groupId, $groupPatterns ) {
		$allPatterns = $this->getAllPatterns();

		if ( $groupPatterns ) {
			$allPatterns[ $groupId ] = $groupPatterns;
		} else {
			unset( $allPatterns[ $groupId ] );
		}

		update_option( self::OPTION_KEY, $allPatterns, false );
	}

	/**
	 * @param string $fieldName
	 *
	 * @return int|null
	 */
	public function findMatchingGroup( $fieldName ) {
		if ( array_key_exists( $fieldName, $this->cachedMatches ) ) {
			return $this->cachedMatches[ $fieldName ];
		}

		$this->cachedMatches[ $fieldName ] = null;

		foreach ( $this->getAllPatterns() as $groupId => $patterns ) {
			if ( $this->matches( $fieldName, $patterns ) ) {
				$this->cachedMatches[ $fieldName ] = (int) $groupId;
				break;
			}
		}

		return $this->cachedMatches[ $fieldName ];
	}

	/**
	 * @param string   $fieldName
	 * @param string[] $patterns
	 *
	 * @return bool
	 */
	private function matches( $fieldName, $patterns ) {
		foreach ( $patterns as $pattern ) {
			if ( Str::match( '/^' . $pattern . '$/', preg_quote( $fieldName ) ) ) {
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
}
