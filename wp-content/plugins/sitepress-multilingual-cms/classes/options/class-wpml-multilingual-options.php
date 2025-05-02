<?php

/**
 * Class WPML_Multilingual_Options
 */
class WPML_Multilingual_Options {

	const NOTICE_GROUP = 'wpml-multilingual-options';

	private $array_helper;
	private $registered_options = array();
	private $sitepress;
	private $utils;

	/**
	 * WPML_Multilingual_Options constructor.
	 *
	 * @param SitePress                              $sitepress
	 * @param WPML_Multilingual_Options_Array_Helper $array_helper
	 * @param WPML_Multilingual_Options_Utils        $utils
	 */
	public function __construct( SitePress $sitepress, WPML_Multilingual_Options_Array_Helper $array_helper, WPML_Multilingual_Options_Utils $utils ) {
		$this->sitepress    = $sitepress;
		$this->array_helper = $array_helper;
		$this->utils        = $utils;
	}

	/**
	 * @param string $new_code         New WPML default language code
	 * @param string $previous_default Previous WPML default language code
	 */
	public function default_language_changed_action( $new_code, $previous_default ) {
		if ( $new_code !== $previous_default ) {
			foreach ( $this->registered_options as $option_name ) {
				$default_options   = $this->utils->get_option_without_filtering( $option_name, null );
				$translated_option = get_option( "{$option_name}_{$new_code}", null );
				if ( $translated_option ) {
					$new_value = $this->merge( $default_options, $translated_option );
					remove_filter( "pre_option_{$option_name}", array( $this, 'pre_option_filter' ), 10 );
					remove_filter(
						"pre_update_option_{$option_name}",
						array(
							$this,
							'pre_update_option_filter',
						),
						10
					);
					update_option( $option_name, $new_value );
					delete_option( "{$option_name}_{$new_code}" );
					$new_translated_option = $default_options;
					if ( is_array( $default_options ) && is_array( $new_value ) ) {
						$new_translated_option = $this->array_helper->array_diff_recursive( $default_options, $new_value );
					}
					update_option( "{$option_name}_{$previous_default}", $new_translated_option, 'no' );
					$this->multilingual_options_action( $option_name );
				}
				$this->invalidate_cache( $option_name, $previous_default );
				$this->invalidate_cache( $option_name, $new_code );
			}
		}
	}

	/**
	 * @param string|null $option_name
	 * @param string[]    $option_pages
	 */
	public function multilingual_options_action( $option_name = null, $option_pages = [] ) {
		if ( $option_name && ! in_array( $option_name, $this->registered_options, true ) ) {
			$this->registered_options[] = $option_name;
			$current_language           = $this->sitepress->get_current_language();
			$default_language           = $this->sitepress->get_default_language();
			if ( $current_language !== $default_language ) {
				add_filter( "pre_option_{$option_name}", array( $this, 'pre_option_filter' ), 10, 2 );
				add_filter( "pre_update_option_{$option_name}", array( $this, 'pre_update_option_filter' ), 10, 3 );
			}
		}
		foreach ( $option_pages as $page_id ) {
			$this->add_notice( $page_id );
		}
	}

	/**
	 * @param string $page_id
	 */
	private function add_notice( $page_id ) {
		$notice_id     = md5( $page_id );
		$admin_notices = wpml_get_admin_notices();
		$admin_notices->remove_notice( self::NOTICE_GROUP, $notice_id );

		$text   = '<h4>' . __( 'You can set different options for each language', 'sitepress' ) . '</h4>'
			. '<p>' . __( 'Use the language switcher in the top admin bar to switch languages, then set and save options for each language individually.', 'sitepress' ) . '</p>';
		$notice = new \WPML_Notice( $notice_id, $text, self::NOTICE_GROUP );
		$notice->set_css_class_types( 'notice-info' );
		$notice->set_restrict_to_screen_ids( [ $page_id ] );
		$notice->set_dismissible( true );
		$admin_notices->add_notice( $notice, true );
	}

	public function init_hooks() {
		add_action( 'wpml_multilingual_options', array( $this, 'multilingual_options_action' ), 10, 2 );
		add_action( 'icl_after_set_default_language', array( $this, 'default_language_changed_action' ), 10, 2 );
	}

	/**
	 * @param mixed  $value
	 * @param string $option_name
	 *
	 * @return mixed
	 */
	public function pre_option_filter( $value, $option_name ) {
		$current_language = $this->sitepress->get_current_language();
		$cache_found      = null;
		$options_filtered = wp_cache_get( "{$option_name}_{$current_language}_filtered", 'options', false, $cache_found );
		if ( $cache_found ) {
			return $options_filtered;
		}

		$default_options  = $this->utils->get_option_without_filtering( $option_name, null );
		$translated_value = get_option( "{$option_name}_{$current_language}", $value );

		$value = $this->merge( $default_options, $translated_value );
		$this->update_cache( $option_name, $current_language, $value );

		return $value;
	}

	/**
	 * @param string $option_name
	 * @param string $language
	 * @param mixed  $value
	 *
	 * @return bool
	 */
	private function update_cache( $option_name, $language, $value ) {
		return wp_cache_set( "{$option_name}_{$language}_filtered", $value, 'options' );
	}

	/**
	 * @param array<mixed>|mixed $new_value
	 * @param array<mixed>|mixed $old_value
	 * @param string $option_name
	 *
	 * @return array
	 */
	public function pre_update_option_filter( $new_value, $old_value, $option_name ) {

		$current_language  = $this->sitepress->get_current_language();
		$default_options   = $this->utils->get_option_without_filtering( $option_name, null );
		$translated_option = null;

		if ( is_array( $new_value ) && is_array( $default_options ) ) {
			$translated_option = $this->array_helper->array_diff_recursive( $new_value, $default_options );
		} elseif ( $new_value !== $default_options ) {
			$translated_option = $new_value;
		}

		update_option( "{$option_name}_{$current_language}", $translated_option, 'no' );
		$this->invalidate_cache( $option_name, $current_language );

		return $default_options;
	}

	/**
	 * @param string $option_name
	 * @param string $language
	 *
	 * @return bool
	 */
	private function invalidate_cache( $option_name, $language ) {
		return wp_cache_delete( "{$option_name}_{$language}_filtered", 'options' );
	}

	/**
	 * @param array $target
	 * @param array $source
	 *
	 * @return array
	 */
	private function merge( $target, $source ) {
		$value = $source;
		if ( is_array( $source ) && is_array( $target ) ) {
			$value = $this->array_helper->recursive_merge( $target, $source );
		}

		return $value;
	}
}
