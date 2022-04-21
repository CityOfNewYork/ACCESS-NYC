<?php

use WPML\FP\Relation;

/**
 * It handles the admin sections shown in the TM page.
 *
 * @author OnTheGo Systems
 */
class WPML_TM_Admin_Sections {
	/**
	 * It stores the tab items.
	 *
	 * @var array The tab items.
	 */
	private $tab_items = array();

	/**
	 * It stores the tab items.
	 *
	 * @var IWPML_TM_Admin_Section[] The admin sections.
	 */
	private $admin_sections = array();
	/** @var array */
	private $items_urls = array();

	/**
	 * It adds the hooks.
	 */
	public function init_hooks() {
		/**
		 * We have to defer creating of Admin Section to be sure that `WP_Installer` class is already loaded
		 */
		add_action( 'init', [ $this, 'init_sections' ] );
	}

	public function init_sections() {
		foreach ( $this->get_admin_sections() as $section ) {
			$this->tab_items[ $section->get_slug() ] = [
				'caption'          => $section->get_caption(),
				'current_user_can' => $section->get_capabilities(),
				'callback'         => $section->get_callback(),
				'order'            => $section->get_order(),
			];
			add_action( 'admin_enqueue_scripts', [ $section, 'admin_enqueue_scripts' ] );
		}
	}

	/**
	 * @return \IWPML_TM_Admin_Section[]
	 */
	private function get_admin_sections() {
		if ( ! $this->admin_sections ) {
			foreach ( $this->get_admin_section_factories() as $factory ) {
				if ( in_array( 'IWPML_TM_Admin_Section_Factory', class_implements( $factory ), true ) ) {
					$sections_factory = new $factory();
					/**
					 * Sections are defined through classes extending `\IWPML_TM_Admin_Section_Factory`.
					 *
					 * @var \IWPML_TM_Admin_Section_Factory $sections_factory An instance of the section factory.
					 */
					$section = $sections_factory->create();

					if ( $section && in_array( 'IWPML_TM_Admin_Section', class_implements( $section ), true ) && $section->is_visible() ) {
						$this->admin_sections[ $section->get_slug() ] = $section;
					}
				}
			}
		}

		return $this->admin_sections;
	}

	/**
	 * It returns the tab items.
	 *
	 * @return array The tab items.
	 */
	public function get_tab_items() {
		return $this->tab_items;
	}

	/**
	 * It returns and filters the admin sections in the TM page.
	 *
	 * @return array<\WPML\TM\Menu\TranslationServices\SectionFactory|\WPML_TM_AMS_ATE_Console_Section_Factory|\WPML_TM_Translation_Roles_Section_Factory>
	 */
	private function get_admin_section_factories() {
		$admin_sections_factories = array(
			WPML_TM_Translation_Roles_Section_Factory::class,
			WPML_TM_AMS_ATE_Console_Section_Factory::class,
		);

		return apply_filters( 'wpml_tm_admin_sections_factories', $admin_sections_factories );
	}

	/**
	 * Returns the URL of a tab item or an empty string if it cannot be found.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function get_item_url( $slug ) {
		if ( $this->get_section( $slug ) ) {
			if ( ! array_key_exists( $slug, $this->items_urls ) ) {
				$this->items_urls[ $slug ] = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . WPML_Translation_Management::PAGE_SLUG_MANAGEMENT . '&sm=' . $slug );
			}

			return $this->items_urls[ $slug ];
		}

		return '';
	}

	/**
	 * Returns an instance of IWPML_TM_Admin_Section from its slug or null if it cannot be found.
	 *
	 * @param string $slug
	 *
	 * @return \IWPML_TM_Admin_Section|null
	 */
	public function get_section( $slug ) {
		$sections = $this->get_admin_sections();
		if ( array_key_exists( $slug, $sections ) ) {
			return $sections[ $slug ];
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public static function is_translation_roles_section() {
		return self::is_section( 'translators' );
	}

	/**
	 * @return bool
	 */
	public static function is_translation_services_section() {
		return self::is_section( 'translation-services' );
	}

	/**
	 * @return bool
	 */
	public static function is_dashboard_section() {
		return self::is_section( 'dashboard' );
	}

	/**
	 * @param string $section
	 *
	 * @return bool
	 */
	private static function is_section( $section ) {
		return Relation::propEq( 'page', 'tm/menu/main.php', $_GET ) &&
		       Relation::propEq( 'sm', $section, $_GET );
	}
}
