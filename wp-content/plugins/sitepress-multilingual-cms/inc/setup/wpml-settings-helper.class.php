<?php

class WPML_Settings_Helper {

	/** @var SitePress */
	protected $sitepress;

	/** @var WPML_Post_Translation */
	protected $post_translation;

	/**
	 * @var WPML_Settings_Filters
	 */
	private $filters;

	/**
	 * @param WPML_Post_Translation $post_translation
	 * @param SitePress             $sitepress
	 */
	public function __construct( WPML_Post_Translation $post_translation, SitePress $sitepress ) {
		$this->sitepress        = $sitepress;
		$this->post_translation = $post_translation;
	}

	/**
	 * @return WPML_Settings_Filters
	 */
	private function get_filters() {
		if ( ! $this->filters ) {
			$this->filters = new WPML_Settings_Filters();
		}

		return $this->filters;
	}

	function set_post_type_translatable( $post_type ) {
		$this->set_post_type_translate_mode( $post_type, WPML_CONTENT_TYPE_TRANSLATE );
	}

	function set_post_type_display_as_translated( $post_type ) {
		$this->set_post_type_translate_mode( $post_type, WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED );
	}

	function set_post_type_not_translatable( $post_type ) {
		$sync_settings = $this->sitepress->get_setting( 'custom_posts_sync_option', array() );
		if ( isset( $sync_settings[ $post_type ] ) ) {
			unset( $sync_settings[ $post_type ] );
		}

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'custom_posts_sync_option', $sync_settings, true );
	}

	private function set_post_type_translate_mode( $post_type, $mode ) {
		$sync_settings               = $this->sitepress->get_setting( 'custom_posts_sync_option', array() );
		$sync_settings[ $post_type ] = $mode;
		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'custom_posts_sync_option', $sync_settings, true );
		$this->sitepress->verify_post_translations( $post_type );
		$this->post_translation->reload();
	}

	function set_taxonomy_translatable( $taxonomy ) {
		$this->set_taxonomy_translatable_mode( $taxonomy, WPML_CONTENT_TYPE_TRANSLATE );
	}

	function set_taxonomy_display_as_translated( $taxonomy ) {
		$this->set_taxonomy_translatable_mode( $taxonomy, WPML_CONTENT_TYPE_DISPLAY_AS_IF_TRANSLATED );
	}

	function set_taxonomy_translatable_mode( $taxonomy, $mode ) {
		$sync_settings              = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );
		$sync_settings[ $taxonomy ] = $mode;
		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'taxonomies_sync_option', $sync_settings, true );
		$this->sitepress->verify_taxonomy_translations( $taxonomy );
	}

	function set_taxonomy_not_translatable( $taxonomy ) {
		$sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );
		if ( isset( $sync_settings[ $taxonomy ] ) ) {
			unset( $sync_settings[ $taxonomy ] );
		}

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'taxonomies_sync_option', $sync_settings, true );
	}

	function set_post_type_translation_unlocked_option( $post_type, $unlocked = true ) {

		$unlocked_settings = $this->sitepress->get_setting( 'custom_posts_unlocked_option', array() );

		$unlocked_settings[ $post_type ] = $unlocked ? 1 : 0;

		$this->sitepress->set_setting( 'custom_posts_unlocked_option', $unlocked_settings, true );
	}

	function set_taxonomy_translation_unlocked_option( $taxonomy, $unlocked = true ) {

		$unlocked_settings = $this->sitepress->get_setting( 'taxonomies_unlocked_option', array() );

		$unlocked_settings[ $taxonomy ] = $unlocked ? 1 : 0;

		$this->sitepress->set_setting( 'taxonomies_unlocked_option', $unlocked_settings, true );
	}

	function activate_slug_translation( $post_type ) {
		$slug_settings                          = $this->sitepress->get_setting( 'posts_slug_translation', array() );
		$slug_settings[ 'types' ]               = isset( $slug_settings[ 'types' ] )
			? $slug_settings[ 'types' ] : array();
		$slug_settings[ 'types' ][ $post_type ] = 1;
		$slug_settings[ 'on' ]                  = 1;

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
	}

	function deactivate_slug_translation( $post_type ) {
		$slug_settings = $this->sitepress->get_setting( 'posts_slug_translation', array() );
		if ( isset( $slug_settings[ 'types' ][ $post_type ] ) ) {
			unset( $slug_settings[ 'types' ][ $post_type ] );
		}

		$this->clear_ls_languages_cache();
		$this->sitepress->set_setting( 'posts_slug_translation', $slug_settings, true );
	}

	/**
	 * @param array[] $taxs_obj_type
	 *
	 * @see \WPML_Config::maybe_add_filter
	 *
	 * @return array
	 */
	function _override_get_translatable_taxonomies( $taxs_obj_type ) {
		global $wp_taxonomies;

		$taxs        = $taxs_obj_type['taxs'];
		$object_type = $taxs_obj_type['object_type'];
		foreach ( $taxs as $k => $tax ) {
			if ( ! $this->sitepress->is_translated_taxonomy( $tax ) ) {
				unset( $taxs[ $k ] );
			}
		}
		$tm_settings = $this->sitepress->get_setting( 'translation-management', array() );
		foreach ( $tm_settings['taxonomies_readonly_config'] as $tx => $translate ) {
			if ( $translate
			     && ! in_array( $tx, $taxs )
			     && isset( $wp_taxonomies[ $tx ] )
			     && in_array( $object_type, $wp_taxonomies[ $tx ]->object_type )
			) {
				$taxs[] = $tx;
			}
		}

		$ret = array( 'taxs' => $taxs, 'object_type' => $taxs_obj_type['object_type'] );

		return $ret;
	}

	/**
	 * @param array[] $types
	 *
	 * @see \WPML_Config::maybe_add_filter
	 *
	 * @return array
	 */
	function _override_get_translatable_documents( $types ) {
		$tm_settings = $this->sitepress->get_setting('translation-management', array());
		foreach ( $types as $k => $type ) {
			if ( isset( $tm_settings[ 'custom-types_readonly_config' ][ $k ] )
				 && ! $tm_settings[ 'custom-types_readonly_config' ][ $k ]
			) {
				unset( $types[ $k ] );
			}
		}
		$types = $this->get_filters()->get_translatable_documents( $types, $tm_settings['custom-types_readonly_config'] );

		return $types;
	}

	/**
	 * Updates the custom post type translation settings with new settings.
	 *
	 * @param array $new_options
	 *
	 * @uses \SitePress::get_setting
	 * @uses \SitePress::save_settings
	 *
	 * @return array new custom post type settings after the update
	 */
	function update_cpt_sync_settings( array $new_options ) {
		$cpt_sync_options = $this->sitepress->get_setting( 'custom_posts_sync_option', array() );
		$cpt_sync_options = array_merge( $cpt_sync_options, $new_options );
		$new_options      = array_filter( $new_options );

		$this->clear_ls_languages_cache();

		do_action( 'wpml_verify_post_translations', $new_options );
		do_action( 'wpml_save_cpt_sync_settings' );
		$this->sitepress->set_setting( 'custom_posts_sync_option', $cpt_sync_options, true );

		return $cpt_sync_options;
	}

	/**
	 * Updates the custom post type unlocked settings with new settings.
	 *
	 * @param array $unlock_options
	 *
	 * @uses \SitePress::get_setting
	 * @uses \SitePress::save_settings
	 *
	 * @return array new custom post type unlocked settings after the update
	 */
	function update_cpt_unlocked_settings( array $unlock_options ) {
		$cpt_unlock_options = $this->sitepress->get_setting( 'custom_posts_unlocked_option', array() );
		$cpt_unlock_options = array_merge( $cpt_unlock_options, $unlock_options );
		$this->sitepress->set_setting( 'custom_posts_unlocked_option', $cpt_unlock_options, true );
		return $cpt_unlock_options ;
	}

	/**
	 * @param string $config_type
	 */
	function maybe_add_filter( $config_type ) {
		if ( $config_type === 'taxonomies' ) {
			add_filter( 'get_translatable_taxonomies',
			            array( $this, '_override_get_translatable_taxonomies' ) );
		} elseif ( $config_type === 'custom-types' ) {
			add_filter( 'get_translatable_documents',
			            array( $this, '_override_get_translatable_documents' ) );
		}
	}

	private function clear_ls_languages_cache() {
		$cache = new WPML_WP_Cache( 'ls_languages' );
		$cache->flush_group_cache();
	}
}