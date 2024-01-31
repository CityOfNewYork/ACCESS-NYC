<?php

namespace WPML\ST\DB\Mappers;

use \wpdb;
use \WPML_DB_Chunk;

class StringsRetrieve {

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var WPML_DB_Chunk $chunk_retrieve */
	private $chunk_retrieve;

	public function __construct( wpdb $wpdb, WPML_DB_Chunk $chunk_retrieve ) {
		$this->wpdb           = $wpdb;
		$this->chunk_retrieve = $chunk_retrieve;
	}

	/**
	 * @param string $language
	 * @param string $domain
	 * @param bool   $modified_mo_only
	 *
	 * @return array
	 */
	public function get( $language, $domain, $modified_mo_only = false ) {
		$args = [ $language, $language, $domain ];

		$query = "
			SELECT
				s.id,
				st.status,
				s.domain_name_context_md5 AS ctx ,
				st.value AS translated,
				st.mo_string AS mo_string,
				s.value AS original,
				s.gettext_context,
				s.name
			FROM {$this->wpdb->prefix}icl_strings s
			" . $this->getStringTranslationJoin() . '
			' . $this->getDomainWhere();

		if ( $modified_mo_only ) {
			$query .= $this->getModifiedMOOnlyWhere();
		}

		$total_strings = $this->get_number_of_strings_in_domain( $language, $domain, $modified_mo_only );

		return $this->chunk_retrieve->retrieve( $query, $args, $total_strings );
	}

	/**
	 * @param string $language
	 * @param string $domain
	 * @param bool   $modified_mo_only
	 *
	 * @return int
	 */
	private function get_number_of_strings_in_domain( $language, $domain, $modified_mo_only ) {
		$tables = "SELECT COUNT(s.id) FROM {$this->wpdb->prefix}icl_strings AS s";

		/** @var string $where */
		/** @phpstan-ignore-next-line */
		$where  = $this->wpdb->prepare( $this->getDomainWhere(), [ $domain ] );

		if ( $modified_mo_only ) {
			/** @var string $sql */
			/** @phpstan-ignore-next-line */
			$sql = $this->wpdb->prepare( $this->getStringTranslationJoin(), [ $language, $language ] );
			$tables .= $sql;
			$where  .= $this->getModifiedMOOnlyWhere();
		}

		return (int) $this->wpdb->get_var( $tables . $where );
	}

	/**
	 * @return string
	 */
	private function getStringTranslationJoin() {
		return " LEFT JOIN {$this->wpdb->prefix}icl_string_translations AS st
					ON s.id = st.string_id
						AND st.language = %s
						AND s.language != %s";
	}

	/** @return string */
	private function getDomainWhere() {
		return ' WHERE UPPER(context) = UPPER(%s)';
	}

	/** @return string */
	private function getModifiedMOOnlyWhere() {
		return ' AND st.status IN (' .
			   wpml_prepare_in( [ ICL_TM_COMPLETE, ICL_TM_NEEDS_UPDATE ], '%d' ) .
			   ') AND st.value IS NOT NULL';
	}
}
