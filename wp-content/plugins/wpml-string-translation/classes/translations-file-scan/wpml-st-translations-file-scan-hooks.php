<?php

class WPML_ST_Translations_File_Scan_Hooks {
	/** @var WPML_ST_Translations_File_Queue */
	private $queue;

	/** @var WPML_ST_Translations_File_Dictionary */
	private $dictionary;

	/** @var WPML_File */
	private $wpml_file;

	/**
	 * @param WPML_ST_Translations_File_Queue      $queue
	 * @param WPML_ST_Translations_File_Dictionary $dictionary
	 * @param WPML_File                            $wpml_file
	 */
	public function __construct(
		WPML_ST_Translations_File_Queue $queue,
		WPML_ST_Translations_File_Dictionary $dictionary,
		WPML_File $wpml_file
	) {
		$this->queue      = $queue;
		$this->dictionary = $dictionary;
		$this->wpml_file  = $wpml_file;
	}

	public function add_hooks() {
		add_filter( 'override_load_textdomain', array( $this, 'block_loading_of_imported_mo_files' ), PHP_INT_MAX, 3 );
		add_action( 'shutdown', array( $this, 'import_translations_files' ), 10, 0 );
	}

	public function import_translations_files() {
		if ( ! $this->queue->is_locked() ) {
			$this->queue->import();
		}
	}

	public function block_loading_of_imported_mo_files( $override, $domain, $mo_file ) {
		$relative_path = $this->wpml_file->get_relative_path( $mo_file );
		$file = $this->dictionary->find_file_info_by_path( $relative_path );

		$statuses = array( WPML_ST_Translations_File_Entry::IMPORTED, WPML_ST_Translations_File_Entry::FINISHED );

		return $file && in_array( $file->get_status(), $statuses, true );
	}
}
