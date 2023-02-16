<?php

class WPML_ST_Blog_Name_And_Description_Hooks implements \IWPML_Action {

	const STRING_DOMAIN               = 'WP';
	const STRING_NAME_BLOGNAME        = 'Blog Title';
	const STRING_NAME_BLOGDESCRIPTION = 'Tagline';

	/** @var array $cache */
	private $cache = [];

	/**
	 * Detect if ST is not installed on the current blog of multisite
	 *
	 * @var bool $is_active_on_current_blog
	 */
	private $is_active_on_current_blog = true;

	public function add_hooks() {
		if ( ! $this->is_customize_page() ) {
			add_filter( 'option_blogname', [ $this, 'option_blogname_filter' ] );
			add_filter( 'option_blogdescription', [ $this, 'option_blogdescription_filter' ] );
			add_action( 'wpml_language_has_switched', [ $this, 'clear_cache' ] );
			add_action( 'switch_blog', [ $this, 'switch_blog_action' ] );
		}
	}

	/** @return bool */
	private function is_customize_page() {
		global $pagenow;

		return 'customize.php' === $pagenow;
	}

	/**
	 * @param string $blogname
	 *
	 * @return string
	 */
	public function option_blogname_filter( $blogname ) {
		return $this->translate_option( self::STRING_NAME_BLOGNAME, $blogname );
	}

	/**
	 * @param string $blogdescription
	 *
	 * @return string
	 */
	public function option_blogdescription_filter( $blogdescription ) {
		return $this->translate_option( self::STRING_NAME_BLOGDESCRIPTION, $blogdescription );
	}

	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return string
	 */
	private function translate_option( $name, $value ) {
		if ( ! $this->is_active_on_current_blog || ! wpml_st_is_requested_blog() ) {
			return $value;
		}

		if ( ! isset( $this->cache[ $name ] ) ) {
			$this->cache[ $name ] = wpml_get_string_current_translation(
				$value,
				self::STRING_DOMAIN,
				$name
			);
		}

		return $this->cache[ $name ];
	}

	/**
	 * As the translation depends on `WPML_String_Translation::get_current_string_language`,
	 * we added this clear cache callback on `wpml_language_has_switched` as done
	 * in `WPML_String_Translation::wpml_language_has_switched`.
	 */
	public function clear_cache() {
		$this->cache = [];
	}

	public function switch_blog_action() {
		$this->is_active_on_current_blog = is_plugin_active( basename( WPML_ST_PATH ) . '/plugin.php' );
	}

	/**
	 * @param string $string_name
	 *
	 * Checks whether a given string is to be translated in the Admin back-end.
	 * Currently only tagline and title of a site are to be translated.
	 * All other admin strings are to always be displayed in the user admin language.
	 *
	 * @return bool
	 */
	public static function is_string( $string_name ) {
		return in_array(
			$string_name,
			[
				self::STRING_NAME_BLOGDESCRIPTION,
				self::STRING_NAME_BLOGNAME,
			],
			true
		);
	}
}
