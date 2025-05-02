<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringItem;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringsCommandInterface;

class InsertStringsCommand extends BulkActionBaseCommand implements InsertStringsCommandInterface {

	public function __construct(
		$wpdb
	) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param StringItem[] $strings
	 */
	public function run( array $strings ) {
		foreach ( array_chunk( $strings, $this->chunk_size ) as $chunk ) {
			$query = "INSERT IGNORE INTO {$this->wpdb->prefix}icl_strings "
				. '(`language`, `context`, `gettext_context`, `domain_name_context_md5`, `name`, '
				. '`value`, `status`, `string_type`, `component_id`, `component_type`) VALUES ';

			$query .= implode( ',', array_map( array( $this, 'build_string_row' ), $chunk ) );

			$this->runBulkQuery( $query );
		}
	}

	/**
	 * @param StringItem $string
	 *
	 * @return string
	 */
	private function build_string_row( StringItem $string ) {
		return $this->wpdb->prepare(
			'(%s, %s, %s, %s, %s, %s, %d, %d, %s, %d)',
			$string->getLanguage(),
			$string->getDomain(),
			$string->getContext(),
			$string->getDomainNameContextMd5(),
			$string->getName(),
			$string->getValue(),
			$string->getStatus(),
			$string->getStringType(),
			$string->getComponentId(),
			$string->getComponentType()
		);
	}
}