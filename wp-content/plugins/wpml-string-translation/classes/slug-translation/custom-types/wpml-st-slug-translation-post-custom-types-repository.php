<?php

class WPML_ST_Slug_Translation_Post_Custom_Types_Repository implements WPML_ST_Slug_Translation_Custom_Types_Repository {
	/** @var SitePress */
	private $sitepress;

	/** @var WPML_ST_Slug_Custom_Type_Factory */
	private $custom_type_factory;

	/** @var array */
	private $post_slug_translation_settings;

	public function __construct( SitePress $sitepress, WPML_ST_Slug_Custom_Type_Factory $custom_type_factory ) {
		$this->sitepress           = $sitepress;
		$this->custom_type_factory = $custom_type_factory;
	}


	public function get() {
		return array_map(
			array( $this, 'build_object' ),
			array_values( array_filter(
				get_post_types( array( 'publicly_queryable' => true ) ),
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
		$post_slug_translation_settings = $this->get_post_slug_translation_settings();

		return isset( $post_slug_translation_settings['types'][ $type ] )
		       && $post_slug_translation_settings['types'][ $type ]
		       && $this->sitepress->is_translated_post_type( $type );
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
	private function get_post_slug_translation_settings() {
		if ( null === $this->post_slug_translation_settings ) {
			$this->post_slug_translation_settings = $this->sitepress->get_setting( 'posts_slug_translation', array() );
		}

		return $this->post_slug_translation_settings;
	}


	/**
	 * @param string $type
	 *
	 * @return bool
	 */
	private function is_display_as_translated( $type ) {
		return $this->sitepress->is_display_as_translated_post_type( $type );
	}
}