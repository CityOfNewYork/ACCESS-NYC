<?php

class WPML_ST_Slug_Translation_Taxonomy_Custom_Types_Repository implements WPML_ST_Slug_Translation_Custom_Types_Repository {
	/** @var SitePress */
	private $sitepress;

	/** @var WPML_ST_Slug_Custom_Type_Factory */
	private $custom_type_factory;

	/** @var WPML_ST_Tax_Slug_Translation_Settings $settings */
	private $settings_repository;

	/** @var array */
	private $settings;

	public function __construct(
		SitePress $sitepress,
		WPML_ST_Slug_Custom_Type_Factory $custom_type_factory,
		WPML_ST_Tax_Slug_Translation_Settings $settings_repository
	) {
		$this->sitepress           = $sitepress;
		$this->custom_type_factory = $custom_type_factory;
		$this->settings_repository = $settings_repository;
	}


	public function get() {
		return array_map(
			array( $this, 'build_object' ),
			array_values( array_filter(
				get_taxonomies( array( 'publicly_queryable' => true ) ),
				array( $this, 'filter' )
			) )
		);
	}

	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	private function filter( $type ) {
		$settings = $this->get_taxonomy_slug_translation_settings();

		return isset( $settings[ $type ] )
		       && $settings[ $type ]
		       && $this->sitepress->is_translated_taxonomy( $type );
	}

	/**
	 * @param string $type
	 *
	 * @return WPML_ST_Slug_Custom_Type
	 */
	private function build_object( $type ) {
		return $this->custom_type_factory->create( $type, $this->is_display_as_translated( $type ) );
	}

	/**
	 * @return array
	 */
	private function get_taxonomy_slug_translation_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->settings_repository->get_types();
		}

		return $this->settings;
	}


	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	private function is_display_as_translated( $type ) {
		return $this->sitepress->is_display_as_translated_taxonomy( $type );
	}
}