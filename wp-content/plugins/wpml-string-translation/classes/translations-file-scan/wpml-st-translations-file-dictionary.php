<?php

class WPML_ST_Translations_File_Dictionary {
	/** @var WPML_ST_Translations_File_Dictionary_Storage */
	private $storage;

	/**
	 * @param WPML_ST_Translations_File_Dictionary_Storage $storage
	 */
	public function __construct( WPML_ST_Translations_File_Dictionary_Storage $storage ) {
		$this->storage = $storage;
	}

	/**
	 * @param $file_path
	 *
	 * @return WPML_ST_Translations_File_Entry
	 */
	public function find_file_info_by_path( $file_path ) {
		$result = $this->storage->find( $file_path );
		if ( $result ) {
			return current( $result );
		}

		return null;
	}

	/**
	 * @param WPML_ST_Translations_File_Entry $file
	 */
	public function save( WPML_ST_Translations_File_Entry $file ) {
		$this->storage->save( $file );
	}

	/**
	 * @return WPML_ST_Translations_File_Entry[]
	 */
	public function get_not_imported_files() {
		return $this->storage->find( null, array( WPML_ST_Translations_File_Entry::NOT_IMPORTED, WPML_ST_Translations_File_Entry::PARTLY_IMPORTED ) );
	}

	/**
	 * @return WPML_ST_Translations_File_Entry[]
	 */
	public function get_imported_files() {
		return $this->storage->find( null, WPML_ST_Translations_File_Entry::IMPORTED );
	}
}
