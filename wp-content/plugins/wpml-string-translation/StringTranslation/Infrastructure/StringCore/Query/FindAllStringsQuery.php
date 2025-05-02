<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchCriteria;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\SearchSelectCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindAllStringsQueryInterface;
use WPML\StringTranslation\Application\StringCore\Query\Dto\StringDto;

class FindAllStringsQuery implements FindAllStringsQueryInterface {

	/** @var FindAllStringsQueryBuilder */
	private $queryBuilder;

	/**
	 * @param FindAllStringsQueryBuilder $queryBuilder
	 */
	public function __construct(
		FindAllStringsQueryBuilder $queryBuilder
	) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @return StringDto[]
	 */
	public function execute( SearchCriteria $criteria, SearchSelectCriteria $selectCriteria ): array {
		global $wpdb;

		$query = $this->queryBuilder->build( $criteria, $selectCriteria );
		$strings = $wpdb->get_results( $query, ARRAY_A );
		$strings = $this->mapStringsCollection( $strings );

		return $strings;
	}

	private function mapStringsCollection( array $strings ): array {
		return array_map(
			function( $string ) {
				return $this->mapString( $string );
			},
			$strings
		);
	}

	private function has( array $string, string $col ) {
		return array_key_exists( $col, $string );
	}

	private function mapString( array $string ): StringDto {
		$hasId        = $this->has( $string, 'string_id' );
		$hasLanguage  = $this->has( $string, 'string_language' );
		$hasDomain    = $this->has( $string, 'domain' );
		$hasContext   = $this->has( $string, 'context' );
		$hasName      = $this->has( $string, 'name' );
		$hasValue     = $this->has( $string, 'value' );
		$hasStatus    = $this->has( $string, 'status' );
		$hasPriority  = $this->has( $string, 'translation_priority' );
		$hasWordCount = $this->has( $string, 'word_count' );
		$hasKind      = $this->has( $string, 'string_type' );
		$hasType      = $this->has( $string, 'component_type' );

		return new StringDto(
			$hasId ? $string['string_id'] : 0,
			$hasLanguage ? $string['string_language'] : '',
			$hasDomain ? $string['domain'] : '',
			$hasContext ? $string['context'] : '',
			$hasName ? $string['name'] : '',
			$hasValue ? $string['value'] : '',
			$hasStatus ? (int) $string['status'] : 0,
			$hasPriority ? $string['translation_priority'] : '',
			$hasWordCount && is_numeric( $string['word_count'] ) ? (int) $string['word_count'] : 0,
			$hasKind ? (int) $string['string_type'] : 0,
			$hasType ? (int) $string['component_type'] : 0
		);
	}
}
