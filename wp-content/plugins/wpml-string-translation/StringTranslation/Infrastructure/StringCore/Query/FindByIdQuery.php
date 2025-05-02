<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Query;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;
use WPML\StringTranslation\Application\StringCore\Query\Criteria\DomainValueAndContextCriteria;
use WPML\StringTranslation\Application\StringCore\Query\FindByIdQueryInterface;

class FindByIdQuery implements FindByIdQueryInterface {

	/** @var \wpdb */
	private $wpdb;

	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @return StringItem[]
	 */
	public function execute( array $ids ): array {
		if ( count( $ids ) === 0 ) {
			return [];
		}

		$query  = "SELECT id, context, gettext_context, value, name FROM {$this->wpdb->prefix}icl_strings WHERE ";
		$query .= 'id IN (' . wpml_prepare_in( $ids, '%d' ) . ')';

		$res = $this->wpdb->get_results( $query, ARRAY_A );

		$strings = [];

		foreach ( $res as $row ) {
			$string = new StringItem();

			$string->setId( $row['id'] );
			$string->setValue( $row['value'] );
			$string->setContext( $row['gettext_context'] );
			$string->setDomain( $row['context'] );
			$string->setName( $row['name'] );

			$strings[] = $string;
		}

		return $strings;
	}
}