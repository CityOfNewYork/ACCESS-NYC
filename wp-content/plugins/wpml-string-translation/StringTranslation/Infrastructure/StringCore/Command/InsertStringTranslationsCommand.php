<?php

namespace WPML\StringTranslation\Infrastructure\StringCore\Command;

use WPML\StringTranslation\Application\StringCore\Domain\StringTranslation;
use WPML\StringTranslation\Application\StringCore\Command\InsertStringTranslationsCommandInterface;

class InsertStringTranslationsCommand extends BulkActionBaseCommand implements InsertStringTranslationsCommandInterface {

	public function __construct(
		$wpdb
	) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param StringTranslation[] $translations
	 */
	public function run( array $translations ) {
		foreach ( array_chunk( $translations, $this->chunk_size ) as $chunk ) {
			$query = "INSERT IGNORE INTO {$this->wpdb->prefix}icl_string_translations "
				. '(`string_id`, `language`, `status`, `value`, `mo_string`, `translator_id`, '
				. '`translation_service`, `batch_id`, `translation_date`) VALUES ';

			$query .= implode( ',', array_map( array( $this, 'buildStringTranslationRow' ), $chunk ) );

			$this->runBulkQuery( $query );
		}
	}

	/**
	 * @param StringTranslation $translation
	 *
	 * @return string
	 */
	private function buildStringTranslationRow( StringTranslation $translation ) {
		return $this->wpdb->prepare(
			'(%s, %s, %s, %s, %s, %s, %s, %s, %s)',
			$translation->getString()->getId(),
			$translation->getLanguage(),
			ICL_TM_COMPLETE,
			$translation->getValue(),
			$translation->getValue(),
			null,
			'local',
			0,
			'2024-01-01 00:00:00'
		);
	}
}