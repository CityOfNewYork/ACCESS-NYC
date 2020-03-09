<?php

namespace WPML\ST\TranslationFile;

use WPML\Collect\Support\Collection;
use WPML\ST\TranslateWpmlString;

class StringsRetrieve {

	// We need to store the strings by key that is a combination of original and gettext context
	// The join needs to be something that is unlikely to be in either so we can split later.
	const KEY_JOIN = '::JOIN::';

	/** @var \WPML\ST\DB\Mappers\StringsRetrieve */
	private $string_retrieve;

	/**
	 * @param \WPML\ST\DB\Mappers\StringsRetrieve $string_retrieve
	 */
	public function __construct( \WPML\ST\DB\Mappers\StringsRetrieve $string_retrieve ) {
		$this->string_retrieve = $string_retrieve;
	}


	/**
	 * @param string $domain
	 * @param string $language
	 * @param bool   $modified_mo_only
	 *
	 * @return StringEntity[]
	 */
	public function get( $domain, $language, $modified_mo_only ) {
		return $this->loadFromDb( $language, $domain, $modified_mo_only )
		            ->filter( function ( $string ) {
			            return (bool) $string['translation'];
		            } )
		            ->mapToGroups( function ( array $string ) {
			            return $this->groupPluralFormsOfSameString( $string );
		            } )
		            ->map( function ( Collection $strings, $key ) {
			            return $this->buildStringEntity( $strings, $key );
		            } )
		            ->values()
		            ->toArray();
	}

	/**
	 * @param string $language
	 * @param string $domain
	 * @param bool   $modified_mo_only
	 *
	 * @return Collection
	 */
	private function loadFromDb( $language, $domain, $modified_mo_only = false ) {
		$result = \wpml_collect( $this->string_retrieve->get( $language, $domain, $modified_mo_only ) );

		return $result->map( function ( $row ) {
			return $this->parseResult( $row );
		} );
	}

	/**
	 * @param array $row_data
	 *
	 * @return array
	 */
	private function parseResult( array $row_data ) {
		return [
			'id'          => $row_data['id'],
			'original'    => $row_data['original'],
			'context'     => $row_data['gettext_context'],
			'translation' => self::parseTranslation( $row_data ),
			'name'        => $row_data['name'],
		];
	}

	/**
	 * @param array $row_data
	 *
	 * @return string|null
	 */
	public static function parseTranslation( array $row_data ) {
		$value = null;

		$has_translation = ! empty( $row_data['translated'] ) && in_array( $row_data['status'], [ ICL_TM_COMPLETE, ICL_TM_NEEDS_UPDATE ] );
		if ( $has_translation ) {
			$value = $row_data['translated'];
		} elseif ( ! empty( $row_data['mo_string'] ) ) {
			$value = $row_data['mo_string'];
		}

		return $value;
	}

	/**
	 * @param array $string
	 *
	 * @return array
	 */
	private function groupPluralFormsOfSameString( array $string ) {
		$groupKey = $this->getPluralGroupKey( $string );
		$pattern  = '/^(.+) \[plural ([0-9]+)\]$/';

		if ( preg_match( $pattern, $string['original'], $matches ) ) {
			$string['original'] = $matches[1];
			$string['index']    = $matches[2];
		} else {
			$string['index'] = null;
		}

		return [
			$string['original'] . self::KEY_JOIN . $string['context'] . self::KEY_JOIN . $groupKey => $string
		];
	}

	/**
	 * Inside a domain, we can have several occurrences of strings
	 * with the same original, but with different names.
	 * In this situation, we should not try to group plurals.
	 *
	 * @param array $string
	 *
	 * @return mixed|string
	 */
	private function getPluralGroupKey( array $string ) {
		$cannotBelongToPluralGroup = TranslateWpmlString::canTranslateWithMO( $string['original'], $string['name'] );

		if ( $cannotBelongToPluralGroup ) {
			return $string['name'];
		}

		return '';
	}

	/**
	 * @param Collection $strings
	 * @param string     $key
	 *
	 * @return StringEntity
	 */
	private function buildStringEntity( Collection $strings, $key ) {
		$translations = $strings->sortBy( 'index' )->pluck( 'translation' )->toArray();
		list( $original, $context ) = explode( self::KEY_JOIN, $key );
		$stringEntity = new StringEntity( $original, $translations, $context );
		$stringEntity->set_name( $strings->first()['name'] );

		return $stringEntity;
	}
}
