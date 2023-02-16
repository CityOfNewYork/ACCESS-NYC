<?php

namespace WPML\ST\StringsFilter;

class TranslationReceiver {
	/** @var \wpdb */
	private $wpdb;

	/** @var QueryBuilder $query_builder */
	private $query_builder;

	public function __construct( \wpdb $wpdb, QueryBuilder $query_builder ) {
		$this->wpdb          = $wpdb;
		$this->query_builder = $query_builder;
	}


	/**
	 * @param StringEntity $string
	 * @param string       $language
	 *
	 * @return TranslationEntity
	 */
	public function get( StringEntity $string, $language ) {
		$query = $this->query_builder->setLanguage( $language )->filterByString( $string )->build();

		$record = $this->wpdb->get_row( $query, ARRAY_A );
		if ( ! $record ) {
			return new TranslationEntity( $string->getValue(), false, false );
		}

		if ( ! $record['translation'] ) {
			return new TranslationEntity( $record['value'], false, true );
		}

		return new TranslationEntity( $record['translation'], true, true );
	}

}
