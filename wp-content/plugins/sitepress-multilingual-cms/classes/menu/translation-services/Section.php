<?php

namespace WPML\TM\Menu\TranslationServices;
use WPML\LIB\WP\User;

class Section implements \IWPML_TM_Admin_Section {
	const SLUG = 'translators';

	/**
	 * The SitePress instance.
	 *
	 * @var \SitePress
	 */
	private $sitepress;

	/**
	 * The WPML_WP_API instance.
	 *
	 * @var \WPML_WP_API
	 */
	private $wp_api;

	/**
	 * The template to use.
	 *
	 * @var mixed $template
	 */
	private $template;

	/**
	 * WPML_TM_Translation_Services_Admin_Section constructor.
	 *
	 * @param \SitePress $sitepress The SitePress instance.
	 * @param callable   $template  The template to use.
	 */
	public function __construct(
		\SitePress $sitepress,
		$template
	) {
		$this->sitepress = $sitepress;
		$this->wp_api    = $sitepress->get_wp_api();
		$this->template  = $template;
	}

	/**
	 * Returns a value which will be used for sorting the sections.
	 *
	 * @return int
	 */
	public function get_order() {
		return 400;
	}

	/**
	 * Outputs the content of the section.
	 */
	public function render() {
		call_user_func( $this->template );
	}

	/**
	 * Used to extend the logic for displaying/hiding the section.
	 *
	 * @return bool
	 */
	public function is_visible() {
		return ! $this->wp_api->constant( 'ICL_HIDE_TRANSLATION_SERVICES' ) && ( $this->wp_api->constant( 'WPML_BYPASS_TS_CHECK' ) || ! $this->sitepress->get_setting( 'translation_service_plugin_activated' ) );
	}

	/**
	 * Returns the unique slug of the sections which is used to build the URL for opening this section.
	 *
	 * @return string
	 */
	public function get_slug() {
		return self::SLUG;
	}

	/**
	 * Returns one or more capabilities required to display this section.
	 *
	 * @return string|array
	 */
	public function get_capabilities() {
		return [ User::CAP_MANAGE_TRANSLATIONS, User::CAP_ADMINISTRATOR ];
	}

	/**
	 * Returns the caption to display in the section.
	 *
	 * @return string
	 */
	public function get_caption() {
		return __( 'Translation Services', 'wpml-translation-management' );
	}

	/**
	 * Returns the callback responsible for rendering the content of the section.
	 *
	 * @return callable
	 */
	public function get_callback() {
		return array( $this, 'render' );
	}

	/**
	 * This method is hooked to the `admin_enqueue_scripts` action.
	 *
	 * @param string $hook The current page.
	 */
	public function admin_enqueue_scripts( $hook ) {}
}
