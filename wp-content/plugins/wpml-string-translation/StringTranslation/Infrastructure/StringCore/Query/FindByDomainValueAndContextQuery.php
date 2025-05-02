<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\DomainValueAndContextCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindByDomainValueAndContextQueryInterface;

class FindByDomainValueAndContextQuery implements FindByDomainValueAndContextQueryInterface {

	/** @var \wpdb */
	private $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @return StringItem[]
	 */
	public function execute( DomainValueAndContextCriteria $criteria ): array {
		$strings = $criteria->getStringsToSearch();
		$fields  = $criteria->getFieldsToHydrate();

		if ( count( $strings ) === 0 ) {
			return [];
		}

		$fetchId            = in_array( 'id', $fields );
		$fetchStringType    = in_array( 'string_type', $fields );
		$fetchComponentId   = in_array( 'component_id', $fields );
		$fetchComponentType = in_array( 'component_type', $fields );
		$fetchPositions     = in_array( 'positions', $fields );

		$fetchStrings = (
			( $fetchId || $fetchStringType || $fetchComponentId || $fetchComponentType )
		);

		if ( $fetchStrings ) {
			$this->findStringsDataByDomainValueAndContext( $strings, $fetchId, $fetchStringType, $fetchComponentId, $fetchComponentType );
		}

		if ( $fetchPositions ) {
			$this->findPositionsData( $strings );
		}

		return $strings;
	}

	/**
	 * @param StringItem[] $strings
	 */
	private function findStringsDataByDomainValueAndContext(
		array $allStrings,
		bool $fetchId,
		bool $fetchStringType,
		bool $fetchComponentId,
		bool $fetchComponentType
	) {
		$fieldsSql = '';
		if ( $fetchId ) {
			$fieldsSql .= 'id, ';
		}
		if ( $fetchStringType ) {
			$fieldsSql .= 'string_type, ';
		}
		if ( $fetchComponentId ) {
			$fieldsSql .= 'component_id, ';
		}
		if ( $fetchComponentType ) {
			$fieldsSql .= 'component_type, ';
		}

		$query = "SELECT " . $fieldsSql . "context, gettext_context, value, name FROM {$this->wpdb->prefix}icl_strings WHERE ";
		$searchRows = [];
		foreach ( $allStrings as $string ) {
			$searchRows[] = $this->buildStringSearchRow( $string );
		}
		$query .= implode( ' OR ', $searchRows );
		$res = $this->wpdb->get_results( $query, ARRAY_A );

		$strings = [];
		foreach ( $allStrings as $string ) {
			$strings[ $string->getDomainValueAndContextKey() . ( $string->getName() ?? '' ) ] = $string;
		}

		foreach ( $res as $row ) {
			$key = $row['context'] . $row['value'] . ( $row['gettext_context'] ?? '' ) . ( $row['name'] ?? '' );
			$string = array_key_exists( $key, $strings ) ? $strings[ $key ] : null;
			if ( ! $string ) {
				continue;
			}

			if ( $fetchId ) {
				$string->setId( $row['id'] );
			}
			if ( $fetchStringType ) {
				$string->setStringType( $row['string_type'] );
			}
			if ( $fetchComponentId ) {
				$string->setComponentId( $row['component_id'] );
			}
			if ( $fetchComponentType ) {
				$string->setComponentType( $row['component_type'] );
			}
		}
	}

	private function buildStringSearchRow( StringItem $string ): string {
		$args = [
			$string->getDomain(),
			$string->getValue(),
		];

		$extraSql = '';
		if ( is_string( $string->getContext() ) && strlen( $string->getContext() ) > 0 ) {
			$extraSql = ' AND gettext_context="%s"';
			$args[]   = $string->getContext();
		}

		return $this->wpdb->prepare(
			'(context="%s" AND value=%s' . $extraSql . ')',
			$args
		);
	}

	private function findPositionsData( array $strings ) {
		$stringIds = wpml_prepare_in(
			array_map(
				function( $string ) {
					return $string->getId();
				},
				$strings
			),
			'%d'
		);

		$query = "SELECT id, string_id, kind, position_in_page FROM {$this->wpdb->prefix}icl_string_positions WHERE string_id IN ({$stringIds})";
		$res   = $this->wpdb->get_results( $query, ARRAY_A );

		$stringById = [];
		foreach ( $strings as $string ) {
			$stringById[ $string->getId() ] = $string;
		}

		foreach ( $res as $row ) {
			$stringId = (int) $row['string_id'];
			if ( ! array_key_exists( $stringId, $stringById ) ) {
				continue;
			}

			$string   = $stringById[ $stringId ];
			$position = new StringPosition(
				$row['kind'],
				$row['position_in_page'],
				$string
			);
			$existingPosition  = null;
			foreach ( $string->getPositions() as $maybeExistingPosition ) {
				if ( $position->isEqualTo( $maybeExistingPosition ) ) {
					$existingPosition = $maybeExistingPosition;
					break;
				}
			}

			// String can call this hydration method with some position objects setup, for example coming from autoregistration callbacks.
			// In such case we want to diff between new positions and already registered positions in the database, so we need to
			// set database id in case the position record with specified kind and url for the string already exists.
			if ( is_object( $existingPosition ) ) {
				$existingPosition->setId( $row['id'] );
			} else {
				$position->setId( $row['id'] );
				$string->addPosition( $position );
			}
		}
	}
}
