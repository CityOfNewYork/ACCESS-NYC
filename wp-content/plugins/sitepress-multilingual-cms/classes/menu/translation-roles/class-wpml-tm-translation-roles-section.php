<?php

use WPML\TranslationRoles\UI\Initializer;
use WPML\TM\Menu\TranslationServices\Section;
use WPML\TM\Menu\TranslationMethod\TranslationMethodSettings;
use WPML\FP\Relation;
use WPML\LIB\WP\User;

class WPML_TM_Translation_Roles_Section implements IWPML_TM_Admin_Section {
	const SLUG = 'translators';

	/**
	 * @var Section
	 */
	private $translation_services_section;

	public function __construct( Section $translation_services_section ) {
		$this->translation_services_section = $translation_services_section;

		TranslationMethodSettings::addHooks();
	}

	/**
	 * Returns a value which will be used for sorting the sections.
	 *
	 * @return int
	 */
	public function get_order() {
		return 300;
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
		return __( 'Translators', 'wpml-translation-management' );

	}

	/**
	 * Returns the callback responsible for rendering the content of the section.
	 *
	 * @return callable
	 */
	public function get_callback() {
		return [ $this, 'render' ];
	}

	/**
	 * This method is hooked to the `admin_enqueue_scripts` action.
	 *
	 * @param string $hook The current page.
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( Relation::propEq( 'sm', 'translators', $_GET ) ) {
			Initializer::loadJS();
		}
	}

	/**
	 * Used to extend the logic for displaying/hiding the section.
	 *
	 * @return bool
	 */
	public function is_visible() {
		return true;
	}

	/**
	 * Outputs the content of the section.
	 */
	public function render() {
		?>
		<div id="wpml-translation-roles-ui-container"></div>
		<?php
		$this->translation_services_section->render();
	}
}
