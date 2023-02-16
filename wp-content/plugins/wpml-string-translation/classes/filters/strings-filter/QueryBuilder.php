<?php

namespace WPML\ST\StringsFilter;

class QueryBuilder {
	/** @var \wpdb */
	private $wpdb;

	/** @var string|null $language */
	private $language;

	/** @var string */
	private $where;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @param string $language
	 *
	 * @return $this
	 */
	public function setLanguage( $language ) {
		$this->language = $language;

		return $this;
	}

	/**
	 * @param array $domains
	 *
	 * @return $this
	 */
	public function filterByDomains( array $domains ) {
		$in          = \wpml_prepare_in( $domains );
		$this->where = "s.context IN({$in})";

		return $this;
	}

	/**
	 * @param StringEntity $string
	 *
	 * @return $this
	 */
	public function filterByString( StringEntity $string ) {
		$this->where = $this->wpdb->prepare(
			's.name = %s AND s.context = %s AND s.gettext_context = %s',
			$string->getName(),
			$string->getDomain(),
			$string->getContext()
		);

		return $this;
	}

	/**
	 * @return string
	 */
	public function build() {
		$result = $this->getSQL();
		if ( $this->where ) {
			$result .= ' WHERE ' . $this->where;
		}

		return $result;
	}

	/**
	 * @return string
	 */
	private function getSQL() {
		return $this->wpdb->prepare(
			"
			SELECT 
				s.value, 
				s.name,
				s.context as domain, 
				s.gettext_context as context, 
				IF(st.status = %d AND st.value IS NOT NULL, st.`value`, st.mo_string) AS `translation`
			FROM {$this->wpdb->prefix}icl_strings s
			LEFT JOIN {$this->wpdb->prefix}icl_string_translations st ON st.string_id = s.id AND st.`language` = %s
			",
			ICL_STRING_TRANSLATION_COMPLETE,
			$this->language
		);
	}
}
