<?php

use WPML\ST\TranslationFile\EntryQueries;
use WPML\ST\TranslationFile\QueueFilter;

class WPML_ST_Translations_File_Queue {
	const DEFAULT_LIMIT = 20000;
	const TIME_LIMIT    = 10; // seconds
	const LOCK_FIELD    = '_wpml_st_file_scan_in_progress';

	/** @var WPML_ST_Translations_File_Dictionary */
	private $file_dictionary;

	/** @var WPML_ST_Translations_File_Scan */
	private $file_scan;

	/** @var WPML_ST_Translations_File_Scan_Storage */
	private $file_scan_storage;

	/** @var WPML_Language_Records */
	private $language_records;

	/** @var int */
	private $limit;

	/** @var WPML_Transient  */
	private $transient;

	/**
	 * @param WPML_ST_Translations_File_Dictionary   $file_dictionary
	 * @param WPML_ST_Translations_File_Scan         $file_scan
	 * @param WPML_ST_Translations_File_Scan_Storage $file_scan_storage
	 * @param WPML_Language_Records                  $language_records
	 * @param int                                    $limit
	 * @param WPML_Transient                         $transient
	 */
	public function __construct(
		WPML_ST_Translations_File_Dictionary $file_dictionary,
		WPML_ST_Translations_File_Scan $file_scan,
		WPML_ST_Translations_File_Scan_Storage $file_scan_storage,
		WPML_Language_Records $language_records,
		$limit,
		WPML_Transient $transient
	) {
		$this->file_dictionary   = $file_dictionary;
		$this->file_scan         = $file_scan;
		$this->file_scan_storage = $file_scan_storage;
		$this->language_records  = $language_records;
		$this->limit             = $limit;
		$this->transient         = $transient;
	}

	/**
	 * @param QueueFilter|null $queueFilter
	 */
	public function import( QueueFilter $queueFilter = null ) {
		$this->file_dictionary->clear_skipped();
		$files = $this->file_dictionary->get_not_imported_files();

		if ( count( $files ) ) {
			$this->lock();

			$start_time = time();
			$imported   = 0;
			foreach ( $files as $file ) {
				if ( $imported >= $this->limit || time() - $start_time > self::TIME_LIMIT ) {
					break;
				}

				if ( ! $queueFilter || $queueFilter->isSelected( $file ) ) {

					$translations = $this->file_scan->load_translations( $file->get_full_path() );

					try {
						$number_of_translations = count( $translations );
						if ( ! $number_of_translations ) {
							throw new RuntimeException( 'File is empty' );
						}

						$translations = $this->constrain_translations_number(
							$translations,
							$file->get_imported_strings_count(),
							$this->limit - $imported
						);

						$imported += $imported_in_file = count( $translations );

						$this->file_scan_storage->save(
							$translations,
							$file->get_domain(),
							$this->map_language_code( $file->get_file_locale() )
						);

						$file->set_imported_strings_count( $file->get_imported_strings_count() + $imported_in_file );

						if ( $file->get_imported_strings_count() >= $number_of_translations ) {
							$file->set_status( WPML_ST_Translations_File_Entry::IMPORTED );
						} else {
							$file->set_status( WPML_ST_Translations_File_Entry::PARTLY_IMPORTED );
						}
					} catch ( WPML_ST_Bulk_Strings_Insert_Exception $e ) {
						$file->set_status( WPML_ST_Translations_File_Entry::PARTLY_IMPORTED );
						break;
					} catch ( Exception $e ) {
						$file->set_status( WPML_ST_Translations_File_Entry::IMPORTED );
					}
				} else {
					$file->set_status( WPML_ST_Translations_File_Entry::SKIPPED );
				}
				$this->file_dictionary->save( $file );

				do_action( 'wpml_st_translations_file_post_import', $file );
			}

			$this->unlock();
		}
	}

	/**
	 * @param string $locale
	 *
	 * @return string
	 */
	private function map_language_code( $locale ) {
		$language_code = $this->language_records->get_language_code( $locale );

		if ( $language_code ) {
			return $language_code;
		}

		return $locale;
	}

	/**
	 * @return bool
	 */
	public function is_completed() {
		return 0 === count( $this->file_dictionary->get_not_imported_files() ) &&
			   0 < count( $this->file_dictionary->get_imported_files() );
	}

	/**
	 * @return string[]
	 */
	public function get_processed() {
		return wp_list_pluck( $this->file_dictionary->get_imported_files(), 'path' );
	}

	/**
	 * @return bool
	 */
	public function is_processing() {
		return 0 !== count( $this->file_dictionary->get_not_imported_files() );
	}

	/**
	 * @return int
	 */
	public function get_pending() {
		return count( $this->file_dictionary->get_not_imported_files() );
	}

	public function mark_as_finished() {
		foreach ( $this->file_dictionary->get_imported_files() as $file ) {
			$file->set_status( WPML_ST_Translations_File_Entry::FINISHED );
			$this->file_dictionary->save( $file );
		}
	}

	/**
	 * @param array $translations
	 * @param int   $offset
	 * @param int   $limit
	 *
	 * @return array
	 */
	private function constrain_translations_number( array $translations, $offset, $limit ) {
		if ( $limit > count( $translations ) ) {
			return $translations;
		}

		return array_slice( $translations, $offset, $limit );
	}

	public function is_locked() {
		return (bool) $this->transient->get( self::LOCK_FIELD );
	}

	private function lock() {
		$this->transient->set( self::LOCK_FIELD, 1, MINUTE_IN_SECONDS * 5 );
	}

	private function unlock() {
		$this->transient->delete( self::LOCK_FIELD );
	}
}
