<?php

class WPML_ST_Translations_File_Component_Stats_Update_Hooks {
	/** @var WPML_ST_Strings_Stats */
	private $string_stats;

	/**
	 * @param WPML_ST_Strings_Stats $string_stats
	 */
	public function __construct( WPML_ST_Strings_Stats $string_stats ) {
		$this->string_stats = $string_stats;
	}

	public function add_hooks() {
		add_action( 'wpml_st_translations_file_post_import', array( $this, 'update_stats' ), 10, 1 );
	}

	/**
	 * @param WPML_ST_Translations_File_Entry $file
	 */
	public function update_stats( WPML_ST_Translations_File_Entry $file ) {
		if ( $file->get_component_id() ) {
			$this->string_stats->update( $file->get_component_id(), $file->get_component_type(), $file->get_domain() );
		}
	}
}