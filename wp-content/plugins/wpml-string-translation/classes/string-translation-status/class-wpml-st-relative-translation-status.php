<?php

/**
 * Class WPML_ST_Relative_Translation_Status
 *
 * @author OnTheGoSystems
 */
class WPML_ST_Relative_Translation_Status {

	/** @var  wpdb $wpdb */
	private $wpdb;

	/** @var  SitePress $sitepress */
	private $sitepress;

	/** @var  WPML_ST_String_Factory $string_factory */
	private $string_factory;

	/**
	 * WPML_ST_Relative_Translation_Status constructor.
	 *
	 * @param wpdb                   $wpdb
	 * @param SitePress              $sitepress
	 * @param WPML_ST_String_Factory $string_factory
	 */
	public function __construct( wpdb $wpdb, SitePress $sitepress, WPML_ST_String_Factory $string_factory ) {
		$this->wpdb           = $wpdb;
		$this->sitepress      = $sitepress;
		$this->string_factory = $string_factory;
	}

	/**
	 * @param int $string_id
	 *
	 * @return bool|int
	 */
	public function get( $string_id ) {
		$string          = $this->string_factory->find_by_id( $string_id );
		$current_user    = $this->sitepress->get_current_user();
		$user_lang_pairs = get_user_meta( $current_user->ID, $this->wpdb->prefix . 'language_pairs', true );

		$to_langs = null;

		if ( isset ( $user_lang_pairs[ $string->get_language() ] ) ) {
			$to_langs       = array_intersect(
				array_keys( $this->sitepress->get_active_languages() ),
				array_keys( $user_lang_pairs[ $string->get_language() ] )
			);
		}

		if( empty( $to_langs ) ) {
			return ICL_TM_NOT_TRANSLATED;
		}

		$sql = "SELECT st.status
            FROM {$this->wpdb->prefix}icl_strings s
            JOIN {$this->wpdb->prefix}icl_string_translations st ON s.id = st.string_id
            WHERE st.language IN (" . wpml_prepare_in( $to_langs ) . ") AND s.id = %d
        ";

		$statuses = wpml_collect( $this->wpdb->get_col( $this->wpdb->prepare( $sql, $string_id ) ) );

		$completed      = $statuses->filter( function( $s ) { return $s == ICL_TM_COMPLETE; } );
		$not_translated = $statuses->filter( function( $s ) { return $s == ICL_TM_NOT_TRANSLATED; } );

		if ( $completed->isEmpty() ) {
			return ICL_TM_NOT_TRANSLATED;
		} elseif ( $not_translated->count() ) {
			return ICL_STRING_TRANSLATION_PARTIAL;
		} else {
			return ICL_TM_COMPLETE;
		}
	}
}
