<?php

namespace WPML\ST\TranslationFile;

use wpdb;
use WPML\Collect\Support\Collection;
use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML_Locale;

class DomainsLocalesMapper {

	const ALIAS_STRINGS             = 's';
	const ALIAS_STRING_TRANSLATIONS = 'st';

	/** @var wpdb $wpdb */
	private $wpdb;

	/** @var WPML_Locale $locale */
	private $locale;

	public function __construct( wpdb $wpdb, WPML_Locale $locale ) {
		$this->wpdb   = $wpdb;
		$this->locale = $locale;
	}

	/**
	 * @param array $string_translation_ids
	 *
	 * @return Collection of objects with properties `domain` and `locale`
	 */
	public function get_from_translation_ids( array $string_translation_ids ) {
		return $this->get_results_where( self::ALIAS_STRING_TRANSLATIONS, $string_translation_ids );
	}

	/**
	 * @param array $string_ids
	 *
	 * @return Collection of objects with properties `domain` and `locale`
	 */
	public function get_from_string_ids( array $string_ids ) {
		return $this->get_results_where( self::ALIAS_STRINGS, $string_ids );
	}

	/**
	 * @param  callable $getActiveLanguages
	 * @param  string   $domain
	 *
	 * @return array
	 */
	public function get_from_domain( callable $getActiveLanguages, $domain ) {
		$createEntity = function ( $locale ) use ( $domain ) {
			return (object) [
				'domain' => $domain,
				'locale' => $locale,
			];
		};

		return Fns::map( $createEntity, Obj::values( Lst::pluck( 'default_locale', $getActiveLanguages() ) ) );
	}

	/**
	 * @param string $table_alias
	 * @param array  $ids
	 *
	 * @return Collection
	 */
	private function get_results_where( $table_alias, array $ids ) {
		$results = [];
		if ( array_filter( $ids ) ) {
			$results = $this->wpdb->get_results(
				"
    			SELECT DISTINCT
    				s.context AS domain,
    				st.language
    			FROM {$this->wpdb->prefix}icl_string_translations AS " . self::ALIAS_STRING_TRANSLATIONS . "
    			JOIN {$this->wpdb->prefix}icl_strings AS " . self::ALIAS_STRINGS . " ON s.id = st.string_id
    			WHERE $table_alias.id IN(" . wpml_prepare_in( $ids ) . ')
    		'
			);
		}

		return wpml_collect( $results )->map(
			function( $row ) {
				return (object) [
					'domain' => $row->domain,
					'locale' => $this->locale->get_locale( $row->language ),
				];
			}
		);
	}
}
