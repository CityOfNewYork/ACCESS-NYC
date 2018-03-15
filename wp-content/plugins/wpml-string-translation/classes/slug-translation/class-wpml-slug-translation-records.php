<?php

class WPML_Slug_Translation_Records {

	const SLUG_NAME_PREFIX = 'URL slug: ';

	/** @var wpdb $wpdb */
	private $wpdb;

	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	public function get_translation( $slug, $lang ) {
		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
					SELECT t.value
					FROM {$this->wpdb->prefix}icl_string_translations t
						JOIN {$this->wpdb->prefix}icl_strings s ON t.string_id = s.id
					WHERE t.language = %s AND s.name = %s AND t.status = %d
				",
				$lang,
				self::SLUG_NAME_PREFIX . $slug,
				ICL_TM_COMPLETE
			)
		);

	}

	public function get_original( $slug, $lang = '' ) {
		if ( $lang ) {
			return $this->wpdb->get_var(
				$this->wpdb->prepare(
					"
						SELECT value
						FROM {$this->wpdb->prefix}icl_strings
						WHERE language = %s AND name = %s
					",
					$lang,
					self::SLUG_NAME_PREFIX . $slug
				)
			);
		} else {
			return $this->wpdb->get_var(
				$this->wpdb->prepare(
					"
						SELECT value
						FROM {$this->wpdb->prefix}icl_strings
						WHERE name = %s
					",
					self::SLUG_NAME_PREFIX . $slug
				)
			);
		}

	}
}