<?php

class WPML_ST_JED_File_Update_Hooks implements IWPML_Action {

	/** @var WPML_ST_JED_File_Manager $jed_file_manager */
	private $jed_file_manager;

	/** @var WPML_ST_JED_Locales_Domains_Mapper $domains_locales_mapper */
	private $domains_locales_mapper;

	/** @var array $updated_translation_ids */
	private $updated_translation_ids = array();

	public function __construct(
		WPML_ST_JED_File_Manager $jed_file_manager,
		WPML_ST_JED_Locales_Domains_Mapper $domains_locales_mapper
	) {
		$this->jed_file_manager       = $jed_file_manager;
		$this->domains_locales_mapper = $domains_locales_mapper;
	}

	public function add_hooks() {
		add_action( 'wpml_st_add_string_translation', array( $this, 'add_to_update_queue' ) );
		add_action( 'wpml_st_translations_file_post_import', array( $this, 'update_imported_file' ) );
	}

	/** @param int $string_translation_id */
	public function add_to_update_queue( $string_translation_id ) {
		if ( ! in_array( $string_translation_id, $this->updated_translation_ids, true ) ) {
			$this->updated_translation_ids[] = $string_translation_id;

			if ( ! has_action( 'shutdown', array( $this, 'process_update_queue' ) ) ) {
				add_action( 'shutdown', array( $this, 'process_update_queue' ) );
			}
		}
	}

	public function process_update_queue() {
		$outdated_entities = $this->domains_locales_mapper->get_from_translation_ids( $this->updated_translation_ids );

		foreach ( $outdated_entities as $entity ) {
			$this->update_file( $entity->domain, $entity->locale );
		}
	}

	public function update_imported_file( WPML_ST_Translations_File_Entry $file_entry ) {
		$this->update_file( $file_entry->get_domain(), $file_entry->get_file_locale() );
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 */
	private function update_file( $domain, $locale ) {
		if ( $this->jed_file_manager->get( $domain, $locale ) ) {
			$this->jed_file_manager->remove( $domain, $locale );
			$this->jed_file_manager->add( $domain, $locale );
		}
	}
}
