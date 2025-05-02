<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Command\UpdateStringsCommandInterface;

class UpdateStringsCommand extends BulkActionBaseCommand implements UpdateStringsCommandInterface {

	public function __construct(
		$wpdb
	) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param StringItem[] $strings
	 * @param array        $fields
	 * @param array        $values
	 */
	public function run( array $strings, array $fields, array $values ) {
		foreach ( array_chunk( $strings, $this->chunk_size ) as $chunk ) {
			$ids = [];
			foreach ( $chunk as $string ) {
				$ids[] = $string->getId();
			}

			$query  = '';
			$query .= "UPDATE {$this->wpdb->prefix}icl_strings SET ";

			$fieldsSql = [];
			for ( $i = 0; $i < count( $fields ); $i++ ) {
				$fieldsSql[] = sanitize_key( $fields[ $i ] ) . " = " . $this->wpdb->prepare( '%s', $values[ $i ] );
			}

			$query .= implode( ', ', $fieldsSql );
			$query .= " WHERE id IN (" . wpml_prepare_in( $ids, '%d' ) . ")";

			$this->runBulkQuery( $query );
		}
	}
}