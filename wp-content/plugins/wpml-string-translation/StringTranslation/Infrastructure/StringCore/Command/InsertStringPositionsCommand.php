<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringPosition;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringPositionsCommandInterface;

class InsertStringPositionsCommand extends BulkActionBaseCommand implements InsertStringPositionsCommandInterface {

	public function __construct(
		$wpdb
	) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param StringPosition[] $positions
	 */
	public function run( array $positions ) {
		foreach ( array_chunk( $positions, $this->chunk_size ) as $chunk ) {
			$query = "INSERT INTO {$this->wpdb->prefix}icl_string_positions "
				. '(`string_id`, `kind`, `position_in_page`) VALUES ';

			$query .= implode( ',', array_map( array( $this, 'buildStringPositionRow' ), $chunk ) );

			$this->runBulkQuery( $query );
		}
	}

	/**
	 * @param StringPosition $position
	 *
	 * @return string
	 */
	private function buildStringPositionRow( StringPosition $position ) {
		return $this->wpdb->prepare(
			'(%s, %d, %s)',
			$position->getString()->getId(),
			$position->getKind(),
			$position->getPositionInPage()
		);
	}
}