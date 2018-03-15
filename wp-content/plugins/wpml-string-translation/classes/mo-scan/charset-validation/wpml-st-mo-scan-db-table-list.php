<?php

class WPML_ST_MO_Scan_Db_Table_List {
	/** @var wpdb */
	private $wpdb;

	/**
	 * @param wpdb $wpdb
	 */
	public function __construct( wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * @return array
	 */
	public function get_tables() {
		$tables = $this->wpdb->get_col( "SHOW TABLES LIKE '{$this->wpdb->prefix}%'" );
		if ( is_multisite() ) {
			if ( is_main_site() ) {
				$tables = array_values( array_filter( $tables, array( $this, 'is_table_from_main_site' ) ) );
			} else {
				$tables = array_merge( $tables, $this->get_global_tables() );
			}
		}

		return $tables;
	}

	private function is_table_from_main_site( $table ) {
		$pattern = "/^{$this->wpdb->prefix}\d+_.*/";
		return 0 === preg_match( $pattern, $table );
	}

	private function get_global_tables() {
		$global_tables = $this->wpdb->global_tables;

		foreach ( $this->users_tables() as $original => $custom_table ) {
			if ( $original !== $custom_table ) {
				$pos = array_search( $original, $global_tables, true );
				if ( false !== $pos ) {
					$global_tables[ $pos ] = $custom_table;
				}
			}
		}

		return array_map( array( $this, 'prepend_base_prefix' ), $global_tables );
	}

	private function prepend_base_prefix( $table ) {
		return $this->wpdb->base_prefix . $table;
	}

	/**
	 * @return array
	 */
	private function users_tables() {
		return array(
			'users'    => $this->get_custom_users_table(),
			'usermeta' => $this->get_custom_usermeta_table(),
		);
	}


	protected function get_custom_users_table() {
		return defined( 'CUSTOM_USER_TABLE' ) ? CUSTOM_USER_TABLE : 'users';
	}

	protected function get_custom_usermeta_table() {
		return defined( 'CUSTOM_USER_META_TABLE' ) ? CUSTOM_USER_META_TABLE : 'usermeta';
	}
}