<?php

use WPML\ST\TranslationFile\EntryQueries;

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
	 * @param string $file_path
	 *
	 * @return WPML_ST_Translations_File_Entry|null
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
		return $this->storage->find(
			null,
			[
				WPML_ST_Translations_File_Entry::NOT_IMPORTED,
				WPML_ST_Translations_File_Entry::PARTLY_IMPORTED,
			]
		);
	}

	public function clear_skipped() {
		$skipped = wpml_collect( $this->storage->find( null, [ WPML_ST_Translations_File_Entry::SKIPPED ] ) );
		$skipped->each(
			function ( WPML_ST_Translations_File_Entry $entry ) {
				$entry->set_status( WPML_ST_Translations_File_Entry::NOT_IMPORTED );
				$this->storage->save( $entry );
			}
		);
	}

	/**
	 * @return WPML_ST_Translations_File_Entry[]
	 */
	public function get_imported_files() {
		return $this->storage->find( null, WPML_ST_Translations_File_Entry::IMPORTED );
	}

	/**
	 * @param null|string $extension
	 * @param null|string $locale
	 *
	 * @return array
	 */
	public function get_domains( $extension = null, $locale = null ) {
		$files = wpml_collect( $this->storage->find() );

		if ( $extension ) {
			$files = $files->filter( EntryQueries::isExtension( $extension ) );
		}
		if ( $locale ) {
			$files = $files->filter(
				function ( WPML_ST_Translations_File_Entry $file ) use ( $locale ) {
					return $file->get_file_locale() === $locale;
				}
			);
		}

		return $files->map( EntryQueries::getDomain() )
					 ->unique()
					 ->values()
					 ->toArray();
	}
}
