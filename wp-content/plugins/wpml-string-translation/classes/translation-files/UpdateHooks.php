<?php

namespace WPML\ST\TranslationFile;

use stdClass;
use WPML\Collect\Support\Collection;
use WPML\Element\API\Languages;

class UpdateHooks implements \IWPML_Action {

	/** @var Manager $file_manager */
	private $file_manager;

	/** @var DomainsLocalesMapper $domains_locales_mapper */
	private $domains_locales_mapper;

	/** @var array $updated_translation_ids */
	private $updated_translation_ids = [];

	/** @var Collection $entities_to_update */
	private $entities_to_update;

	/** @var callable */
	private $resetDomainsCache;

	public function __construct(
		Manager $file_manager,
		DomainsLocalesMapper $domains_locales_mapper,
		callable $resetDomainsCache = null
	) {
		$this->file_manager           = $file_manager;
		$this->domains_locales_mapper = $domains_locales_mapper;
		$this->entities_to_update     = wpml_collect( [] );
		$this->resetDomainsCache      = $resetDomainsCache ?: [ Domains::class, 'resetCache' ];
	}

	public function add_hooks() {
		add_action( 'wpml_st_add_string_translation', array( $this, 'add_to_update_queue' ) );
		add_action( 'wpml_st_update_string', array( $this, 'refresh_after_update_original_string' ), 10, 6 );
		add_action( 'wpml_st_before_remove_strings', array( $this, 'refresh_before_remove_strings' ) );
		/**
		 * @see UpdateHooks::refresh_domain
		 * @since @3.1.0
		 */
		add_action( 'wpml_st_refresh_domain', [ $this, 'refresh_domain' ] );

		if ( ! $this->file_manager->isPartialFile() ) {
			add_action( 'wpml_st_translations_file_post_import', array( $this, 'update_imported_file' ) );
		}
	}

	/** @param int $string_translation_id */
	public function add_to_update_queue( $string_translation_id ) {
		if ( ! in_array( $string_translation_id, $this->updated_translation_ids, true ) ) {
			$this->updated_translation_ids[] = $string_translation_id;
			$this->add_shutdown_action();
		}
	}

	private function add_shutdown_action() {
		if ( ! has_action( 'shutdown', array( $this, 'process_update_queue' ) ) ) {
			add_action( 'shutdown', array( $this, 'process_update_queue' ) );
		}
	}

	/**
	 * @return array
	 */
	public function process_update_queue() {
		call_user_func( $this->resetDomainsCache );

		$outdated_entities        = $this->domains_locales_mapper->get_from_translation_ids( $this->updated_translation_ids );
		$this->entities_to_update = $this->entities_to_update->merge( $outdated_entities );

		$this->entities_to_update->each(
			function( $entity ) {
				$this->update_file( $entity->domain, $entity->locale );
			}
		);

		return $this->entities_to_update->toArray();
	}

	/**
	 * @param string     $domain
	 * @param string     $name
	 * @param string     $old_value
	 * @param string     $new_value
	 * @param bool|false $force_complete
	 * @param stdClass   $string
	 */
	public function refresh_after_update_original_string( $domain, $name, $old_value, $new_value, $force_complete, $string ) {
		$outdated_entities        = $this->domains_locales_mapper->get_from_string_ids( [ $string->id ] );
		$this->entities_to_update = $this->entities_to_update->merge( $outdated_entities );
		$this->add_shutdown_action();
	}

	public function update_imported_file( \WPML_ST_Translations_File_Entry $file_entry ) {
		if ( $file_entry->get_status() === \WPML_ST_Translations_File_Entry::IMPORTED ) {
			$this->update_file( $file_entry->get_domain(), $file_entry->get_file_locale() );
		}
	}

	/**
	 * It dispatches the regeneration of MO files for a specific domain in all active languages.
	 *
	 * @param string $domain
	 */
	public function refresh_domain( $domain ) {
		$outdated_entities        = $this->domains_locales_mapper->get_from_domain(
			[ Languages::class, 'getActive' ],
			$domain
		);
		$this->entities_to_update = $this->entities_to_update->merge( $outdated_entities );
		$this->add_shutdown_action();
	}

	/**
	 * We need to refresh before the strings are deleted,
	 * otherwise we can't determine which domains to refresh.
	 *
	 * @param array $string_ids
	 */
	public function refresh_before_remove_strings( array $string_ids ) {
		$outdated_entities        = $this->domains_locales_mapper->get_from_string_ids( $string_ids );
		$this->entities_to_update = $this->entities_to_update->merge( $outdated_entities );
		$this->add_shutdown_action();
	}

	/**
	 * @param string $domain
	 * @param string $locale
	 */
	private function update_file( $domain, $locale ) {
		/**
		 * It does not matter whether MO/JED file can be handled or not, that's why `remove` method is placed before `handles` check.
		 * If a file is not `handable` then it can still should be removed.
		 */
		$this->file_manager->remove( $domain, $locale );
		if ( $this->file_manager->handles( $domain ) ) {
			$this->file_manager->add( $domain, $locale );
		}
	}
}
